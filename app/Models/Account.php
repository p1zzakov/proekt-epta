<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'accounts';

    protected $fillable = [
        'username',
        'twitch_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'status',
        'last_used_at',
        'messages_sent',
        'messages_today',
        'messages_today_date',
        'rate_limit',
        'note',
        'proxy_id',
        'phone_verified',
        'type',
    ];

    protected $casts = [
        'token_expires_at'     => 'datetime',
        'last_used_at'         => 'datetime',
        'messages_today_date'  => 'date',
        'is_active'            => 'boolean',
        'phone_verified'       => 'boolean',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    // ─────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────

    public function follows()
    {
        return $this->hasMany(AccountFollow::class);
    }

    public function proxy()
    {
        return $this->belongsTo(Proxy::class);
    }

    // ─────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────

    /**
     * Аккаунты готовые к работе: активны, статус available,
     * и либо токен бессрочный, либо ещё не истёк.
     *
     * Исправлен баг: предыдущая версия использовала ->orWhere() на верхнем уровне,
     * что ломало SQL когда к scope добавлялись другие условия (AND/OR путались).
     */
    public function scopeAvailable($query)
    {
        return $query
            ->where('is_active', true)
            ->where('status', 'available')
            ->where(function ($q) {
                $q->whereNull('token_expires_at')
                  ->orWhere('token_expires_at', '>', now());
            });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->whereNotIn('status', ['banned', 'invalid']);
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    public function isFollowing(string $channel): bool
    {
        return $this->follows()->where('channel', $channel)->exists();
    }

    public function markFollowed(string $channel, string $channelId): void
    {
        $this->follows()->updateOrCreate(
            ['channel' => $channel],
            ['channel_id' => $channelId, 'followed_at' => now()]
        );
    }

    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) return false;
        return Carbon::now()->gte($this->token_expires_at);
    }

    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->status === 'available'
            && !$this->isTokenExpired();
    }

    public function markBusy(): void
    {
        $this->status = 'busy';
        $this->save();
    }

    public function markAvailable(): void
    {
        $this->status = 'available';
        $this->save();
    }

    public function markBanned(): void
    {
        $this->is_active = false;
        $this->status    = 'banned';
        $this->save();
    }

    public function markInvalid(): void
    {
        $this->is_active = false;
        $this->status    = 'invalid';
        $this->save();
    }

    public function incrementMessages(): void
    {
        $today = now()->toDateString();

        if ($this->messages_today_date?->toDateString() !== $today) {
            $this->messages_today      = 0;
            $this->messages_today_date = $today;
        }

        $this->messages_sent++;
        $this->messages_today++;
        $this->last_used_at = now();
        $this->save();
    }
}