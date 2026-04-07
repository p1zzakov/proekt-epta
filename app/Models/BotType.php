<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotType extends Model
{
    protected $table = 'bot_types';

    protected $fillable = [
        'name',
        'label',
        'system_prompt',
        'behavior_prompt',
        'emoji_instruction',
        'emotes',
        'emoji',
        'ru_words',
        'is_active',
    ];

    protected $casts = [
        'emotes'    => 'array',
        'emoji'     => 'array',
        'ru_words'  => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Боты этого типа
    public function bots()
    {
        return $this->hasMany(Bot::class, 'style', 'name');
    }
}
