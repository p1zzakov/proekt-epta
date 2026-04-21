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
    ];
    protected $casts = [
        'knowledge'      => 'array',
        'cooldown_until' => 'datetime',
        'is_active'      => 'boolean',
    ];
}