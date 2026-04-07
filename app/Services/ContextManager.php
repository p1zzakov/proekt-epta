<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ContextManager
{
    // Максимум сообщений в памяти на канал
    private int $maxMessages = 20;

    // TTL контекста — 2 часа (стрим кончился = память чистится)
    private int $ttl = 7200;

    // ─────────────────────────────────────────
    // Добавить сообщение стримера
    // ─────────────────────────────────────────

    public function addStreamerMessage(string $channel, string $text): void
    {
        $this->push($channel, [
            'role' => 'streamer',
            'name' => 'Streamer',
            'text' => $text,
            'at'   => now()->timestamp,
        ]);
    }

    // ─────────────────────────────────────────
    // Добавить ответ бота
    // ─────────────────────────────────────────

    public function addBotMessage(string $channel, string $botName, string $text): void
    {
        $this->push($channel, [
            'role' => 'bot',
            'name' => $botName,
            'text' => $text,
            'at'   => now()->timestamp,
        ]);
    }

    // ─────────────────────────────────────────
    // Получить последние N сообщений
    // ─────────────────────────────────────────

    public function getContext(string $channel, int $limit = 8): array
    {
        try {
            $key  = $this->key($channel);
            $raw  = Redis::lrange($key, 0, $limit - 1);

            if (empty($raw)) return [];

            return array_map(fn($item) => json_decode($item, true), $raw);

        } catch (\Exception $e) {
            Log::error('ContextManager: ошибка чтения', [
                'channel' => $channel,
                'error'   => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ─────────────────────────────────────────
    // Очистить контекст канала
    // ─────────────────────────────────────────

    public function clear(string $channel): void
    {
        try {
            Redis::del($this->key($channel));
            Log::info('ContextManager: контекст очищен', ['channel' => $channel]);
        } catch (\Exception $e) {
            Log::error('ContextManager: ошибка очистки', ['error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────
    // Статистика
    // ─────────────────────────────────────────

    public function count(string $channel): int
    {
        try {
            return (int) Redis::llen($this->key($channel));
        } catch (\Exception) {
            return 0;
        }
    }

    // ─────────────────────────────────────────
    // Внутренние методы
    // ─────────────────────────────────────────

    private function push(string $channel, array $message): void
    {
        try {
            $key = $this->key($channel);

            // Добавляем в начало списка (новые — первые)
            Redis::lpush($key, json_encode($message, JSON_UNESCAPED_UNICODE));

            // Обрезаем до maxMessages
            Redis::ltrim($key, 0, $this->maxMessages - 1);

            // Обновляем TTL
            Redis::expire($key, $this->ttl);

        } catch (\Exception $e) {
            Log::error('ContextManager: ошибка записи', [
                'channel' => $channel,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function key(string $channel): string
    {
        return 'context:stream:' . mb_strtolower($channel);
    }
}
