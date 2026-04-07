<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    protected $fillable = [
        'type', 'host', 'port', 'username', 'password',
        'is_active', 'status', 'fail_count',
        'last_checked_at', 'last_used_at', 'response_time_ms', 'note',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'last_checked_at' => 'datetime',
        'last_used_at'    => 'datetime',
    ];

    protected $hidden = ['password'];

    public function account()
    {
        return $this->hasOne(Account::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->where('status', 'available');
    }

    // Формат для использования в curl/guzzle
    // socks5://user:pass@host:port
    public function toUrl(): string
    {
        $scheme = $this->type;
        if ($this->username && $this->password) {
            return "{$scheme}://{$this->username}:{$this->password}@{$this->host}:{$this->port}";
        }
        return "{$scheme}://{$this->host}:{$this->port}";
    }

    // Парсим строку формата host:port:user:pass или user:pass@host:port
    public static function parseString(string $str, string $type = 'socks5'): ?array
    {
        $str = trim($str);
        if (empty($str)) return null;

        // Формат: socks5://user:pass@host:port
        if (preg_match('/^(https?|socks5):\/\/(.+):(.+)@(.+):(\d+)$/', $str, $m)) {
            return ['type' => $m[1], 'username' => $m[2], 'password' => $m[3], 'host' => $m[4], 'port' => (int)$m[5]];
        }

        // Формат: host:port:user:pass
        if (preg_match('/^([^:]+):(\d+):([^:]+):(.+)$/', $str, $m)) {
            return ['type' => $type, 'host' => $m[1], 'port' => (int)$m[2], 'username' => $m[3], 'password' => $m[4]];
        }

        // Формат: user:pass@host:port
        if (preg_match('/^([^:]+):([^@]+)@([^:]+):(\d+)$/', $str, $m)) {
            return ['type' => $type, 'username' => $m[1], 'password' => $m[2], 'host' => $m[3], 'port' => (int)$m[4]];
        }

        // Формат: host:port
        if (preg_match('/^([^:]+):(\d+)$/', $str, $m)) {
            return ['type' => $type, 'host' => $m[1], 'port' => (int)$m[2], 'username' => null, 'password' => null];
        }

        return null;
    }
}
