<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\ResponseGenerator;
use Illuminate\Console\Command;

class TestBotResponse extends Command
{
    protected $signature = 'bot:test {phrase? : Фраза стримера} {--style= : Стиль бота} {--all : Прогнать все стили}';
    protected $description = 'Живой тест — бот отвечает на фразу стримера через Ollama';

    public function handle(ResponseGenerator $generator): int
    {
        if ($this->option('all')) {
            return $this->testAllStyles($generator);
        }

        $phrase = $this->argument('phrase') ?? 'ребят, что думаете про этот билд?';
        $style  = $this->option('style') ?? 'sarcastic';

        $this->runTest($generator, $phrase, $style);

        return 0;
    }

    private function runTest(ResponseGenerator $generator, string $phrase, string $style): void
    {
        $bot = Bot::firstOrCreate(
            ['name' => "TestBot_{$style}"],
            [
                'style'     => $style,
                'knowledge' => ['gaming', 'стримы'],
                'toxicity'  => 0.3,
                'verbosity' => 1.0,
                'weight'    => 10,
            ]
        );

        $this->newLine();
        $this->line("┌─────────────────────────────────────────");
        $this->line("│ 🤖 Бот:      <fg=cyan>{$bot->name}</> (стиль: <fg=yellow>{$style}</>)");
        $this->line("│ 💬 Стример:  <fg=white>{$phrase}</>");
        $this->line("├─────────────────────────────────────────");
        $this->line("│ ⏳ Отправляем в Ollama...");

        $start    = microtime(true);
        $response = $generator->generate($bot, $phrase);
        $elapsed  = round((microtime(true) - $start) * 1000);

        if ($response) {
            $this->line("│ ✅ Ответ бота: <fg=green>{$response}</>");
        } else {
            $this->line("│ ❌ <fg=red>Ответ не получен — проверь Ollama</>");
        }

        $this->line("│ ⚡ Время:     {$elapsed}ms");
        $this->line("└─────────────────────────────────────────");
        $this->newLine();
    }

    private function testAllStyles(ResponseGenerator $generator): int
    {
        $this->newLine();
        $this->info('🚀 Прогоняем все 9 стилей...');

        $phrase = $this->argument('phrase') ?? 'ребят, что думаете про этот билд?';
        $this->line("💬 Фраза стримера: <fg=white>{$phrase}</>");

        $styles = ['sarcastic', 'hype', 'toxic', 'silent', 'memer', 'analyst', 'noob', 'veteran', 'hater'];

        foreach ($styles as $style) {
            $this->runTest($generator, $phrase, $style);
            sleep(1);
        }

        $this->info('✅ Готово!');
        return 0;
    }
}
