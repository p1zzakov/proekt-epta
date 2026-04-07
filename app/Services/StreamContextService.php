<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StreamContextService
{
    private string $clientId = 'kimne78kx3ncx6brgo4mv6wki5h1ko';

    // Получить категорию стрима через GQL
    public function getStreamCategory(string $channel): ?array
    {
        $cacheKey = "stream_category:{$channel}";

        return Cache::remember($cacheKey, 60, function() use ($channel) {
            try {
                $response = Http::withHeaders([
                    'Client-ID'    => $this->clientId,
                    'Content-Type' => 'application/json',
                ])->post('https://gql.twitch.tv/gql', [[
                    'operationName' => 'StreamMetadata',
                    'query' => 'query StreamMetadata($channelLogin: String!) {
                        user(login: $channelLogin) {
                            lastBroadcast {
                                game { name }
                                title
                            }
                            stream {
                                game { name }
                                title
                            }
                        }
                    }',
                    'variables' => ['channelLogin' => $channel]
                ]]);

                $user = $response->json('0.data.user');
                if (!$user) return null;

                $stream = $user['stream'] ?? $user['lastBroadcast'] ?? null;
                if (!$stream) return null;

                return [
                    'game'  => $stream['game']['name'] ?? null,
                    'title' => $stream['title'] ?? null,
                ];
            } catch (\Exception $e) {
                Log::error('StreamContext: failed to get category', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    // Читаем последние сообщения чата из IRC (хранятся в Cache)
    public function getChatHistory(string $channel, int $limit = 10): array
    {
        $key = "chat_history:{$channel}";
        $history = Cache::get($key, []);
        return array_slice($history, -$limit);
    }

    // Добавить сообщение в историю чата
    public function addChatMessage(string $channel, string $author, string $message): void
    {
        $key     = "chat_history:{$channel}";
        $history = Cache::get($key, []);

        $history[] = [
            'author'    => $author,
            'message'   => $message,
            'timestamp' => time(),
        ];

        // Храним последние 30 сообщений
        if (count($history) > 30) {
            $history = array_slice($history, -30);
        }

        Cache::put($key, $history, 3600);
    }

    // Построить контекст для промпта — категория + чат
    public function buildContextString(string $channel, string $botName): string
    {
        $lines = [];

        // Категория стрима
        $cat = $this->getStreamCategory($channel);
        if ($cat) {
            if ($cat['game'])  $lines[] = "Игра/категория: {$cat['game']}";
            if ($cat['title']) $lines[] = "Название стрима: {$cat['title']}";
        }

        // История чата
        $history = $this->getChatHistory($channel, 8);
        if (!empty($history)) {
            $lines[] = "\nПоследние сообщения в чате:";
            foreach ($history as $msg) {
                // Не показываем боту его же сообщения как чужие
                $prefix = $msg['author'] === $botName ? '[ты написал]' : "[{$msg['author']}]";
                $lines[] = "{$prefix}: {$msg['message']}";
            }
        }

        return implode("\n", $lines);
    }
}
