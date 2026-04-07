<?php

namespace Tests\Unit;

use App\Services\ContextManager;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ContextManagerTest extends TestCase
{
    private ContextManager $ctx;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ctx = new ContextManager();
        // Чистим перед каждым тестом
        Redis::del('context:stream:testchannel');
    }

    public function test_empty_context_returns_empty_array(): void
    {
        $result = $this->ctx->getContext('testchannel');
        $this->assertEmpty($result);
    }

    public function test_add_streamer_message(): void
    {
        $this->ctx->addStreamerMessage('testchannel', 'норм билд?');
        $context = $this->ctx->getContext('testchannel');

        $this->assertCount(1, $context);
        $this->assertEquals('streamer', $context[0]['role']);
        $this->assertEquals('норм билд?', $context[0]['text']);
    }

    public function test_add_bot_message(): void
    {
        $this->ctx->addBotMessage('testchannel', 'Vasya', 'ну конечно 🙄');
        $context = $this->ctx->getContext('testchannel');

        $this->assertCount(1, $context);
        $this->assertEquals('bot', $context[0]['role']);
        $this->assertEquals('Vasya', $context[0]['name']);
        $this->assertEquals('ну конечно 🙄', $context[0]['text']);
    }

    public function test_messages_ordered_newest_first(): void
    {
        $this->ctx->addStreamerMessage('testchannel', 'первое');
        $this->ctx->addBotMessage('testchannel', 'Vasya', 'второе');
        $this->ctx->addStreamerMessage('testchannel', 'третье');

        $context = $this->ctx->getContext('testchannel');

        $this->assertEquals('третье', $context[0]['text']);
        $this->assertEquals('второе', $context[1]['text']);
        $this->assertEquals('первое', $context[2]['text']);
    }

    public function test_context_limit_respected(): void
    {
        // Добавляем 25 сообщений
        for ($i = 1; $i <= 25; $i++) {
            $this->ctx->addStreamerMessage('testchannel', "сообщение {$i}");
        }

        // Всего не больше 20
        $this->assertLessThanOrEqual(20, $this->ctx->count('testchannel'));
    }

    public function test_get_context_with_limit(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->ctx->addStreamerMessage('testchannel', "msg {$i}");
        }

        $context = $this->ctx->getContext('testchannel', 5);
        $this->assertCount(5, $context);
    }

    public function test_clear_context(): void
    {
        $this->ctx->addStreamerMessage('testchannel', 'что-то');
        $this->ctx->addBotMessage('testchannel', 'Bot', 'ответ');

        $this->ctx->clear('testchannel');

        $this->assertEmpty($this->ctx->getContext('testchannel'));
        $this->assertEquals(0, $this->ctx->count('testchannel'));
    }

    public function test_count_messages(): void
    {
        $this->assertEquals(0, $this->ctx->count('testchannel'));

        $this->ctx->addStreamerMessage('testchannel', 'раз');
        $this->ctx->addBotMessage('testchannel', 'Bot', 'два');

        $this->assertEquals(2, $this->ctx->count('testchannel'));
    }

    public function test_channels_are_isolated(): void
    {
        Redis::del('context:stream:channel_a');
        Redis::del('context:stream:channel_b');

        $this->ctx->addStreamerMessage('channel_a', 'сообщение А');
        $this->ctx->addStreamerMessage('channel_b', 'сообщение Б');

        $ctxA = $this->ctx->getContext('channel_a');
        $ctxB = $this->ctx->getContext('channel_b');

        $this->assertCount(1, $ctxA);
        $this->assertCount(1, $ctxB);
        $this->assertEquals('сообщение А', $ctxA[0]['text']);
        $this->assertEquals('сообщение Б', $ctxB[0]['text']);

        Redis::del('context:stream:channel_a');
        Redis::del('context:stream:channel_b');
    }

    public function test_message_has_timestamp(): void
    {
        $this->ctx->addStreamerMessage('testchannel', 'тест');
        $context = $this->ctx->getContext('testchannel');

        $this->assertArrayHasKey('at', $context[0]);
        $this->assertIsInt($context[0]['at']);
        $this->assertGreaterThan(0, $context[0]['at']);
    }

    public function test_channel_name_is_case_insensitive(): void
    {
        $this->ctx->addStreamerMessage('TestChannel', 'сообщение');
        $context = $this->ctx->getContext('testchannel'); // нижний регистр

        $this->assertCount(1, $context);
    }
}
