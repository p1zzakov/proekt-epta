<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TwitchChatService
{
    private string $ircHost         = '34.212.92.60';
    private int    $ircPort         = 6667;
    private string $clientId        = 'kimne78kx3ncx6brgo4mv6wki5h1ko';
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

            $joined        = false;
            $roomstate     = false;
            $userstateCount = 0;
            $sent          = false;
            $start         = time();

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
            if (str_contains($result, 'msg_slowmode')) return false;

            $account->increment('messages_sent');
            $account->increment('messages_today');
            $account->last_used_at = now();
            $account->save();

            return true;

        } finally {
            fclose($socket);
        }
    }

    public function checkChatAccess(Account $account, string $channel): string
    {
        $socket = @fsockopen($this->ircHost, $this->ircPort, $errno, $errstr, 10);
        if (!$socket) return 'connection_failed';

        try {
            stream_set_timeout($socket, 3);

            fwrite($socket, "PASS oauth:{$account->access_token}\r\n");
            fwrite($socket, "NICK {$account->username}\r\n");
            fwrite($socket, "CAP REQ :twitch.tv/commands twitch.tv/tags\r\n");
            fwrite($socket, "JOIN #{$channel}\r\n");

            $sent           = false;
            $userstateCount = 0;
            $result         = null;
            $start          = time();

            while ($result === null && time() - $start < 15) {
                $line = fgets($socket);
                if (!$line) continue;
                $line = trim($line);
                if (str_starts_with($line, 'PING')) { fwrite($socket, "PONG :tmi.twitch.tv\r\n"); continue; }

                if (str_contains($line, 'USERSTATE')) $userstateCount++;

                if (str_contains($line, 'ROOMSTATE') && !$sent) {
                    sleep(1);
                    fwrite($socket, "PRIVMSG #{$channel} :pog\r\n");
                    $sent = true;
                    continue;
                }

                if (!$sent) continue;

                if (str_contains($line, 'msg_requires_verified_phone_number')) {
                    $account->phone_verified = false;
                    $account->save();
                    $result = 'needs_phone';
                } elseif (str_contains($line, 'msg_followersonly')) {
                    $result = 'needs_follow';
                } elseif (str_contains($line, 'USERSTATE') && $userstateCount >= 2) {
                    $account->phone_verified = true;
                    $account->save();
                    $result = 'ok';
                }
            }

            return $result ?? 'unknown';
        } finally {
            fclose($socket);
        }
    }

    public function bulkCheckAccounts(string $channel, int $limit = 50): array
    {
        $accounts = Account::where('status', 'available')
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        $results = ['ok' => 0, 'needs_phone' => 0, 'needs_follow' => 0, 'unknown' => 0];

        foreach ($accounts as $account) {
            $status = $this->checkChatAccess($account, $channel);
            $results[$status] = ($results[$status] ?? 0) + 1;
            usleep(300000);
        }

        return $results;
    }

    public function followChannel(Account $account, string $channel, string $channelId): bool
    {
        try {
            $response = Http::timeout(15)->post("{$this->followServiceUrl}/follow", [
                'token'      => $account->access_token,
                'channel_id' => $channelId,
            ]);
            $data = $response->json();
            if ($data['success'] ?? false) {
                $account->markFollowed($channel, $channelId);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Follow failed', ['error' => $e->getMessage()]);
            return false;
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

    /**
     * Читаем живой чат канала через IRC — до $seconds секунд
     * Возвращаем массив сообщений [{author, message, timestamp}]
     */
    public function readChatMessages(string $channel, int $seconds = 8): array
    {
        $streamContext = app(StreamContextService::class);
        $messages = [];

        $socket = @fsockopen($this->ircHost, $this->ircPort, $errno, $errstr, 10);
        if (!$socket) return [];

        try {
            stream_set_timeout($socket, 2);

            // Подключаемся анонимно (justinfan)
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

                // Парсим PRIVMSG: @tags :user!user@user.tmi.twitch.tv PRIVMSG #channel :message
                if (preg_match('/:(\w+)!\w+@\w+\.tmi\.twitch\.tv PRIVMSG #\w+ :(.+)/', $line, $m)) {
                    $author  = $m[1];
                    $message = trim($m[2]);

                    $msg = ['author' => $author, 'message' => $message, 'timestamp' => time()];
                    $messages[] = $msg;

                    // Сохраняем в историю
                    $streamContext->addChatMessage($channel, $author, $message);
                }
            }
        } finally {
            fclose($socket);
        }

        return $messages;
    }

    /**
     * Получить контекст стрима — категорию + историю чата
     */
    public function getStreamContext(string $channel, string $botName): array
    {
        $streamContext = app(StreamContextService::class);

        // Получаем категорию
        $cat = $streamContext->getStreamCategory($channel);

        // История чата
        $history = $streamContext->getChatHistory($channel, 8);

        return [
            'game'         => $cat['game'] ?? null,
            'title'        => $cat['title'] ?? null,
            'chat_history' => $history,
        ];
    }
}
