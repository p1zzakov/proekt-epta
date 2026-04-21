<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TwitchChatService
{
    private string $ircHost          = '34.212.92.60';
    private int    $ircPort          = 6667;
    private string $clientId         = 'kimne78kx3ncx6brgo4mv6wki5h1ko';
    private string $followServiceUrl = 'http://host.docker.internal:3001';

    public function sendMessage(Account $account, string $channel, string $message): bool
    {
        $socket = @fsockopen($this->ircHost, $this->ircPort, $errno, $errstr, 10);
        if (!$socket) {
            Log::error('IRC: connection failed', ['error' => $errstr]);
            return false;
        }

        try {
            stream_set_timeout($socket, 5);

            fwrite($socket, "PASS oauth:{$account->access_token}\r\n");
            fwrite($socket, "NICK {$account->username}\r\n");
            fwrite($socket, "CAP REQ :twitch.tv/commands twitch.tv/tags\r\n");
            fwrite($socket, "JOIN #{$channel}\r\n");

            $joined    = false;
            $roomstate = false;
            $start     = time();

            while (time() - $start < 10) {
                $line = fgets($socket);
                if (!$line) continue;
                $line = trim($line);
                if (str_starts_with($line, 'PING')) { fwrite($socket, "PONG :tmi.twitch.tv\r\n"); continue; }
                if (str_contains($line, 'ROOMSTATE')) $roomstate = true;
                if (str_contains($line, 'End of /NAMES')) $joined = true;
                if ($joined && $roomstate) break;
            }

            if (!$joined) return false;

            fwrite($socket, "PRIVMSG #{$channel} :{$message}\r\n");

            $result = '';
            $start  = time();
            while (time() - $start < 4) {
                $line = fgets($socket);
                if ($line) {
                    $result .= $line;
                    if (str_starts_with($line, 'PING')) fwrite($socket, "PONG :tmi.twitch.tv\r\n");
                }
            }

            if (str_contains($result, 'msg_requires_verified_phone_number')) {
                $account->phone_verified = false;
                $account->save();
                return false;
            }

            if (str_contains($result, 'msg_followersonly')) return false;
            if (str_contains($result, 'msg_slowmode'))     return false;

            $account->increment('messages_sent');
            $account->increment('messages_today');
            $account->last_used_at = now();
            $account->save();

            return true;

        } finally {
            fclose($socket);
        }
    }

    /**
     * Проверяем доступ к чату БЕЗ отправки сообщений.
     *
     * Порядок событий от Twitch при JOIN:
     *   1. ROOMSTATE — ограничения канала (followers-only, phone и т.д.)
     *   2. USERSTATE — статус аккаунта (приходит только если может писать)
     *
     * Ждём сначала ROOMSTATE, потом делаем вывод.
     * followers-only=-1 → выключен
     * followers-only=0+ → включён (0 минут и более)
     */
    public function checkChatAccess(Account $account, string $channel): string
    {
        $socket = @fsockopen($this->ircHost, $this->ircPort, $errno, $errstr, 10);
        if (!$socket) return 'connection_failed';

        try {
            stream_set_timeout($socket, 5);

            fwrite($socket, "PASS oauth:{$account->access_token}\r\n");
            fwrite($socket, "NICK {$account->username}\r\n");
            fwrite($socket, "CAP REQ :twitch.tv/commands twitch.tv/tags\r\n");
            fwrite($socket, "JOIN #{$channel}\r\n");

            $result         = null;
            $roomstateSeen  = false;
            $roomstateClean = false;
            $start          = time();

            while ($result === null && time() - $start < 12) {
                $line = fgets($socket);
                if (!$line) {
                    // fgets вернул false/empty — таймаут сокета сработал
                    // Если ROOMSTATE уже пришёл чистый — аккаунт может писать
                    if ($roomstateClean) {
                        $account->phone_verified = true;
                        $account->save();
                        $result = 'ok';
                    }
                    continue;
                }
                $line = trim($line);

                if (str_starts_with($line, 'PING')) {
                    fwrite($socket, "PONG :tmi.twitch.tv\r\n");
                    continue;
                }

                if (str_contains($line, 'Login authentication failed')) {
                    $result = 'invalid_token';
                    break;
                }

                // ROOMSTATE — ограничения канала
                if (str_contains($line, 'ROOMSTATE')) {
                    $roomstateSeen = true;

                    if (preg_match('/requires-verified-phone-number=1/', $line)) {
                        $account->phone_verified = false;
                        $account->save();
                        $result = 'needs_phone';
                        break;
                    }

                    // followers-only=-1 выключен, 0+ включён
                    if (preg_match('/followers-only=(-?\d+)/', $line, $m) && (int)$m[1] >= 0) {
                        $result = 'needs_follow';
                        break;
                    }

                    // ROOMSTATE без ограничений — канал открытый
                    $roomstateClean = true;
                }

                // USERSTATE приходит только когда аккаунт может писать
                if (str_contains($line, 'USERSTATE') && $roomstateSeen) {
                    $account->phone_verified = true;
                    $account->save();
                    $result = 'ok';
                    break;
                }
            }

            // Fallback: ROOMSTATE был чистый но USERSTATE так и не пришёл
            if ($result === null && $roomstateClean) {
                $account->phone_verified = true;
                $account->save();
                $result = 'ok';
            }

            return $result ?? 'unknown';

        } finally {
            fclose($socket);
        }
    }

    public function bulkCheckAccounts(string $channel, int $limit = 50): array
    {
        // Проверяем только чатботов (с верифицированным телефоном)
        $accounts = Account::where('status', 'available')
            ->where('is_active', true)
            ->where('type', 'chatbot')
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        $results = ['ok' => 0, 'needs_phone' => 0, 'needs_follow' => 0, 'unknown' => 0, 'invalid_token' => 0];

        foreach ($accounts as $account) {
            $status = $this->checkChatAccess($account, $channel);
            $results[$status] = ($results[$status] ?? 0) + 1;
            usleep(300000);
        }

        return $results;
    }

    /**
     * Проверяем верификацию телефона через Twitch GQL API.
     * phoneNumber != null → телефон есть → chatbot
     * phoneNumber == null → нет телефона → viewer
     *
     * Возвращает: ok | needs_phone | invalid_token | error
     */
    public function checkPhoneVerified(Account $account, string $channel = ''): string
    {
        try {
            $resp = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders([
                'Authorization' => "OAuth {$account->access_token}",
                'Client-Id'     => $this->clientId,
                'Content-Type'  => 'application/json',
            ])->post('https://gql.twitch.tv/gql', [[
                'query' => '{ currentUser { login phoneNumber } }'
            ]]);

            $data = $resp->json()[0] ?? null;

            // Невалидный токен
            if (!empty($data['errors'])) {
                $msg = $data['errors'][0]['message'] ?? '';
                if (str_contains(strtolower($msg), 'auth') || str_contains(strtolower($msg), 'token')) {
                    return 'invalid_token';
                }
                return 'error';
            }

            $currentUser = $data['data']['currentUser'] ?? null;
            if (!$currentUser) return 'invalid_token';

            $hasPhone = !empty($currentUser['phoneNumber']);

            $account->phone_verified = $hasPhone;
            $account->type           = $hasPhone ? 'chatbot' : 'viewer';
            $account->save();

            return $hasPhone ? 'ok' : 'needs_phone';

        } catch (\Exception $e) {
            Log::error('checkPhoneVerified error', ['account' => $account->username, 'error' => $e->getMessage()]);
            return 'error';
        }
    }

    public function followChannel(Account $account, string $channel, string $channelId): array
    {
        try {
            $response = Http::timeout(60)->post("{$this->followServiceUrl}/follow", [
                'token'      => $account->access_token,
                'channel_id' => $channelId,
            ]);
            $data = $response->json();
            if ($data['success'] ?? false) {
                $account->markFollowed($channel, $channelId);
                return ['ok' => true];
            }
            $error = $data['error'] ?? 'unknown error';
            Log::error('Follow failed', ['account' => $account->username, 'error' => $error]);
            return ['ok' => false, 'error' => $error];
        } catch (\Exception $e) {
            Log::error('Follow exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function getChannelId(string $channel): ?string
    {
        return Cache::remember("channel_id:{$channel}", 3600, function () use ($channel) {
            try {
                $resp = Http::withHeaders([
                    'Client-Id'    => $this->clientId,
                    'Content-Type' => 'application/json',
                ])->post('https://gql.twitch.tv/gql', [[
                    'query'     => 'query($login:String!){user(login:$login){id}}',
                    'variables' => ['login' => $channel],
                ]]);
                return $resp->json()[0]['data']['user']['id'] ?? null;
            } catch (\Exception) {
                return null;
            }
        });
    }

    public function validateToken(Account $account): bool
    {
        try {
            $resp = Http::timeout(10)->withHeaders([
                'Authorization' => "OAuth {$account->access_token}",
                'Client-Id'     => $this->clientId,
                'Content-Type'  => 'application/json',
            ])->post('https://gql.twitch.tv/gql', [
                ['query' => '{ currentUser { id login displayName } }'],
            ]);

            $user = $resp->json()[0]['data']['currentUser'] ?? null;
            if (!$user) return false;

            $account->twitch_id = $user['id'];
            $account->username  = $user['login'];
            $account->status    = 'available';
            $account->is_active = true;
            $account->save();

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function markInvalid(Account $account): void
    {
        $account->status    = 'invalid';
        $account->is_active = false;
        $account->save();
    }

    public function readChatMessages(string $channel, int $seconds = 8): array
    {
        $streamContext = app(StreamContextService::class);
        $messages      = [];

        $socket = @fsockopen($this->ircHost, $this->ircPort, $errno, $errstr, 10);
        if (!$socket) return [];

        try {
            stream_set_timeout($socket, 2);

            $anonNick = 'justinfan' . rand(10000, 99999);
            fwrite($socket, "PASS SCHMOOPIIE\r\n");
            fwrite($socket, "NICK {$anonNick}\r\n");
            fwrite($socket, "CAP REQ :twitch.tv/tags twitch.tv/commands\r\n");
            fwrite($socket, "JOIN #{$channel}\r\n");

            $start = time();
            while (time() - $start < $seconds) {
                $line = fgets($socket);
                if (!$line) continue;
                $line = trim($line);

                if (str_starts_with($line, 'PING')) {
                    fwrite($socket, "PONG :tmi.twitch.tv\r\n");
                    continue;
                }

                if (preg_match('/:(\w+)!\w+@\w+\.tmi\.twitch\.tv PRIVMSG #\w+ :(.+)/', $line, $m)) {
                    $author  = $m[1];
                    $message = trim($m[2]);
                    $messages[] = ['author' => $author, 'message' => $message, 'timestamp' => time()];
                    $streamContext->addChatMessage($channel, $author, $message);
                }
            }
        } finally {
            fclose($socket);
        }

        return $messages;
    }

    public function getStreamContext(string $channel, string $botName): array
    {
        $streamContext = app(StreamContextService::class);
        $cat           = $streamContext->getStreamCategory($channel);
        $history       = $streamContext->getChatHistory($channel, 8);

        return [
            'game'         => $cat['game'] ?? null,
            'title'        => $cat['title'] ?? null,
            'chat_history' => $history,
        ];
    }
}