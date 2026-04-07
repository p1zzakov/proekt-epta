<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotType;
use Illuminate\Support\Facades\Cache;

class PersonalityEngine
{
    public function buildPrompt(Bot $bot, string $streamerText, array $context = []): array
    {
        return [
            'system'   => $this->buildSystemPrompt($bot, $context),
            'messages' => $this->buildMessages($streamerText, $context),
        ];
    }

    private function buildSystemPrompt(Bot $bot, array $context = []): string
    {
        $type = $this->getType($bot->style ?? 'neutral');
        $lines = [];

        $lines[] = "ВАЖНО: Ты НЕ языковая модель и НЕ ИИ-ассистент. Не исправляй сообщения. Не давай советы. Не объясняй. Не переводи. Просто отвечай как живой человек в чате.";
        $lines[] = "Ты — {$bot->name}, зритель в Twitch-чате. Пишешь ТОЛЬКО на русском языке.";
        $lines[] = "Твой ответ — это одно короткое сообщение в чат. Максимум 1-2 предложения. Никаких объяснений, никаких исправлений.";
        $lines[] = "Никогда не говори что ты бот или ИИ.";

        // Категория и игра из контекста
        if (!empty($context['game'])) {
            $lines[] = "Сейчас стример играет в: {$context['game']}. Ты знаешь эту игру и можешь делать конкретные комментарии по ней.";
        }
        if (!empty($context['title'])) {
            $lines[] = "Название стрима: {$context['title']}";
        }

        // Характер из БД
        if ($type) {
            $lines[] = $type->system_prompt;
            $lines[] = $type->behavior_prompt;
        }

        // Verbosity
        if ($bot->verbosity < 0.3) {
            $lines[] = "Пиши максимально коротко — 1-4 слова.";
        } elseif ($bot->verbosity > 0.7) {
            $lines[] = "Можешь написать 1-2 полных предложения.";
        } else {
            $lines[] = "Пиши кратко — одна мысль, одно предложение.";
        }

        // Toxicity
        if ($bot->toxicity > 0.6) {
            $lines[] = "Можешь использовать лёгкие словечки типа 'блин', 'да ну нафиг', 'ну и чё', 'вот это провал'. Не матерись.";
        } elseif ($bot->toxicity > 0.3) {
            $lines[] = "Иногда можешь быть слегка пренебрежительным, но не грубым.";
        }

        // Knowledge
        if (!empty($bot->knowledge)) {
            $topics = implode(', ', (array)$bot->knowledge);
            $lines[] = "Хорошо разбираешься в: {$topics}. Можешь вставлять конкретные комментарии по теме.";
        }

        // Emoji
        if ($type) {
            $lines[] = $type->emoji_instruction;
        }

        // Дискуссия — иногда отвечаем на сообщение другого бота
        if (!empty($context['chat_history'])) {
            $lastMsg = end($context['chat_history']);
            if ($lastMsg && $lastMsg['author'] !== $bot->name && mt_rand(0, 2) === 0) {
                $lines[] = "Последнее сообщение в чате от {$lastMsg['author']}: \"{$lastMsg['message']}\". Можешь ответить именно на него (упомяни {$lastMsg['author']} или просто ответь по смыслу).";
            }
        }

        return implode("\n", $lines);
    }

    private function buildMessages(string $streamerText, array $context): array
    {
        $messages = [];

        // История чата для контекста
        if (!empty($context['chat_history'])) {
            $history = array_slice($context['chat_history'], -6); // последние 6 сообщений
            foreach ($history as $msg) {
                $messages[] = ['role' => 'user', 'content' => "[{$msg['author']}]: {$msg['message']}"];
            }
        }

        // Предыдущие сообщения бота
        if (!empty($context['bot_messages'])) {
            foreach ($context['bot_messages'] as $entry) {
                if ($entry['role'] === 'streamer') {
                    $messages[] = ['role' => 'user',      'content' => "[Стример]: {$entry['text']}"];
                } else {
                    $messages[] = ['role' => 'assistant', 'content' => "[{$entry['name']}]: {$entry['text']}"];
                }
            }
        }

        $messages[] = ['role' => 'user', 'content' => "[Стример]: {$streamerText}"];

        return $messages;
    }

    public function applyWritingStyle(Bot $bot, string $response): string
    {
        $response = trim($response);
        $response = preg_replace('/^\[.*?\]:\s*/u', '', $response);
        $response = trim($response, '"\'«»');

        $type  = $this->getType($bot->style ?? 'neutral');
        $style = $bot->style ?? 'neutral';

        switch ($style) {
            case 'silent':
                $response = $this->firstSentence($response);
                if (mt_rand(0, 4) === 0 && $type) {
                    $pool = array_merge($type->emotes ?? [], $type->emoji ?? []);
                    if (!empty($pool)) $response = $pool[array_rand($pool)];
                }
                break;

            case 'hype':
                if (mt_rand(0, 2) === 0) {
                    $response = mb_strtoupper($response);
                }
                if (!$this->hasEmoji($response) && $type) {
                    $pool = array_merge($type->emotes ?? [], $type->emoji ?? []);
                    if (!empty($pool)) $response .= ' ' . $pool[array_rand($pool)];
                }
                break;

            case 'memer':
                if (!$this->hasEmoji($response) && mt_rand(0, 1) && $type) {
                    $pool = array_merge($type->emotes ?? [], $type->emoji ?? []);
                    if (!empty($pool)) $response .= ' ' . $pool[array_rand($pool)];
                }
                break;

            case 'toxic':
            case 'hater':
                if (!$this->hasEmoji($response) && mt_rand(0, 2) === 0 && $type) {
                    $pool = $type->emoji ?? [];
                    if (!empty($pool)) $response .= ' ' . $pool[array_rand($pool)];
                }
                break;
        }

        if (in_array($style, ['toxic', 'noob', 'hater']) && mt_rand(0, 4) === 0) {
            $response = $this->addTypo($response);
        }

        return $response;
    }

    private function getType(string $style): ?BotType
    {
        return Cache::remember("bot_type:{$style}", 300, function () use ($style) {
            return BotType::where('name', $style)->where('is_active', true)->first();
        });
    }

    private function firstSentence(string $text): string
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', $text);
        return $parts[0] ?? $text;
    }

    private function hasEmoji(string $text): bool
    {
        $emoteWords = ['KEKW', 'Pog', 'PogChamp', 'LUL', 'monka', 'OMEGALUL', 'pepeLaugh', 'peepo', 'HYPERS', 'EZ', 'Clap'];
        foreach ($emoteWords as $emote) {
            if (str_contains($text, $emote)) return true;
        }
        return preg_match('/[\x{1F000}-\x{1FFFF}]|[\x{2600}-\x{27BF}]/u', $text) === 1;
    }

    private function addTypo(string $text): string
    {
        $words = explode(' ', $text);
        if (count($words) < 2) return $text;
        $idx  = array_rand($words);
        $word = $words[$idx];
        if (mb_strlen($word) > 3) {
            if (mt_rand(0, 1)) {
                $words[$idx] = mb_substr($word, 0, mb_strlen($word) - 1);
            } else {
                $words[$idx] = str_replace(['е', 'и'], ['и', 'е'], $word);
            }
        }
        return implode(' ', $words);
    }

    public function getEmotePool(string $category): array  { return []; }
    public function getEmojiPool(string $category): array  { return []; }
    public function getRuWordsPool(string $category): array { return []; }
}
