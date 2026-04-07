<?php

namespace Tests\Unit;

use App\Models\Bot;
use App\Services\PersonalityEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalityEngineTest extends TestCase
{
    use RefreshDatabase;

    private PersonalityEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PersonalityEngine();
    }

    private function makeBot(array $attrs = []): Bot
    {
        return Bot::create(array_merge([
            'name'      => 'Vasya',
            'style'     => 'neutral',
            'knowledge' => [],
            'toxicity'  => 0.0,
            'verbosity' => 0.5,
            'weight'    => 10,
        ], $attrs));
    }

    // ─────────────────────────────────────────
    // Базовая структура промпта
    // ─────────────────────────────────────────

    public function test_build_prompt_returns_system_and_messages(): void
    {
        $bot    = $this->makeBot();
        $result = $this->engine->buildPrompt($bot, 'норм билд?');

        $this->assertArrayHasKey('system', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertNotEmpty($result['system']);
        $this->assertNotEmpty($result['messages']);
    }

    public function test_system_prompt_contains_bot_name(): void
    {
        $bot    = $this->makeBot(['name' => 'Kolyan']);
        $result = $this->engine->buildPrompt($bot, 'привет');

        $this->assertStringContainsString('Kolyan', $result['system']);
    }

    public function test_system_prompt_is_in_russian(): void
    {
        $bot    = $this->makeBot();
        $result = $this->engine->buildPrompt($bot, 'норм?');

        $this->assertStringContainsString('русском', $result['system']);
    }

    public function test_system_prompt_says_never_reveal_as_bot(): void
    {
        $bot    = $this->makeBot();
        $result = $this->engine->buildPrompt($bot, 'ты бот?');

        $this->assertStringContainsString('бот', $result['system']);
    }

    // ─────────────────────────────────────────
    // Стили — проверяем ключевые слова промпта
    // ─────────────────────────────────────────

    /** @dataProvider styleKeywordProvider */
    public function test_style_keyword_in_system_prompt(string $style, string $keyword): void
    {
        $bot    = $this->makeBot(['style' => $style]);
        $result = $this->engine->buildPrompt($bot, 'что думаете?');

        $this->assertStringContainsStringIgnoringCase(
            $keyword,
            $result['system'],
            "Промпт стиля '{$style}' должен содержать '{$keyword}'"
        );
    }

    public static function styleKeywordProvider(): array
    {
        return [
            'sarcastic' => ['sarcastic', 'скептик'],
            'hype'      => ['hype',      'фанат'],
            'toxic'     => ['toxic',     'токсичный'],
            'silent'    => ['silent',    'молчун'],
            'memer'     => ['memer',     'мемщик'],
            'analyst'   => ['analyst',   'аналитик'],
            'noob'      => ['noob',      'нубас'],
            'veteran'   => ['veteran',   'старожил'],
            'hater'     => ['hater',     'хейтер'],
        ];
    }

    // ─────────────────────────────────────────
    // Смайлы в инструкции
    // ─────────────────────────────────────────

    public function test_hype_style_has_pogchamp_in_instructions(): void
    {
        $bot    = $this->makeBot(['style' => 'hype']);
        $result = $this->engine->buildPrompt($bot, 'топ момент!');

        $this->assertStringContainsString('PogChamp', $result['system']);
    }

    public function test_memer_style_has_kekw_in_instructions(): void
    {
        $bot    = $this->makeBot(['style' => 'memer']);
        $result = $this->engine->buildPrompt($bot, 'лол что');

        $this->assertStringContainsString('KEKW', $result['system']);
    }

    public function test_sarcastic_style_has_doubt_emoji_in_instructions(): void
    {
        $bot    = $this->makeBot(['style' => 'sarcastic']);
        $result = $this->engine->buildPrompt($bot, 'ну как вам?');

        $this->assertStringContainsString('monkaHmm', $result['system']);
    }

    // ─────────────────────────────────────────
    // Knowledge и toxicity
    // ─────────────────────────────────────────

    public function test_knowledge_topics_in_system_prompt(): void
    {
        $bot    = $this->makeBot(['knowledge' => ['gaming', 'dota']]);
        $result = $this->engine->buildPrompt($bot, 'какой предмет брать?');

        $this->assertStringContainsString('gaming', $result['system']);
        $this->assertStringContainsString('dota', $result['system']);
    }

    public function test_high_toxicity_adds_swear_note(): void
    {
        $bot    = $this->makeBot(['toxicity' => 0.8]);
        $result = $this->engine->buildPrompt($bot, 'ну как вам?');

        $this->assertStringContainsString('блин', $result['system']);
    }

    public function test_low_toxicity_no_swear_note(): void
    {
        $bot    = $this->makeBot(['toxicity' => 0.1]);
        $result = $this->engine->buildPrompt($bot, 'ну как вам?');

        $this->assertStringNotContainsString('блин', $result['system']);
    }

    // ─────────────────────────────────────────
    // Messages / context
    // ─────────────────────────────────────────

    public function test_streamer_text_is_last_message(): void
    {
        $bot    = $this->makeBot();
        $result = $this->engine->buildPrompt($bot, 'этот билд норм?');
        $last   = end($result['messages']);

        $this->assertStringContainsString('этот билд норм?', $last['content']);
        $this->assertEquals('user', $last['role']);
    }

    public function test_context_messages_added_in_correct_order(): void
    {
        $bot     = $this->makeBot();
        $context = [
            ['role' => 'streamer', 'name' => 'Streamer', 'text' => 'смотрите этот момент'],
            ['role' => 'bot',      'name' => 'Petya',    'text' => 'топ момент'],
        ];
        $result   = $this->engine->buildPrompt($bot, 'ну как вам?', $context);
        $messages = $result['messages'];

        $this->assertCount(3, $messages);
        $this->assertStringContainsString('смотрите этот момент', $messages[0]['content']);
        $this->assertStringContainsString('топ момент',           $messages[1]['content']);
        $this->assertStringContainsString('ну как вам?',          $messages[2]['content']);
    }

    public function test_empty_context_gives_one_message(): void
    {
        $bot    = $this->makeBot();
        $result = $this->engine->buildPrompt($bot, 'привет чат', []);

        $this->assertCount(1, $result['messages']);
    }

    // ─────────────────────────────────────────
    // applyWritingStyle
    // ─────────────────────────────────────────

    public function test_apply_style_removes_bot_name_prefix(): void
    {
        $bot    = $this->makeBot(['name' => 'Vasya', 'style' => 'neutral']);
        $result = $this->engine->applyWritingStyle($bot, '[Vasya]: норм стрим');

        $this->assertStringNotContainsString('[Vasya]:', $result);
        $this->assertStringContainsString('норм стрим', $result);
    }

    public function test_apply_style_removes_quotes(): void
    {
        $bot    = $this->makeBot(['style' => 'neutral']);
        $result = $this->engine->applyWritingStyle($bot, '"норм стрим"');

        $this->assertStringNotContainsString('"', $result);
    }

    public function test_apply_style_returns_non_empty_string(): void
    {
        $bot    = $this->makeBot(['style' => 'friendly']);
        $result = $this->engine->applyWritingStyle($bot, 'хороший стрим сегодня');

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function test_silent_style_truncates_to_first_sentence(): void
    {
        $bot    = $this->makeBot(['style' => 'silent']);
        $result = $this->engine->applyWritingStyle($bot, 'норм билд. вообще топ. ещё бы лучше было.');

        $this->assertStringContainsString('норм билд', $result);
        $this->assertStringNotContainsString('ещё бы лучше было', $result);
    }

    public function test_hype_style_adds_emote_when_no_emoji_present(): void
    {
        // Запускаем несколько раз — хоть раз должен добавить эмоут/эмодзи
        $bot     = $this->makeBot(['style' => 'hype']);
        $hasEmoji = false;

        for ($i = 0; $i < 20; $i++) {
            $result = $this->engine->applyWritingStyle($bot, 'просто текст без смайлов');
            // hype всегда добавляет смайл если нет
            if (preg_match('/[\x{1F000}-\x{1FFFF}]|[\x{2600}-\x{27BF}]|Pog|POGGERS|EZ|Clap|HYPERS/u', $result)) {
                $hasEmoji = true;
                break;
            }
        }

        $this->assertTrue($hasEmoji, 'Hype бот должен добавлять эмоут/эмодзи');
    }

    // ─────────────────────────────────────────
    // Verbosity в промпте
    // ─────────────────────────────────────────

    public function test_low_verbosity_adds_short_instruction(): void
    {
        $bot    = $this->makeBot(['verbosity' => 0.1]);
        $result = $this->engine->buildPrompt($bot, 'привет');

        $this->assertStringContainsString('1-4 слова', $result['system']);
    }

    public function test_high_verbosity_allows_two_sentences(): void
    {
        $bot    = $this->makeBot(['verbosity' => 0.9]);
        $result = $this->engine->buildPrompt($bot, 'привет');

        $this->assertStringContainsString('2 предложения', $result['system']);
    }

    // ─────────────────────────────────────────
    // Пулы смайлов доступны публично
    // ─────────────────────────────────────────

    public function test_emote_pools_are_not_empty(): void
    {
        $this->assertNotEmpty($this->engine->getEmotePool('hype'));
        $this->assertNotEmpty($this->engine->getEmotePool('laugh'));
        $this->assertNotEmpty($this->engine->getEmojiPool('hype'));
        $this->assertNotEmpty($this->engine->getRuWordsPool('laugh'));
    }
}
