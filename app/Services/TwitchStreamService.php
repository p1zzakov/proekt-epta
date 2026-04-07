<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwitchStreamService
{
    private string $clientId;
    private string $clientSecret;
    private string $apiUrl = 'https://api.twitch.tv/helix';

    public function __construct()
    {
        $this->clientId     = config('bot.twitch_client_id', '');
        $this->clientSecret = config('bot.twitch_client_secret', '');
    }

    // ─────────────────────────────────────────
    // Данные стрима — кешируем на 2 минуты
    // ─────────────────────────────────────────

    public function getStreamInfo(string $channelName): ?array
    {
        $cacheKey = "twitch_stream:{$channelName}";

        return Cache::remember($cacheKey, 120, function () use ($channelName) {
            return $this->fetchStreamInfo($channelName);
        });
    }

    private function fetchStreamInfo(string $channelName): ?array
    {
        $token = $this->getAppToken();
        if (!$token) return null;

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Client-ID'     => $this->clientId,
                    'Authorization' => "Bearer {$token}",
                ])
                ->get("{$this->apiUrl}/streams", [
                    'user_login' => $channelName,
                ]);

            if (!$response->successful()) return null;

            $data = $response->json('data.0');
            if (!$data) return null;

            // Получаем название игры отдельным запросом
            $gameName = null;
            if (!empty($data['game_id'])) {
                $gameName = $this->getGameName($data['game_id'], $token);
            }

            return [
                'title'        => $data['title'] ?? null,
                'game_id'      => $data['game_id'] ?? null,
                'game'         => $gameName,
                'viewer_count' => $data['viewer_count'] ?? 0,
                'language'     => $data['language'] ?? 'ru',
                'is_live'      => true,
            ];

        } catch (\Exception $e) {
            Log::error('TwitchStreamService: ошибка получения стрима', [
                'channel' => $channelName,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function getGameName(string $gameId, string $token): ?string
    {
        $cacheKey = "twitch_game:{$gameId}";

        return Cache::remember($cacheKey, 3600, function () use ($gameId, $token) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'Client-ID'     => $this->clientId,
                        'Authorization' => "Bearer {$token}",
                    ])
                    ->get("{$this->apiUrl}/games", ['id' => $gameId]);

                return $response->json('data.0.name');
            } catch (\Exception) {
                return null;
            }
        });
    }

    // ─────────────────────────────────────────
    // App Access Token (не нужен юзер)
    // ─────────────────────────────────────────

    private function getAppToken(): ?string
    {
        return Cache::remember('twitch_app_token', 3600 * 24, function () {
            try {
                $response = Http::timeout(5)
                    ->post('https://id.twitch.tv/oauth2/token', [
                        'client_id'     => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'grant_type'    => 'client_credentials',
                    ]);

                if (!$response->successful()) {
                    Log::error('TwitchStreamService: не удалось получить app token', [
                        'status' => $response->status(),
                    ]);
                    return null;
                }

                return $response->json('access_token');

            } catch (\Exception $e) {
                Log::error('TwitchStreamService: ошибка получения токена', [
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Сбросить кеш стрима (например после смены игры).
     */
    public function clearCache(string $channelName): void
    {
        Cache::forget("twitch_stream:{$channelName}");
    }
}
