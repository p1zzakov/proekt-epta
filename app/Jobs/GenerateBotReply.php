<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Services\BotSelector;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBotReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180; // 🔥 важно
    public $tries = 1;

    protected $streamId;
    protected $message;

    public function __construct($streamId, $message)
    {
        $this->streamId = $streamId;
        $this->message = $message;
    }

    public function handle()
    {
        Log::info('BOT JOB START');

        $selector = new BotSelector();
        $bot = $selector->select($this->message);

        if (!$bot) {
            Log::warning('NO BOT SELECTED');
            return;
        }

        Log::info('BOT SELECTED: ' . $bot->name);

        $prompt = $this->buildPrompt($bot);

        try {
            $response = Http::timeout(180)
                ->connectTimeout(10)
                ->post(env('OLLAMA_HOST') . '/api/generate', [
                    'model' => env('OLLAMA_MODEL'),
                    'prompt' => $prompt,
                    'stream' => false,
                ]);

            if (!$response->successful()) {
                Log::error('OLLAMA FAIL', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return;
            }

            $data = $response->json();

            $reply = $data['response'] ?? null;

            if (!$reply) {
                Log::error('EMPTY RESPONSE');
                return;
            }

            Log::info('FINAL REPLY: ' . $reply);

            Redis::publish("stream_chat_{$this->streamId}", json_encode([
                'bot' => $bot->name,
                'message' => $reply
            ]));

        } catch (\Exception $e) {
            Log::error('OLLAMA EXCEPTION: ' . $e->getMessage());
        }
    }

    protected function buildPrompt($bot)
    {
        return "
Ты чат-бот стрима.

Имя: {$bot->name}
Стиль: {$bot->style}

Сообщение пользователя:
{$this->message}

Ответь коротко, как в Twitch-чате.
";
    }
}