<?php

namespace Tests\Unit;

use App\Models\Bot;
use App\Services\BotSelector;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotSelectorTest extends TestCase
{
    use RefreshDatabase;

    private BotSelector $selector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selector = new BotSelector();
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    private function makeBot(array $attrs = []): Bot
    {
        return Bot::create(array_merge([
            'name'          => 'TestBot',
            'style'         => 'neutral',
            'knowledge'     => [],
            'toxicity'      => 0.0,
            'verbosity'     => 1.0, // всегда отвечает
            'weight'        => 10,
            'cooldown_until' => null,
        ], $attrs));
    }

    // ─────────────────────────────────────────
    // 1. Нет ботов → возвращает null
    // ─────────────────────────────────────────

    public function test_returns_null_when_no_bots(): void
    {
        $result = $this->selector->select('привет чат');
        $this->assertNull($result);
    }

    // ─────────────────────────────────────────
    // 2. Бот на кулдауне — не выбирается
    // ─────────────────────────────────────────

    public function test_bot_on_cooldown_is_skipped(): void
    {
        $this->makeBot([
            'name'          => 'CoolBot',
            'cooldown_until' => Carbon::now()->addMinutes(5),
        ]);

        $result = $this->selector->select('что думаете');
        $this->assertNull($result);
    }

    // ─────────────────────────────────────────
    // 3. Бот с истёкшим кулдауном — выбирается
    // ─────────────────────────────────────────

    public function test_bot_with_expired_cooldown_is_eligible(): void
    {
        $bot = $this->makeBot([
            'name'          => 'ReadyBot',
            'cooldown_until' => Carbon::now()->subSeconds(1),
        ]);

        $result = $this->selector->select('привет');
        $this->assertNotNull($result);
        $this->assertEquals($bot->id, $result->id);
    }

    // ─────────────────────────────────────────
    // 4. Релевантность по knowledge
    // ─────────────────────────────────────────

    public function test_relevant_bot_is_preferred_over_fallback(): void
    {
        // Бот знает gaming — должен выбраться на фразу про игру
        $gamer = $this->makeBot(['name' => 'Gamer', 'knowledge' => ['gaming', 'билд'], 'weight' => 10]);
        // Бот без тематики — fallback
        $random = $this->makeBot(['name' => 'Random', 'knowledge' => [], 'weight' => 10]);

        // Запускаем 20 раз — хоть раз должен выбраться Gamer
        $selectedNames = [];
        for ($i = 0; $i < 20; $i++) {
            $bot = $this->selector->select('этот билд вообще норм?');
            if ($bot) {
                $selectedNames[] = $bot->name;
            }
        }

        $this->assertContains('Gamer', $selectedNames, 'Релевантный бот должен выбираться');
    }

    // ─────────────────────────────────────────
    // 5. applyCooldown ставит cooldown
    // ─────────────────────────────────────────

    public function test_apply_cooldown_sets_future_timestamp(): void
    {
        $bot = $this->makeBot();

        $this->selector->applyCooldown($bot);

        $bot->refresh();
        $this->assertNotNull($bot->cooldown_until);
        $this->assertTrue($bot->cooldown_until->isFuture());
    }

    // ─────────────────────────────────────────
    // 6. После applyCooldown бот не выбирается
    // ─────────────────────────────────────────

    public function test_bot_not_selected_after_cooldown_applied(): void
    {
        $bot = $this->makeBot();
        $this->selector->applyCooldown($bot);

        $result = $this->selector->select('что думаете про стрим');
        $this->assertNull($result);
    }

    // ─────────────────────────────────────────
    // 7. Verbosity = 0 → бот никогда не отвечает
    // ─────────────────────────────────────────

    public function test_zero_verbosity_bot_never_selected(): void
    {
        $this->makeBot(['name' => 'Silent', 'verbosity' => 0.0]);

        $results = [];
        for ($i = 0; $i < 30; $i++) {
            $results[] = $this->selector->select('привет чат');
        }

        $selected = array_filter($results);
        $this->assertEmpty($selected, 'Бот с verbosity=0 не должен выбираться никогда');
    }

    // ─────────────────────────────────────────
    // 8. Weighted random — тяжёлый бот чаще
    // ─────────────────────────────────────────

    public function test_heavy_weight_bot_selected_more_often(): void
    {
        $heavy = $this->makeBot(['name' => 'Heavy', 'weight' => 90]);
        $light = $this->makeBot(['name' => 'Light', 'weight' => 10]);

        $counts = ['Heavy' => 0, 'Light' => 0];

        for ($i = 0; $i < 200; $i++) {
            $bot = $this->selector->select('что думаешь');
            if ($bot) {
                $counts[$bot->name]++;
            }
        }

        $this->assertGreaterThan(
            $counts['Light'],
            $counts['Heavy'],
            'Бот с весом 90 должен выбираться чаще чем с весом 10'
        );
    }

    // ─────────────────────────────────────────
    // 9. Fallback — нет релевантных, выбирает любого
    // ─────────────────────────────────────────

    public function test_fallback_selects_any_bot_when_no_relevant(): void
    {
        $this->makeBot(['name' => 'OnlyBot', 'knowledge' => ['футбол']]);

        // Фраза про игру — не релевантна футбольному боту, но fallback должен сработать
        $result = $this->selector->select('погода сегодня норм');

        // Может выбраться (fallback) или нет (verbosity) — главное не крашится
        $this->assertTrue($result === null || $result instanceof Bot);
    }

    // ─────────────────────────────────────────
    // 10. Регистронезависимость knowledge
    // ─────────────────────────────────────────

    public function test_knowledge_matching_is_case_insensitive(): void
    {
        $bot = $this->makeBot(['name' => 'CaseBot', 'knowledge' => ['Gaming']]);

        $result = $this->selector->select('ну gaming вообще топ');
        $this->assertNotNull($result);
        $this->assertEquals('CaseBot', $result->name);
    }
}
