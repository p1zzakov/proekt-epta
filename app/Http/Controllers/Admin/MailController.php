<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;

class MailController extends Controller
{
    public function __construct(private ImapService $imap) {}

    // Список ящиков
    public function index()
    {
        $mailboxes = Mailbox::orderBy('email')->get();
        return view('admin.mail.index', compact('mailboxes'));
    }

    // Создать ящик
    public function store(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email|unique:mailboxes,email',
            'name'     => 'nullable|string|max:64',
            'password' => 'required|string|min:6',
            'note'     => 'nullable|string',
        ]);

        $domain    = explode('@', $data['email'])[1];
        $localPart = explode('@', $data['email'])[0];
        $password  = $data['password'];

        // Генерируем SHA512-CRYPT хэш
        $hash = shell_exec("doveadm pw -s SHA512-CRYPT -p " . escapeshellarg($password));
        $hash = trim($hash);

        // Добавляем в virtual_passwd
        file_put_contents(
            '/etc/dovecot/virtual_passwd',
            "{$data['email']}:{$hash}\n",
            FILE_APPEND
        );

        // Добавляем в postfix virtual_mailbox
        file_put_contents(
            '/etc/postfix/virtual_mailbox',
            "{$data['email']}  {$domain}/{$localPart}/Maildir/\n",
            FILE_APPEND
        );
        shell_exec('postmap /etc/postfix/virtual_mailbox');
        shell_exec('postfix reload');

        // Создаём Maildir
        $maildir = "/var/mail/vhosts/{$domain}/{$localPart}/Maildir";
        foreach (['', '/cur', '/new', '/tmp'] as $sub) {
            if (!is_dir($maildir . $sub)) {
                mkdir($maildir . $sub, 0755, true);
            }
        }
        shell_exec("chown -R vmail:vmail /var/mail/vhosts/{$domain}/{$localPart}");

        // Сохраняем пароль для IMAP
        $passDir = storage_path('app/mailbox_passwords');
        if (!is_dir($passDir)) mkdir($passDir, 0700, true);
        file_put_contents("{$passDir}/{$data['email']}.txt", $password);

        Mailbox::create([
            'email'         => $data['email'],
            'name'          => $data['name'] ?? null,
            'password_hash' => $hash,
            'note'          => $data['note'] ?? null,
        ]);

        return redirect()->route('admin.mail.index')
            ->with('success', "Ящик {$data['email']} создан!");
    }

    // Удалить ящик
    public function destroy(Mailbox $mailbox)
    {
        $email     = $mailbox->email;
        $localPart = $mailbox->local_part;
        $domain    = $mailbox->domain;

        // Убираем из virtual_passwd
        $lines = file('/etc/dovecot/virtual_passwd', FILE_IGNORE_NEW_LINES);
        $lines = array_filter($lines, fn($l) => !str_starts_with($l, "{$email}:"));
        file_put_contents('/etc/dovecot/virtual_passwd', implode("\n", $lines) . "\n");

        // Убираем из virtual_mailbox
        $lines = file('/etc/postfix/virtual_mailbox', FILE_IGNORE_NEW_LINES);
        $lines = array_filter($lines, fn($l) => !str_starts_with($l, "{$email} "));
        file_put_contents('/etc/postfix/virtual_mailbox', implode("\n", $lines) . "\n");
        shell_exec('postmap /etc/postfix/virtual_mailbox');
        shell_exec('postfix reload');

        // Удаляем файл пароля
        @unlink(storage_path("app/mailbox_passwords/{$email}.txt"));

        $mailbox->delete();

        return redirect()->route('admin.mail.index')
            ->with('success', "Ящик {$email} удалён!");
    }

    // Входящие
    public function inbox(Mailbox $mailbox, Request $request)
    {
        $folder   = $request->get('folder', 'INBOX');
        $messages = $this->imap->getMessages($mailbox, $folder, 50);
        $folders  = $this->imap->getFolders($mailbox);
        $unread   = $this->imap->getUnreadCount($mailbox);

        return view('admin.mail.inbox', compact('mailbox', 'messages', 'folders', 'folder', 'unread'));
    }

    // Читать письмо
    public function show(Mailbox $mailbox, int $uid)
    {
        $message = $this->imap->getMessage($mailbox, $uid);
        if (!$message) abort(404);

        return view('admin.mail.show', compact('mailbox', 'message'));
    }

    // Написать письмо
    public function compose(Mailbox $mailbox, Request $request)
    {
        $replyTo  = $request->get('reply_to');
        $replyUid = $request->get('uid');
        $subject  = $request->get('subject', '');
        $body     = $request->get('body', '');

        return view('admin.mail.compose', compact('mailbox', 'replyTo', 'replyUid', 'subject', 'body'));
    }

    // Отправить письмо
    public function send(Mailbox $mailbox, Request $request)
    {
        $data = $request->validate([
            'to'      => 'required|email',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        // Отправляем через Postfix от имени этого ящика
        config(['mail.from.address' => $mailbox->email]);
        config(['mail.from.name'    => $mailbox->name ?? $mailbox->email]);

        Mail::send([], [], function ($mail) use ($data, $mailbox) {
            $mail->to($data['to'])
                 ->from($mailbox->email, $mailbox->name ?? 'ViewLab')
                 ->subject($data['subject'])
                 ->html($data['body']);
        });

        return redirect()->route('admin.mail.inbox', $mailbox)
            ->with('success', "Письмо отправлено на {$data['to']}!");
    }

    // Удалить письмо
    public function deleteMessage(Mailbox $mailbox, int $uid)
    {
        $this->imap->deleteMessage($mailbox, $uid);
        return redirect()->route('admin.mail.inbox', $mailbox)
            ->with('success', 'Письмо удалено!');
    }
}
