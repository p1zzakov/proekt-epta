<?php

namespace Tests\Feature;

use App\Models\Bot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotApiTest extends TestCase
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
    // GET /api/bots
    // ─────────────────────────────────────────

    public function test_get_bots_returns_empty_list(): void
    {
        $response = $this->getJson('/api/bots');

        $response->assertOk()
                 ->assertJson(['data' => [], 'total' => 0]);
    }

    public function test_get_bots_returns_all_bots(): void
    {
        $this->makeBot(['name' => 'BotA']);
        $this->makeBot(['name' => 'BotB']);

        $response = $this->getJson('/api/bots');

        $response->assertOk()
                 ->assertJson(['total' => 2])
                 ->assertJsonCount(2, 'data');
    }

    // ─────────────────────────────────────────
    // POST /api/bots
    // ─────────────────────────────────────────

    public function test_create_bot_with_valid_data(): void
    {
        $response = $this->postJson('/api/bots', [
            'name'      => 'Vasya',
            'style'     => 'sarcastic',
            'knowledge' => ['gaming', 'dota'],
            'toxicity'  => 0.3,
            'verbosity' => 0.7,
            'weight'    => 15,
        ]);

        $response->assertCreated()
                 ->assertJsonPath('data.name', 'Vasya')
                 ->assertJsonPath('data.style', 'sarcastic');

        $this->assertDatabaseHas('bots', ['name' => 'Vasya']);
    }

    public function test_create_bot_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/bots', []);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['name', 'style']);
    }

    public function test_create_bot_fails_with_invalid_style(): void
    {
        $response = $this->postJson('/api/bots', [
            'name'  => 'Bot',
            'style' => 'nonexistent_style',
        ]);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['style']);
    }

    public function test_create_bot_fails_with_duplicate_name(): void
    {
        $this->makeBot(['name' => 'Vasya']);

        $response = $this->postJson('/api/bots', [
            'name'  => 'Vasya',
            'style' => 'hype',
        ]);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['name']);
    }

    // ─────────────────────────────────────────
    // GET /api/bots/{id}
    // ─────────────────────────────────────────

    public function test_get_single_bot(): void
    {
        $bot = $this->makeBot(['name' => 'Kolyan']);

        $response = $this->getJson("/api/bots/{$bot->id}");

        $response->assertOk()
                 ->assertJsonPath('data.name', 'Kolyan');
    }

    public function test_get_nonexistent_bot_returns_404(): void
    {
        $this->getJson('/api/bots/999')->assertNotFound();
    }

    // ─────────────────────────────────────────
    // PUT /api/bots/{id}
    // ─────────────────────────────────────────

    public function test_update_bot(): void
    {
        $bot = $this->makeBot(['name' => 'OldName', 'style' => 'neutral']);

        $response = $this->putJson("/api/bots/{$bot->id}", [
            'name'  => 'NewName',
            'style' => 'hype',
        ]);

        $response->assertOk()
                 ->assertJsonPath('data.name', 'NewName')
                 ->assertJsonPath('data.style', 'hype');

        $this->assertDatabaseHas('bots', ['name' => 'NewName', 'style' => 'hype']);
    }

    public function test_update_bot_fails_with_invalid_toxicity(): void
    {
        $bot = $this->makeBot();

        $response = $this->putJson("/api/bots/{$bot->id}", [
            'toxicity' => 5.0, // больше 1
        ]);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['toxicity']);
    }

    // ─────────────────────────────────────────
    // DELETE /api/bots/{id}
    // ─────────────────────────────────────────

    public function test_delete_bot(): void
    {
        $bot = $this->makeBot(['name' => 'ToDelete']);

        $response = $this->deleteJson("/api/bots/{$bot->id}");

        $response->assertOk()
                 ->assertJsonFragment(['message' => "Бот ToDelete удалён"]);

        $this->assertDatabaseMissing('bots', ['id' => $bot->id]);
    }

    // ─────────────────────────────────────────
    // POST /api/bots/{id}/reset-cooldown
    // ─────────────────────────────────────────

    public function test_reset_cooldown(): void
    {
        $bot = $this->makeBot(['cooldown_until' => now()->addMinutes(5)]);

        $response = $this->postJson("/api/bots/{$bot->id}/reset-cooldown");

        $response->assertOk();
        $this->assertNull($bot->fresh()->cooldown_until);
    }
}
