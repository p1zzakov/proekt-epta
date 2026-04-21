<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Models\Account;
use App\Services\AccountPool;
use App\Services\ResponseGenerator;
use App\Services\TwitchChatService;
use App\Services\StreamContextService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RunChatBots implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function __construct(
        private string $channel,
        private int    $botCount    = 3,
        private int    $durationSec = 300,
        private int    $pauseSec    = 15,
        private string $mode        = 'real',
        private bool   $dryRun      = false, // true = только показываем, не отправляем
    ) {}

    public function handle(
        ResponseGenerator    $generator,
        TwitchChatService    $chat,
        StreamContextService $streamContext,
        AccountPool          $pool,
    ): void {
        $cacheKey = "bot_chat_test:{$this->channel}";

        $this->log($cacheKey, "🚀 Тест запущен | канал: {$this->channel} | ботов: {$this->botCount} | режим: {$this->mode} | длительность: {$this->durationSec}сек");

        // Берём персонажей-ботов (без привязки к аккаунту)
        $bots = Bot::where('is_active', true)
            ->inRandomOrder()
            ->limit($this->botCount)
            ->get();

        if ($bots->isEmpty()) {
            $this->log($cacheKey, '❌ Нет активных ботов');
            $this->setStatus($cacheKey, 'finished');
            return;
        }

        // Проверяем есть ли вообще chatbot аккаунты
        $availableChatbots = Account::where('status', 'available')
            ->where('is_active', true)
            ->where('type', 'chatbot')
            ->where('phone_verified', true)
            ->count();

        if ($availableChatbots === 0) {
            $this->log($cacheKey, '❌ Нет доступных chatbot аккаунтов (type=chatbot, phone_verified=true)');
            $this->setStatus($cacheKey, 'finished');
            return;
        }

        $this->log($cacheKey, '✅ Персонажи: ' . $bots->pluck('name')->join(', '));
        $this->log($cacheKey, "💬 Доступно chatbot аккаунтов: {$availableChatbots}");

        // Получаем контекст стрима
        $cat = $streamContext->getStreamCategory($this->channel);
        if ($cat) {
            $this->log($cacheKey, "🎮 Игра: " . ($cat['game'] ?? '—') . " | " . ($cat['title'] ?? ''));
        }

        // Загружаем chatbot аккаунты и распределяем по ботам-персонажам
        $chatbotAccounts = \App\Models\Account::where('status', 'available')
            ->where('is_active', true)
            ->where('type', 'chatbot')
            ->where('phone_verified', true)
            ->inRandomOrder()
            ->get();

        if ($chatbotAccounts->isEmpty()) {
            $this->log($cacheKey, '❌ Нет доступных chatbot аккаунтов');
            $this->setStatus($cacheKey, 'finished');
            return;
        }

        $this->log($cacheKey, "💬 Chatbot аккаунтов: {$chatbotAccounts->count()}");

        // Проверяем запущен ли Whisper для этого канала
        $whisperKey = "stream_speech:{$this->channel}";
        $hasWhisper = false;
        try {
            $hasWhisper = Redis::llen($whisperKey) > 0;
        } catch (\Exception) {}

        if ($hasWhisper) {
            $this->log($cacheKey, "🎙️ Whisper активен — слушаем речь стримера");
        } else {
            $this->log($cacheKey, "⚠️ Whisper не запущен — реагируем только на чат и игру");
        }

        $endTime      = time() + $this->durationSec;
        $botList      = $bots->values();
        $totalBots    = $botList->count();
        $totalAccs    = $chatbotAccounts->count();
        $botIndex     = 0;

        while (time() < $endTime) {
            // Проверяем флаг остановки
            $state = Cache::get($cacheKey, []);
            if (($state['status'] ?? '') === 'stopped') {
                $this->log($cacheKey, '⏹ Остановлен вручную');
                return;
            }

            // Берём персонажа и аккаунт по кругу — каждый следующий бот использует другой аккаунт
            $bot     = $botList[$botIndex % $totalBots];
            $account = $chatbotAccounts[$botIndex % $totalAccs];
            $botIndex++;

            // Получаем историю чата
            $chatHistory = $streamContext->getChatHistory($this->channel, 8);

            if ($this->mode === 'real') {
                $newMessages = $chat->readChatMessages($this->channel, 5);
                $trigger = $this->pickTrigger($newMessages, $chatHistory, $cat);
            } else {
                // Режим "между собой" — реагируем на контекст, не копируем сообщения
                $game  = $cat['game'] ?? 'стрим';
                $title = $cat['title'] ?? '';
                $selfTriggers = [
                    "Что думаешь о стриме?",
                    "Как тебе игра {$game}?",
                    "Что происходит на стриме?",
                    "Оцени то что происходит на стриме",
                ];
                if ($title) {
                    $selfTriggers[] = "Стрим: {$title}. Твои мысли?";
                }
                if (!empty($chatHistory)) {
                    $last = end($chatHistory);
                    $selfTriggers[] = 'Прокомментируй: ' . $last['message'];
                }
                $trigger = $selfTriggers[array_rand($selfTriggers)];
            }

            // Если нет реального контекста — пропускаем итерацию
            if ($trigger === null) {
                $this->log($cacheKey, "⏭️ Нет контекста — пропускаем");
                sleep($this->pauseSec);
                continue;
            }

            $context = [
                'game'            => $cat['game'] ?? null,
                'title'           => $cat['title'] ?? null,
                'chat_history'    => $chatHistory,
                'bot_messages'    => [],
                'streamer_speech' => $this->getStreamerSpeech(),
            ];

            // Генерируем ответ от имени персонажа
            $response = $generator->generate($bot, $trigger, $context);

            if ($response) {
                $response = $this->cleanResponse($response);

                if ($this->dryRun) {
                    // DRY-RUN — только показываем, не отправляем
                    $this->log($cacheKey, "🔍 [DRY] [{$bot->name} / {$account->username}] {$response}");
                } else {
                    $sent = $chat->sendMessage($account, $this->channel, $response);

                    if ($sent) {
                        $this->log($cacheKey, "✅ [{$bot->name} / {$account->username}] {$response}");
                        $streamContext->addChatMessage($this->channel, $account->username, $response);
                    } else {
                        $reason = !$account->phone_verified ? 'нужен телефон' : 'канал заблокировал';
                        $this->log($cacheKey, "❌ [{$bot->name} / {$account->username}] не отправлено — {$reason}");
                    }
                }
            } else {
                $this->log($cacheKey, "⚠️ [{$bot->name}] Ollama не ответила");
            }

            $actualDelay = max(5, $this->pauseSec + rand(-3, 3));
            sleep($actualDelay);
        }

        $this->log($cacheKey, '🏁 Тест завершён');
        $this->setStatus($cacheKey, 'finished');
    }

    /**
     * Обрезаем мусор от модели — китайский, объяснения, скобки
     */
    private function cleanResponse(string $text): string
    {
        $text = trim($text);

        // Убираем префиксы типа [TestBot_toxic]: или [Стример]:
        $text = preg_replace('/^\[.*?\]:\s*/u', '', $text);

        // Если модель вышла за рамки и начала объяснять — берём только первое предложение
        // Признаки: китайские символы, скобки с пояснением, "согласно", "данный"
        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $text) ||
            str_contains($text, 'согласно') ||
            str_contains($text, 'данный') ||
            str_contains($text, 'character') ||
            str_contains($text, 'setting')) {
            // Берём только первое предложение до точки/восклицания/вопроса
            preg_match('/^[^.!?]+[.!?]/u', $text, $m);
            $text = $m[0] ?? mb_substr($text, 0, 60);
        }

        // Обрезаем до 200 символов
        if (mb_strlen($text) > 200) {
            $text = mb_substr($text, 0, 197) . '...';
        }

        return trim($text, '"\'«» ');
    }

    /**
     * Получаем последние фразы стримера из Redis (Whisper)
     */
    private function getStreamerSpeech(): ?string
    {
        try {
            $key  = "stream_speech:{$this->channel}";
            $raw  = Redis::lrange($key, 0, 4); // последние 5 фраз
            if (empty($raw)) return null;

            $speeches = array_map(fn($item) => json_decode($item, true), $raw);
            $speeches = array_filter($speeches, fn($s) => isset($s['text']) && time() - ($s['timestamp'] ?? 0) < 120);

            if (empty($speeches)) return null;

            // Берём самую свежую фразу
            $latest = reset($speeches);
            return $latest['text'] ?? null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Выбираем на что реагировать.
     * Умно выбираем сообщение из чата — вопрос, эмоция, обсуждение.
     * Возвращает null если нет реального контекста.
     */
    private function pickTrigger(array $newMessages, array $chatHistory, ?array $cat): ?string
    {
        $game  = $cat['game'] ?? null;
        $title = $cat['title'] ?? null;

        // Приоритет 1: речь стримера (Whisper)
        $speech = $this->getStreamerSpeech();
        if ($speech && rand(0, 2) === 0) {
            return $speech;
        }

        // Приоритет 2: новые сообщения из чата — выбираем интересное
        $allMessages = array_merge($newMessages, array_slice($chatHistory, -5));
        $interesting = $this->findInterestingMessage($allMessages);
        if ($interesting) {
            return $interesting;
        }

        // Приоритет 3: название стрима
        if ($title && rand(0, 1) === 0) {
            return $title;
        }

        // Приоритет 4: игра
        if ($game) {
            return "играет {$game}";
        }

        return null;
    }

    /**
     * Находим сообщение из чата на которое стоит ответить.
     * Приоритет: вопросы > эмоции > обсуждение > рандом
     */
    private function findInterestingMessage(array $messages): ?string
    {
        if (empty($messages)) return null;

        $questions   = [];
        $emotional   = [];
        $discussions = [];

        foreach ($messages as $msg) {
            $text = $msg['message'] ?? '';
            if (empty($text) || mb_strlen($text) < 2) continue;

            // Вопросы — лучший триггер для ответа
            if (str_contains($text, '?') || preg_match('/^(а |как |что |где |когда |почему |зачем |кто |чем )/ui', $text)) {
                $questions[] = $text;
                continue;
            }

            // Эмоциональные — хороший триггер
            if (preg_match('/ору|лол|кек|лмао|ахах|хаха|ору|топ|кринж|жиза|огонь|красава|нафиг|блин|бля|вау|ого/ui', $text)) {
                $emotional[] = $text;
                continue;
            }

            // Обсуждение — нейтральный триггер
            if (mb_strlen($text) > 5) {
                $discussions[] = $text;
            }
        }

        // Берём с приоритетом
        if (!empty($questions))   return $questions[array_rand($questions)];
        if (!empty($emotional) && rand(0,1))   return $emotional[array_rand($emotional)];
        if (!empty($discussions) && rand(0,1)) return $discussions[array_rand($discussions)];

        return null;
    }

    private function log(string $cacheKey, string $message): void
    {
        $data = Cache::get($cacheKey, ['status' => 'running', 'log' => []]);
        $data['log'][] = ['time' => now()->format('H:i:s'), 'text' => $message];
        if (count($data['log']) > 100) {
            $data['log'] = array_slice($data['log'], -100);
        }
        Cache::put($cacheKey, $data, 3600);
        Log::info("BotChat [{$this->channel}]: {$message}");
    }

    private function setStatus(string $cacheKey, string $status): void
    {
        $data = Cache::get($cacheKey, []);
        $data['status'] = $status;
        Cache::put($cacheKey, $data, 3600);
    }
}