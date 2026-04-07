<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{
    use Notifiable;

    protected $table = 'clients';

    protected $fillable = [
        'name', 'email', 'password', 'telegram', 'twitch_channel',
        'balance', 'plan', 'plan_expires_at', 'is_active', 'status',
        'email_verified_at', 'email_verify_token', 'last_login_at',
        'last_login_ip', 'notes',
    ];

    protected $hidden = ['password', 'remember_token', 'email_verify_token'];

    protected $casts = [
        'balance'          => 'decimal:2',
        'plan_expires_at'  => 'datetime',
        'email_verified_at'=> 'datetime',
        'last_login_at'    => 'datetime',
        'is_active'        => 'boolean',
    ];

    // ─────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────

    public function transactions()
    {
        return $this->hasMany(BalanceTransaction::class);
    }

    // ─────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    public function scopeWithActivePlan($query)
    {
        return $query->where(function($q) {
            $q->where('plan', '!=', 'free')
              ->where(function($q2) {
                  $q2->whereNull('plan_expires_at')
                     ->orWhere('plan_expires_at', '>', now());
              });
        });
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    public function isPlanActive(): bool
    {
        if ($this->plan === 'free') return false;
        if (!$this->plan_expires_at) return true;
        return $this->plan_expires_at->isFuture();
    }

    public function deposit(float $amount, string $description = '', string $reference = '', ?int $adminId = null): BalanceTransaction
    {
        $this->balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type'          => 'deposit',
            'amount'        => $amount,
            'balance_after' => $this->balance,
            'description'   => $description,
            'reference'     => $reference,
            'created_by'    => $adminId,
        ]);
    }

    public function withdraw(float $amount, string $description = ''): ?BalanceTransaction
    {
        if ($this->balance < $amount) return null;

        $this->balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'type'          => 'withdraw',
            'amount'        => $amount,
            'balance_after' => $this->balance,
            'description'   => $description,
        ]);
    }

    public function addBonus(float $amount, string $description = '', ?int $adminId = null): BalanceTransaction
    {
        $this->balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type'          => 'bonus',
            'amount'        => $amount,
            'balance_after' => $this->balance,
            'description'   => $description ?: 'Бонус от администратора',
            'created_by'    => $adminId,
        ]);
    }

    public function getPlanLabelAttribute(): string
    {
        return match($this->plan) {
            'free'       => '🆓 Free',
            'basic'      => '⭐ Basic',
            'pro'        => '🔥 Pro',
            'enterprise' => '💎 Enterprise',
            default      => $this->plan,
        };
    }
}
