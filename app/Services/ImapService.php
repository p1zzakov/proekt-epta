<?php

namespace App\Services;

use App\Models\Mailbox;
use Illuminate\Support\Collection;

class ImapService
{
    private string $host   = 'host.docker.internal';
    private int    $port   = 143;
    private string $domain = 'viewlab.top';

    /**
     * Получить письма из папки
     */
    public function getMessages(Mailbox $mailbox, string $folder = 'INBOX', int $limit = 50): Collection
    {
        $connection = $this->connect($mailbox);
        if (!$connection) return collect();

        try {
            $messages = [];
            $count    = imap_num_msg($connection);
            $start    = max(1, $count - $limit + 1);

            for ($i = $count; $i >= $start; $i--) {
                $header = imap_headerinfo($connection, $i);
                $uid    = imap_uid($connection, $i);

                $messages[] = [
                    'uid'     => $uid,
                    'num'     => $i,
                    'from'    => $this->parseAddress($header->from[0] ?? null),
                    'to'      => $this->parseAddress($header->to[0] ?? null),
                    'subject' => $this->decodeHeader($header->subject ?? '(no subject)'),
                    'date'    => $header->date ?? '',
                    'seen'    => str_contains($header->Unseen ?? '', 'U') ? false : true,
                    'answered'=> isset($header->Answered) && $header->Answered === 'A',
                ];
            }

            return collect($messages);
        } finally {
            imap_close($connection);
        }
    }

    /**
     * Получить одно письмо полностью
     */
    public function getMessage(Mailbox $mailbox, int $uid, string $folder = 'INBOX'): ?array
    {
        $connection = $this->connect($mailbox);
        if (!$connection) return null;

        try {
            $num    = imap_msgno($connection, $uid);
            if (!$num) return null;

            $header  = imap_headerinfo($connection, $num);
            $structure = imap_fetchstructure($connection, $num);

            // Отмечаем как прочитанное
            imap_setflag_full($connection, (string)$uid, '\\Seen', ST_UID);

            $body = $this->getBody($connection, $num, $structure);

            return [
                'uid'     => $uid,
                'from'    => $this->parseAddress($header->from[0] ?? null),
                'to'      => $this->parseAddress($header->to[0] ?? null),
                'cc'      => isset($header->cc) ? $this->parseAddress($header->cc[0]) : null,
                'subject' => $this->decodeHeader($header->subject ?? '(no subject)'),
                'date'    => $header->date ?? '',
                'body'    => $body,
                'raw_from'=> $header->fromaddress ?? '',
            ];
        } finally {
            imap_close($connection);
        }
    }

    /**
     * Получить папки
     */
    public function getFolders(Mailbox $mailbox): array
    {
        $connection = $this->connect($mailbox);
        if (!$connection) return ['INBOX'];

        try {
            $list    = imap_list($connection, $this->mailboxString($mailbox), '*');
            $folders = [];
            foreach ($list ?: [] as $folder) {
                $name = str_replace($this->mailboxString($mailbox), '', $folder);
                $folders[] = $name;
            }
            return $folders;
        } finally {
            imap_close($connection);
        }
    }

    /**
     * Удалить письмо
     */
    public function deleteMessage(Mailbox $mailbox, int $uid): bool
    {
        $connection = $this->connect($mailbox);
        if (!$connection) return false;

        try {
            imap_delete($connection, (string)$uid, FT_UID);
            imap_expunge($connection);
            return true;
        } finally {
            imap_close($connection);
        }
    }

    /**
     * Количество непрочитанных
     */
    public function getUnreadCount(Mailbox $mailbox): int
    {
        $connection = $this->connect($mailbox);
        if (!$connection) return 0;

        try {
            $check = imap_check($connection);
            $unseen = imap_search($connection, 'UNSEEN');
            return $unseen ? count($unseen) : 0;
        } finally {
            imap_close($connection);
        }
    }

    // ─────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────

    private function connect(Mailbox $mailbox, string $folder = 'INBOX')
    {
        if (!function_exists('imap_open')) return null;

        $mailboxStr = $this->mailboxString($mailbox, $folder);

        // Получаем пароль из файла
        $password = $this->getPassword($mailbox->email);
        if (!$password) return null;

        return @imap_open($mailboxStr, $mailbox->email, $password, 0, 1, [
            'DISABLE_AUTHENTICATOR' => 'GSSAPI'
        ]);
    }

    private function mailboxString(Mailbox $mailbox, string $folder = 'INBOX'): string
    {
        return "{{$this->host}:{$this->port}/imap/notls}{$folder}";
    }

    private function getPassword(string $email): ?string
    {
        // Читаем пароль из Laravel storage (зашифрованный)
        $file = storage_path("app/mailbox_passwords/{$email}.txt");
        if (file_exists($file)) {
            return trim(file_get_contents($file));
        }
        return null;
    }

    private function getBody($connection, int $num, $structure): string
    {
        // Пробуем HTML, потом plain text
        if ($structure->type === 0) {
            // Простое сообщение
            $encoding = $structure->encoding;
            $body     = imap_fetchbody($connection, $num, '1');
            return $this->decodeBody($body, $encoding);
        }

        // Multipart
        foreach ($structure->parts as $idx => $part) {
            $partNum = $idx + 1;
            if ($part->subtype === 'HTML') {
                $body = imap_fetchbody($connection, $num, (string)$partNum);
                return $this->decodeBody($body, $part->encoding);
            }
        }

        // Fallback — plain text
        foreach ($structure->parts as $idx => $part) {
            if ($part->type === 0) {
                $body = imap_fetchbody($connection, $num, (string)($idx + 1));
                return nl2br(htmlspecialchars($this->decodeBody($body, $part->encoding)));
            }
        }

        return '';
    }

    private function decodeBody(string $body, int $encoding): string
    {
        return match($encoding) {
            3 => base64_decode($body),
            4 => quoted_printable_decode($body),
            default => $body,
        };
    }

    private function decodeHeader(string $str): string
    {
        $decoded = imap_mime_header_decode($str);
        $result  = '';
        foreach ($decoded as $part) {
            $charset = $part->charset === 'default' ? 'UTF-8' : $part->charset;
            $result .= mb_convert_encoding($part->text, 'UTF-8', $charset);
        }
        return $result;
    }

    private function parseAddress(?object $addr): string
    {
        if (!$addr) return '';
        $name  = isset($addr->personal) ? $this->decodeHeader($addr->personal) : '';
        $email = ($addr->mailbox ?? '') . '@' . ($addr->host ?? '');
        return $name ? "{$name} <{$email}>" : $email;
    }
}
