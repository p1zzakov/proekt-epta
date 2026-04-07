<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'slug', 'name', 'price', 'billing_period', 'min_units', 'max_units',
        'description', 'features', 'is_popular', 'is_active', 'sort_order',
        'button_text', 'badge', 'bot_mode',
        'max_viewers', 'max_bots', 'max_streams', 'stream_duration',
    ];

    protected $casts = [
        'features'        => 'array',
        'is_popular'      => 'boolean',
        'is_active'       => 'boolean',
        'price'           => 'decimal:2',
        'max_viewers'     => 'integer',
        'max_bots'        => 'integer',
        'max_streams'     => 'integer',
        'stream_duration' => 'integer',
        'min_units'       => 'integer',
        'max_units'       => 'integer',
    ];

    // Периоды
    public static array $periods = [
        'hour'   => ['label' => 'час',    'label_ru' => 'часов',   'short' => 'ч'],
        'day'    => ['label' => 'день',   'label_ru' => 'дней',    'short' => 'д'],
        'week'   => ['label' => 'неделя', 'label_ru' => 'недель',  'short' => 'нед'],
        'month'  => ['label' => 'месяц',  'label_ru' => 'месяцев', 'short' => 'мес'],
        'stream' => ['label' => 'стрим',  'label_ru' => 'стримов', 'short' => 'стрим'],
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function getPeriodLabel(): string
    {
        return self::$periods[$this->billing_period]['label'] ?? $this->billing_period;
    }

    public function getPeriodShort(): string
    {
        return self::$periods[$this->billing_period]['short'] ?? $this->billing_period;
    }

    public function getBotModeLabel(): string
    {
        return match($this->bot_mode) {
            'viewers' => '👁️ Только зрители',
            'manual'  => '🕹️ Зрители + ручной чат',
            'ai'      => '🧠 Зрители + AI чат',
            default   => $this->bot_mode,
        };
    }

    public function getMaxViewersLabel(): string
    {
        return $this->max_viewers === 0 ? '∞' : (string) $this->max_viewers;
    }

    public function getMaxBotsLabel(): string
    {
        return $this->max_bots === 0 ? '∞' : (string) $this->max_bots;
    }

    // Считаем итоговую сумму
    public function calculatePrice(int $units): float
    {
        return round($this->price * $units, 2);
    }
}