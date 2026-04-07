<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResponseGenerator
{
    private string $ollamaUrl;
    private string $model;
    private int    $timeout;

    public function __construct()
    {
        $this->ollamaUrl = config('bot.ollama_url', 'http://localhost:11434');
        $this->model     = config('bot.ollama_model', 'llama3');
        $this->timeout   = config('bot.ollama_timeout', 30);
    }

    public function generate(Bot $bot, string $streamerText, array $context = []): ?string
    {
        $personality = app(PersonalityEngine::class);
        $payload     = $personality->buildPrompt($bot, $streamerText, $context);

        try {
            $response = $this->callOllama($payload['system'], $payload['messages']);
        } catch (ConnectionException $e) {
            Log::error('Ollama connection failed', ['bot' => $bot->name, 'error' => $e->getMessage()]);
            return null;
        } catch (\Exception $e) {
            Log::error('ResponseGenerator error', ['bot' => $bot->name, 'error' => $e->getMessage()]);
            return null;
        }

        if (!$response) return null;

        $styled = $personality->applyWritingStyle($bot, $response);

        Log::info('Bot response generated', [
            'bot'      => $bot->name,
            'streamer' => $streamerText,
            'response' => $styled,
        ]);

        return $styled;
    }

    private function callOllama(string $system, array $messages): ?string
    {
        $response = Http::timeout($this->timeout)
            ->post("{$this->ollamaUrl}/api/chat", [
                'model'    => $this->model,
                'stream'   => false,
                'messages' => $this->injectSystemIntoMessages($system, $messages),
                'options'  => [
                    'temperature'       => 1.0,
                    'top_p'             => 0.95,
                    'repeat_penalty'    => 1.1,
                    'num_predict'       => 60,
                ],
            ]);

        if (!$response->successful()) {
            Log::warning('Ollama returned non-200', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json('message.content');
    }

    /**
     * phi3 и многие модели плохо читают поле "system" в /api/chat.
     * Надёжнее — вшить системный промпт первым сообщением от user.
     */
    private function injectSystemIntoMessages(string $system, array $messages): array
    {
        $systemMessage = [
            'role'    => 'user',
            'content' => $system,
        ];

        $ack = [
            'role'    => 'assistant',
            'content' => 'Понял, буду отвечать именно так.',
        ];

        return array_merge([$systemMessage, $ack], $messages);
    }

    public function isAvailable(): bool
    {
        try {
            return Http::timeout(3)->get("{$this->ollamaUrl}/api/tags")->successful();
        } catch (\Exception) {
            return false;
        }
    }
}