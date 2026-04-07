<?php

namespace Tests\Unit;

use App\Models\Bot;
use App\Services\ResponseGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResponseGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function makeBot(array $attrs = []): Bot
    {
        return Bot::create(array_merge([
            'name'      => 'TestBot',
            'style'     => 'neutral',
            'knowledge' => [],
            'toxicity'  => 0.0,
            'verbosity' => 1.0,
            'weight'    => 10,
        ], $attrs));
    }

    // ─────────────────────────────────────────
    // Успешный ответ от Ollama
    // ─────────────────────────────────────────

    public function test_returns_string_on_successful_ollama_response(): void
    {
        Http::fake([
            '*/api/chat' => Http::response([
                'message' => ['content' => 'норм билд вообще'],
            ], 200),
        ]);

        $bot = $this->makeBot();
        $generator = new ResponseGenerator();

        $result = $generator->generate($bot, 'что думаете про этот билд?');

        $this->assertNotNull($result);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ─────────────────────────────────────────
    // Ollama недоступна → возвращает null
    // ─────────────────────────────────────────

    public function test_returns_null_on_connection_failure(): void
    {
        Http::fake([
            '*/api/chat' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        $bot = $this->makeBot();
        $generator = new ResponseGenerator();

        $result = $generator->generate($bot, 'привет чат');

        $this->assertNull($result);
    }

    // ─────────────────────────────────────────
    // Ollama вернула 500 → возвращает null
    // ─────────────────────────────────────────

    public function test_returns_null_on_server_error(): void
    {
        Http::fake([
            '*/api/chat' => Http::response('Internal Server Error', 500),
        ]);

        $bot = $this->makeBot();
        $generator = new ResponseGenerator();

        $result = $generator->generate($bot, 'ну как вам?');

        $this->assertNull($result);
    }

    // ─────────────────────────────────────────
    // Ollama вернула пустой content → null
    // ─────────────────────────────────────────

    public function test_returns_null_when_content_missing(): void
    {
        Http::fake([
            '*/api/chat' => Http::response(['message' => []], 200),
        ]);

        $bot = $this->makeBot();
        $generator = new ResponseGenerator();

        $result = $generator->generate($bot, 'что скажешь?');

        $this->assertNull($result);
    }

    // ─────────────────────────────────────────
    // isAvailable — Ollama доступна
    // ─────────────────────────────────────────

    public function test_is_available_returns_true_when_ollama_up(): void
    {
        Http::fake([
            '*/api/tags' => Http::response(['models' => []], 200),
        ]);

        $generator = new ResponseGenerator();
        $this->assertTrue($generator->isAvailable());
    }

    // ─────────────────────────────────────────
    // isAvailable — Ollama недоступна
    // ─────────────────────────────────────────

    public function test_is_available_returns_false_when_ollama_down(): void
    {
        Http::fake([
            '*/api/tags' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('refused');
            },
        ]);

        $generator = new ResponseGenerator();
        $this->assertFalse($generator->isAvailable());
    }

    // ─────────────────────────────────────────
    // Ответ проходит через PersonalityEngine (стиль применяется)
    // ─────────────────────────────────────────

    public function test_response_does_not_contain_bot_name_prefix(): void
    {
        Http::fake([
            '*/api/chat' => Http::response([
                'message' => ['content' => '[TestBot]: норм стрим сегодня'],
            ], 200),
        ]);

        $bot = $this->makeBot(['name' => 'TestBot']);
        $generator = new ResponseGenerator();

        $result = $generator->generate($bot, 'как вам стрим?');

        $this->assertNotNull($result);
        $this->assertStringNotContainsString('[TestBot]:', $result);
    }

    // ─────────────────────────────────────────
    // С контекстом — запрос всё равно уходит
    // ─────────────────────────────────────────

    public function test_generate_with_context_works(): void
    {
        Http::fake([
            '*/api/chat' => Http::response([
                'message' => ['content' => 'да согласен'],
            ], 200),
        ]);

        $bot = $this->makeBot();
        $context = [
            ['role' => 'streamer', 'name' => 'Streamer', 'text' => 'этот момент был норм'],
            ['role' => 'bot',      'name' => 'Petya',    'text' => 'ну да'],
        ];

        $generator = new ResponseGenerator();
        $result = $generator->generate($bot, 'ну как вам?', $context);

        $this->assertNotNull($result);
        $this->assertStringContainsString('согласен', $result);
    }
}
