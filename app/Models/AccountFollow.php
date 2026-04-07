<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountFollow extends Model
{
    protected $table = 'account_follows';

    protected $fillable = ['account_id', 'channel', 'channel_id', 'followed_at'];

    protected $casts = ['followed_at' => 'datetime'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
