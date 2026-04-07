<?php

namespace App\Services;

use App\Models\Bot;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BotSelector
{

    public function select(string $streamText): ?Bot
    {

        $bots = Bot::all();

        if ($bots->isEmpty()) {
            return null;
        }

        $eligibleBots = [];

        foreach ($bots as $bot) {

            if ($this->isOnCooldown($bot)) {
                continue;
            }

            if (!$this->passesVerbosity($bot)) {
                continue;
            }

            if ($this->isRelevant($bot, $streamText)) {
                $eligibleBots[] = $bot;
            }
        }

        if (!empty($eligibleBots)) {
            return $this->weightedRandom($eligibleBots);
        }

        // fallback если нет релевантных ботов

        $fallbackBots = [];

        foreach ($bots as $bot) {

            if ($this->isOnCooldown($bot)) {
                continue;
            }

            if (!$this->passesVerbosity($bot)) {
                continue;
            }

            $fallbackBots[] = $bot;
        }

        if (!empty($fallbackBots)) {
            return $this->weightedRandom($fallbackBots);
        }

        return null;
    }



    private function isOnCooldown(Bot $bot): bool
    {

        if (!$bot->cooldown_until) {
            return false;
        }

        return Carbon::now()->lt($bot->cooldown_until);

    }



    private function passesVerbosity(Bot $bot): bool
    {

        $chance = $bot->verbosity;

        $rand = mt_rand(0,100) / 100;

        return $rand <= $chance;

    }



    private function isRelevant(Bot $bot, string $text): bool
    {

        if (!$bot->knowledge) {
            return true;
        }

        $text = Str::lower($text);

        foreach ($bot->knowledge as $topic) {

            if (Str::contains($text, Str::lower($topic))) {
                return true;
            }

        }

        return false;

    }



    private function weightedRandom(array $bots): Bot
    {

        $totalWeight = 0;

        foreach ($bots as $bot) {
            $totalWeight += $bot->weight;
        }

        $rand = mt_rand(1, $totalWeight);

        $current = 0;

        foreach ($bots as $bot) {

            $current += $bot->weight;

            if ($rand <= $current) {
                return $bot;
            }

        }

        return $bots[0];

    }



    public function applyCooldown(Bot $bot): void
    {

        $seconds = rand(30,120);

        $bot->cooldown_until = now()->addSeconds($seconds);

        $bot->save();

    }

}