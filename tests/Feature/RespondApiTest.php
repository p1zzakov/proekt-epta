<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Services\BotSelector;
use App\Services\ResponseGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RespondApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeBot(array $attrs = []): Bot
    {
        return Bot::create(array_merge([
            'name'      => 'TestBot',
            'style'     => 'sarcastic',
            'knowledge' => [],
            'toxicity'  => 0.0,
            'verbosity' => 1.0,
            'weight'    => 10,
        ], $attrs));
    }

    // ─────────────────────────────────────────
    // POST /api/respond — успешный ответ
    // ─────────────────────────────────────────

    public function test_respond_returns_bot_message(): void
    {
        $this->makeBot();

        Http::fake([
            '*/api/chat' => Http::response([
                'message' => ['content' => 'ну и билд конечно 🤔'],
            ], 200),
        ]);

        $response = $this->postJson('/api/respond', [
            'text' => 'ребят норм билд?',
        ]);

        $response->assertOk()
                 ->assertJsonPath('responded', true)
                 ->assertJsonStructure([
                     'responded',
                     'bot' => ['id', 'name', 'style'],
                     'message',
                     'streamer_text',
                 ]);
    }

    // ─────────────────────────────────────────
    // Нет ботов → responded: false
    // ─────────────────────────────────────────

    public function test_respond_returns_false_when_no_bots(): void
    {
        $response = $this->postJson('/api/respond', [
            'text' => 'привет чат',
        ]);

        $response->assertOk()
                 ->assertJsonPath('responded', false)
                 ->assertJsonPath('reason', 'no_eligible_bot');
    }

    // ─────────────────────────────────────────
    // Ollama упала → 503
    // ─────────────────────────────────────────

    public function test_respond_returns_503_when_ollama_fails(): void
    {
        $this->makeBot();

        Http::fake([
            '*/api/chat' => Http::response('error', 500),
        ]);

        $response = $this->postJson('/api/respond', [
            'text' => 'ребят норм билд?',
        ]);

        $response->assertStatus(503)
                 ->assertJsonPath('responded', false)
                 ->assertJsonPath('reason', 'ollama_failed');
    }

    // ─────────────────────────────────────────
    // Валидация — text обязателен
    // ─────────────────────────────────────────

    public function test_respond_requires_text(): void
    {
        $response = $this->postJson('/api/respond', []);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['text']);
    }

    // ─────────────────────────────────────────
    // С контекстом и игрой
    // ─────────────────────────────────────────

    public function test_respond_accepts_context_and_game(): void
    {
        $this->makeBot();

        Http::fake([
            '*/api/chat' => Http::response([
                'message' => ['content' => 'в доте надо было взять другой предмет'],
            ], 200),
        ]);

        $response = $this->postJson('/api/respond', [
            'text'         => 'ну как вам этот момент?',
            'game'         => 'Dota 2',
            'stream_title' => 'Рейтинг гринд',
            'context'      => [
                ['role' => 'streamer', 'name' => 'Streamer', 'text' => 'смотрите этот момент'],
                ['role' => 'bot',      'name' => 'Petya',    'text' => 'топ'],
            ],
        ]);

        $response->assertOk()
                 ->assertJsonPath('responded', true);
    }

    // ─────────────────────────────────────────
    // Кулдаун применяется после ответа
    // ─────────────────────────────────────────

    public function test_cooldown_applied_after_response(): void
    {
        $bot = $this->makeBot();

        Http::fake([
            '*/api/chat' => Http::response([
                'message' => ['content' => 'норм стрим'],
            ], 200),
        ]);

        $this->postJson('/api/respond', ['text' => 'привет чат']);

        $this->assertNotNull($bot->fresh()->cooldown_until);
        $this->assertTrue($bot->fresh()->cooldown_until->isFuture());
    }
}
