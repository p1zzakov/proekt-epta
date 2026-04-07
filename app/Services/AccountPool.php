<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountFollow;
use Illuminate\Support\Facades\Cache;

class AccountPool
{
    /**
     * Выдать аккаунты для канала с приоритетом по подпискам
     * 1. Уже подписаны на канал
     * 2. Ещё не подписаны
     */
    public function getForChannel(string $channel, int $count = 5): array
    {
        // Уже подписаны
        $followed = Account::where('status', 'available')
            ->where('is_active', true)
            ->whereHas('follows', fn($q) => $q->where('channel', $channel))
            ->inRandomOrder()
            ->limit($count)
            ->get();

        $needed = $count - $followed->count();

        // Добираем остальных
        $others = collect();
        if ($needed > 0) {
            $excludeIds = $followed->pluck('id')->toArray();
            $others = Account::where('status', 'available')
                ->where('is_active', true)
                ->whereNotIn('id', $excludeIds)
                ->whereDoesntHave('follows', fn($q) => $q->where('channel', $channel))
                ->inRandomOrder()
                ->limit($needed)
                ->get();
        }

        return $followed->merge($others)->all();
    }

    /**
     * Стандартный выбор одного аккаунта
     */
    public function acquire(): ?Account
    {
        return Account::where('status', 'available')
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();
    }

    public function release(Account $account, int $cooldownSeconds = 60): void
    {
        $account->status       = 'cooldown';
        $account->last_used_at = now();
        $account->save();

        \App\Jobs\ReleaseAccount::dispatch($account->id)
            ->delay(now()->addSeconds($cooldownSeconds));
    }

    public function stats(): array
    {
        return [
            'total'     => Account::count(),
            'available' => Account::where('status', 'available')->count(),
            'busy'      => Account::where('status', 'busy')->count(),
            'cooldown'  => Account::where('status', 'cooldown')->count(),
            'banned'    => Account::where('status', 'banned')->count(),
            'invalid'   => Account::where('status', 'invalid')->count(),
        ];
    }
}
