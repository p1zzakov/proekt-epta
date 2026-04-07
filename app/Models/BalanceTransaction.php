<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceTransaction extends Model
{
    protected $table = 'balance_transactions';

    protected $fillable = [
        'client_id', 'type', 'amount', 'balance_after',
        'description', 'reference', 'created_by',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'deposit'  => '💰',
            'withdraw' => '💸',
            'bonus'    => '🎁',
            'refund'   => '↩️',
            default    => '•',
        };
    }
}
