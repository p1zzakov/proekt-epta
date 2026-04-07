<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    protected $table = 'bots';
    protected $fillable = [
        'name',
        'style',
        'knowledge',
        'toxicity',
        'verbosity',
        'weight',
        'cooldown_until',
        'is_active',
        'account_id',
    ];
    protected $casts = [
        'knowledge'      => 'array',
        'cooldown_until' => 'datetime',
        'is_active'      => 'boolean',
    ];

    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
