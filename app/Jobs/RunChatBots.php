<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Services\ResponseGenerator;
use App\Services\TwitchChatService;
use App\Services\StreamContextService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunChatBots implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(
        private string $channel,
        private int    $botCount   = 5,
        private int    $durationSec = 120,
    ) {}

    public function handle(
        ResponseGenerator  $generator,
        TwitchChatService  $chat,
        StreamContextService $streamContext,
    ): void {
        Log::info("RunChatBots started", ['channel' => $this->channel, 'bots' => $this->botCount]);

        // Получаем ботов
        $bots = Bot::where('is_active', true)
            ->with('account')
            ->inRandomOrder()
            ->limit($this->botCount)
            ->get();

        if ($bots->isEmpty()) {
            Log::warning('RunChatBots: no active bots');
            return;
        }

        // Получаем категорию стрима сразу
        $cat = $streamContext->getStreamCategory($this->channel);
        Log::info('Stream category', ['cat' => $cat]);

        $startTime  = time();
        $endTime    = $startTime + $this->durationSec;
        $botIndex   = 0;
        $botList    = $bots->values();
        $totalBots  = $botList->count();

        while (time() < $endTime) {
            // Читаем чат 8 секунд
            $newMessages = $chat->readChatMessages($this->channel, 8);

            if (!empty($newMessages)) {
                Log::info('Chat messages received', ['count' => count($newMessages)]);
            }

            // Берём случайного бота по кругу
            $bot     = $botList[$botIndex % $totalBots];
            $botIndex++;

            if (!$bot->account) continue;

            // Строим контекст
            $chatHistory = $streamContext->getChatHistory($this->channel, 8);

            $context = [
                'game'         => $cat['game'] ?? null,
                'title'        => $cat['title'] ?? null,
                'chat_history' => $chatHistory,
                'bot_messages' => [],
            ];

            // Определяем на что реагировать
            $streamerText = $this->pickTrigger($newMessages, $chatHistory, $cat);

            // Генерируем ответ
            $response = $generator->generate($bot, $streamerText, $context);

            if ($response) {
                // Случайная задержка перед отправкой (имитация живого чела)
                $delay = rand(2, 12);
                sleep($delay);

                $sent = $chat->sendMessage($bot->account, $this->channel, $response);

                if ($sent) {
                    // Сохраняем в историю чата
                    $streamContext->addChatMessage($this->channel, $bot->account->username, $response);
                    Log::info('Bot sent message', [
                        'bot'     => $bot->name,
                        'channel' => $this->channel,
                        'msg'     => $response,
                    ]);
                }
            }

            // Пауза между ботами — рандомная чтобы не спамить
            $pause = rand(5, 20);
            sleep($pause);
        }

        Log::info("RunChatBots finished", ['channel' => $this->channel]);
    }

    /**
     * Выбираем на что реагировать:
     * - на последнее сообщение стримера (из тайтла/игры)
     * - или на сообщение в чате
     * - или просто комментируем игру
     */
    private function pickTrigger(array $newMessages, array $chatHistory, ?array $cat): string
    {
        $game  = $cat['game'] ?? 'стрим';
        $title = $cat['title'] ?? '';

        // С вероятностью 30% отвечаем на живое сообщение чата
        if (!empty($newMessages) && rand(0, 2) === 0) {
            $msg = $newMessages[array_rand($newMessages)];
            return "В чате написали: \"{$msg['message']}\"";
        }

        // С вероятностью 40% комментируем последнее сообщение в истории
        if (!empty($chatHistory) && rand(0, 1) === 0) {
            $last = end($chatHistory);
            return "В чате: \"{$last['message']}\"";
        }

        // Иначе — общий контекст игры
        $triggers = [
            "Стример играет в {$game}",
            "Продолжается стрим по {$game}",
            "{$game} — что думаешь о происходящем?",
            "Стрим идёт, игра {$game}",
        ];

        if ($title) {
            $triggers[] = "Стрим: {$title}";
        }

        return $triggers[array_rand($triggers)];
    }
}
