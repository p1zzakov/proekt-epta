<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description'];

    // ─────────────────────────────────────────
    // Получить значение по ключу
    // ─────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting:{$key}", 300, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) return $default;

        return match($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    // ─────────────────────────────────────────
    // Установить значение
    // ─────────────────────────────────────────

    public static function set(string $key, mixed $value): void
    {
        static::where('key', $key)->update(['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    // ─────────────────────────────────────────
    // Все настройки сгруппированные
    // ─────────────────────────────────────────

    public static function grouped(): array
    {
        return static::all()->groupBy('group')->toArray();
    }
}
