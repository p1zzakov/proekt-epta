<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\ResponseGenerator;
use App\Services\TwitchChatService;
use App\Services\StreamContextService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BotChatRun extends Command
{
    protected $signature = 'bot-chat:run
        {channel        : Twitch канал}
        {bot_count=3    : Кол-во ботов}
        {duration=300   : Длительность в секундах}
        {delay=15       : Задержка между сообщениями (сек)}
        {mode=real      : Режим: real (реальный чат) | self (между собой)}';

    protected $description = 'Запустить тест общения ботов в чате';

    public function handle(
        ResponseGenerator    $generator,
        TwitchChatService    $chat,
        StreamContextService $streamContext,
    ): void {
        $channel   = $this->argument('channel');
        $botCount  = (int) $this->argument('bot_count');
        $duration  = (int) $this->argument('duration');
        $delay     = (int) $this->argument('delay');
        $mode      = $this->argument('mode');
        $cacheKey  = "bot_chat_test:{$channel}";

        $this->log($cacheKey, "🚀 Тест запущен | канал: {$channel} | ботов: {$botCount} | режим: {$mode} | длительность: {$duration}сек");

        // Берём ботов с аккаунтами
        $bots = Bot::with('account')
            ->where('is_active', true)
            ->whereNotNull('account_id')
            ->inRandomOrder()
            ->limit($botCount)
            ->get()
            ->filter(fn($b) => $b->account);

        if ($bots->isEmpty()) {
            $this->log($cacheKey, '❌ Нет активных ботов с аккаунтами');
            $this->setStatus($cacheKey, 'stopped');
            return;
        }

        $this->log($cacheKey, '✅ Боты: ' . $bots->pluck('name')->join(', '));

        // Получаем контекст стрима
        $cat = $streamContext->getStreamCategory($channel);
        if ($cat) {
            $this->log($cacheKey, "🎮 Игра: " . ($cat['game'] ?? '—') . " | " . ($cat['title'] ?? ''));
        }

        $endTime  = time() + $duration;
        $botList  = $bots->values();
        $total    = $botList->count();
        $botIndex = 0;

        while (time() < $endTime) {
            // Проверяем флаг остановки
            $state = Cache::get($cacheKey, []);
            if (($state['status'] ?? '') === 'stopped') {
                $this->log($cacheKey, '⏹ Остановлен вручную');
                break;
            }

            $bot = $botList[$botIndex % $total];
            $botIndex++;

            // Получаем контекст
            $chatHistory = $streamContext->getChatHistory($channel, 8);

            if ($mode === 'real') {
                // Читаем реальный чат 5 сек
                $newMessages = $chat->readChatMessages($channel, 5);
                $trigger = $this->pickTrigger($newMessages, $chatHistory, $cat);
            } else {
                // Режим "между собой" — реагируем на последнее сообщение из истории
                $trigger = !empty($chatHistory)
                    ? "В чате написали: \"{$chatHistory[array_key_last($chatHistory)]['message']}\""
                    : "Стример играет в " . ($cat['game'] ?? 'игру');
            }

            $context = [
                'game'         => $cat['game'] ?? null,
                'title'        => $cat['title'] ?? null,
                'chat_history' => $chatHistory,
                'bot_messages' => [],
            ];

            // Генерируем ответ через Ollama
            $response = $generator->generate($bot, $trigger, $context);

            if ($response) {
                $sent = $chat->sendMessage($bot->account, $channel, $response);
                $icon = $sent ? '✅' : '❌';
                $this->log($cacheKey, "{$icon} [{$bot->name}] {$response}");

                if ($sent) {
                    $streamContext->addChatMessage($channel, $bot->account->username, $response);
                }
            } else {
                $this->log($cacheKey, "⚠️ [{$bot->name}] Ollama не ответила");
            }

            // Задержка между сообщениями с небольшим рандомом
            $actualDelay = $delay + rand(-3, 5);
            $actualDelay = max(5, $actualDelay);
            sleep($actualDelay);
        }

        $this->log($cacheKey, '🏁 Тест завершён');
        $this->setStatus($cacheKey, 'finished');
    }

    private function pickTrigger(array $newMessages, array $chatHistory, ?array $cat): string
    {
        $game  = $cat['game'] ?? 'стрим';
        $title = $cat['title'] ?? '';

        if (!empty($newMessages) && rand(0, 2) === 0) {
            $msg = $newMessages[array_rand($newMessages)];
            return "В чате написали: \"{$msg['message']}\"";
        }

        if (!empty($chatHistory) && rand(0, 1) === 0) {
            $last = end($chatHistory);
            return "В чате: \"{$last['message']}\"";
        }

        $triggers = [
            "Стример играет в {$game}",
            "Продолжается стрим по {$game}",
            "{$game} — что думаешь о происходящем?",
        ];

        if ($title) $triggers[] = "Стрим: {$title}";

        return $triggers[array_rand($triggers)];
    }

    private function log(string $cacheKey, string $message): void
    {
        $data = Cache::get($cacheKey, ['status' => 'running', 'log' => []]);
        $data['log'][] = [
            'time' => now()->format('H:i:s'),
            'text' => $message,
        ];
        // Держим последние 100 строк
        if (count($data['log']) > 100) {
            $data['log'] = array_slice($data['log'], -100);
        }
        Cache::put($cacheKey, $data, 3600);
        $this->info($message);
    }

    private function setStatus(string $cacheKey, string $status): void
    {
        $data = Cache::get($cacheKey, []);
        $data['status'] = $status;
        Cache::put($cacheKey, $data, 3600);
    }
}
