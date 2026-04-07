<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelSettings extends Model
{
    protected $table = 'channel_settings';

    protected $fillable = [
        'channel', 'channel_id', 'followers_only', 'followers_only_minutes',
        'requires_phone', 'subs_only', 'slow_mode', 'slow_seconds', 'checked_at',
    ];

    protected $casts = [
        'followers_only' => 'boolean',
        'requires_phone' => 'boolean',
        'subs_only'      => 'boolean',
        'slow_mode'      => 'boolean',
        'checked_at'     => 'datetime',
    ];

    public function needsChatVerification(): bool
    {
        return $this->followers_only || $this->requires_phone;
    }

    public function getWarnings(): array
    {
        $warnings = [];
        if ($this->followers_only) {
            $mins = $this->followers_only_minutes;
            $warnings[] = $mins > 0
                ? "Чат только для фолловеров от {$mins} мин."
                : "Чат только для фолловеров";
        }
        if ($this->requires_phone) {
            $warnings[] = "Требуется верификация телефона";
        }
        if ($this->subs_only) {
            $warnings[] = "Чат только для подписчиков";
        }
        if ($this->slow_mode) {
            $warnings[] = "Slow mode: {$this->slow_seconds} сек.";
        }
        return $warnings;
    }
}
