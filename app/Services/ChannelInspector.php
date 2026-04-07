<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ChannelSettings;
use Illuminate\Support\Facades\Log;

class ChannelInspector
{
    private string $ircHost = '34.212.92.60';
    private int    $ircPort = 6667;

    /**
     * Проверяем настройки канала через IRC
     * Кешируем на 10 минут
     */
    public function inspect(string $channel, bool $forceRefresh = false): ChannelSettings
    {
        $settings = ChannelSettings::where('channel', $channel)->first();

        // Обновляем если старше 10 минут или принудительно
        if ($forceRefresh || !$settings || $settings->checked_at?->lt(now()->subMinutes(10))) {
            $settings = $this->fetchFromIrc($channel, $settings);
        }

        return $settings;
    }

    /**
     * Проверяем через IRC — подключаемся и читаем ROOMSTATE
     */
    private function fetchFromIrc(string $channel, ?ChannelSettings $existing): ChannelSettings
    {
        // Берём любой доступный аккаунт для проверки
        $account = Account::where('status', 'available')->where('is_active', true)->inRandomOrder()->first();
        if (!$account) {
            return $existing ?? new ChannelSettings(['channel' => $channel]);
        }

        $socket = @fsockopen($this->ircHost, $this->ircPort, $errno, $errstr, 10);
        if (!$socket) {
            Log::error('ChannelInspector: IRC connection failed');
            return $existing ?? new ChannelSettings(['channel' => $channel]);
        }

        try {
            stream_set_timeout($socket, 5);

            fwrite($socket, "PASS oauth:{$account->access_token}\r\n");
            fwrite($socket, "NICK {$account->username}\r\n");
            fwrite($socket, "CAP REQ :twitch.tv/commands twitch.tv/tags\r\n");
            fwrite($socket, "JOIN #{$channel}\r\n");

            $data  = [
                'followers_only'         => false,
                'followers_only_minutes' => 0,
                'subs_only'              => false,
                'slow_mode'              => false,
                'slow_seconds'           => 0,
                'requires_phone'         => false,
                'channel_id'             => null,
            ];

            $sent  = false;
            $start = time();

            while (time() - $start < 10) {
                $line = fgets($socket);
                if (!$line) continue;
                $line = trim($line);

                if (str_starts_with($line, 'PING')) {
                    fwrite($socket, "PONG :tmi.twitch.tv\r\n");
                    continue;
                }

                // Парсим ROOMSTATE
                if (str_contains($line, 'ROOMSTATE')) {
                    preg_match('/room-id=(\d+)/', $line, $m);
                    $data['channel_id'] = $m[1] ?? null;

                    preg_match('/followers-only=(-?\d+)/', $line, $m);
                    $fo = (int)($m[1] ?? -1);
                    $data['followers_only']         = $fo >= 0;
                    $data['followers_only_minutes'] = max(0, $fo);

                    preg_match('/subs-only=(\d+)/', $line, $m);
                    $data['subs_only'] = ($m[1] ?? 0) == 1;

                    preg_match('/slow=(\d+)/', $line, $m);
                    $slow = (int)($m[1] ?? 0);
                    $data['slow_mode']    = $slow > 0;
                    $data['slow_seconds'] = $slow;
                }

                // Проверяем требование телефона
                if (str_contains($line, 'ROOMSTATE') && !$sent) {
                    fwrite($socket, "PRIVMSG #{$channel} :.\r\n");
                    $sent = true;
                }

                if ($sent && str_contains($line, 'msg_requires_verified_phone_number')) {
                    $data['requires_phone'] = true;
                    break;
                }

                if ($sent && str_contains($line, 'USERSTATE')) {
                    // Успешно — телефон не нужен
                    break;
                }
            }

            // Сохраняем в БД
            $settings = ChannelSettings::updateOrCreate(
                ['channel' => $channel],
                array_merge($data, ['checked_at' => now()])
            );

            Log::info('ChannelInspector: checked', [
                'channel'        => $channel,
                'followers_only' => $data['followers_only'],
                'requires_phone' => $data['requires_phone'],
                'subs_only'      => $data['subs_only'],
            ]);

            return $settings;

        } finally {
            fclose($socket);
        }
    }

    /**
     * Получить аккаунты подходящие для канала
     */
    public function getSuitableAccounts(string $channel, string $botMode, int $count): array
    {
        $settings = $this->inspect($channel);

        $query = Account::where('status', 'available')->where('is_active', true);

        // Если нужен чат И канал требует телефон — только верифицированные
        if ($botMode !== 'viewers' && $settings->requires_phone) {
            $query->where('phone_verified', true);
        }

        return $query->inRandomOrder()->limit($count)->get()->all();
    }
}
