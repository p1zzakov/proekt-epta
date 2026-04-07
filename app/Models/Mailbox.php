<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mailbox extends Model
{
    protected $fillable = [
        'email', 'name', 'password_hash', 'is_active',
        'messages_count', 'last_login_at', 'note',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
    ];

    protected $hidden = ['password_hash'];

    public function getDisplayNameAttribute(): string
    {
        return $this->name ? "{$this->name} <{$this->email}>" : $this->email;
    }

    public function getLocalPartAttribute(): string
    {
        return explode('@', $this->email)[0];
    }

    public function getDomainAttribute(): string
    {
        return explode('@', $this->email)[1] ?? 'viewlab.top';
    }
}
