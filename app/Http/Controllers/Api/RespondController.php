<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BotSelector;
use App\Services\ContextManager;
use App\Services\ResponseGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RespondController extends Controller
{
    public function __construct(
        private BotSelector     $selector,
        private ResponseGenerator $generator,
        private ContextManager  $context,
    ) {}

    /**
     * POST /api/respond
     *
     * {
     *   "channel":      "streamer_login",   — обязательно
     *   "text":         "ребят норм билд?", — обязательно
     *   "game":         "Dota 2",           — опционально
     *   "stream_title": "Рейтинг гринд"     — опционально
     * }
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel'      => 'required|string|max:64',
            'text'         => 'required|string|max:500',
            'game'         => 'nullable|string|max:128',
            'stream_title' => 'nullable|string|max:256',
        ]);

        $channel      = mb_strtolower($data['channel']);
        $streamerText = $data['text'];

        // 1. Сохраняем фразу стримера в контекст
        $this->context->addStreamerMessage($channel, $streamerText);

        // 2. Обогащаем текст контекстом стрима
        $enrichedText = $this->enrich(
            $streamerText,
            $data['game'] ?? null,
            $data['stream_title'] ?? null,
        );

        // 3. Выбираем бота
        $bot = $this->selector->select($enrichedText);

        if (!$bot) {
            return response()->json([
                'responded' => false,
                'reason'    => 'no_eligible_bot',
                'message'   => 'Все боты на кулдауне или не прошли verbosity',
            ]);
        }

        // 4. Берём последние сообщения из контекста (кроме только что добавленного)
        $history = $this->context->getContext($channel, 8);

        // 5. Генерируем ответ
        $response = $this->generator->generate($bot, $enrichedText, $history);

        if (!$response) {
            return response()->json([
                'responded' => false,
                'reason'    => 'ollama_failed',
                'message'   => 'Ollama не ответила',
            ], 503);
        }

        // 6. Сохраняем ответ бота в контекст
        $this->context->addBotMessage($channel, $bot->name, $response);

        // 7. Кулдаун на бота
        $this->selector->applyCooldown($bot);

        return response()->json([
            'responded'    => true,
            'bot'          => [
                'id'    => $bot->id,
                'name'  => $bot->name,
                'style' => $bot->style,
            ],
            'message'      => $response,
            'streamer_text' => $streamerText,
            'channel'      => $channel,
            'context_size' => $this->context->count($channel),
        ]);
    }

    private function enrich(string $text, ?string $game, ?string $title): string
    {
        if (!$game && !$title) return $text;

        $parts = [];
        if ($game)  $parts[] = "Игра: {$game}";
        if ($title) $parts[] = "Стрим: {$title}";

        return $text . ' [' . implode(', ', $parts) . ']';
    }
}