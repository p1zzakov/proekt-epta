<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $table = 'broadcasts';

    protected $fillable = [
        'title', 'message',
        'send_email', 'send_telegram', 'send_push',
        'audience', 'audience_plan', 'audience_status', 'audience_ids',
        'status', 'total_recipients', 'sent_count', 'failed_count',
        'sent_at', 'created_by',
    ];

    protected $casts = [
        'send_email'    => 'boolean',
        'send_telegram' => 'boolean',
        'send_push'     => 'boolean',
        'audience_ids'  => 'array',
        'sent_at'       => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getChannelsAttribute(): string
    {
        $channels = [];
        if ($this->send_email)    $channels[] = '✉️ Email';
        if ($this->send_telegram) $channels[] = '✈️ Telegram';
        if ($this->send_push)     $channels[] = '🔔 Push';
        return implode(', ', $channels) ?: '—';
    }

    public function getAudienceLabelAttribute(): string
    {
        return match($this->audience) {
            'all'    => '👥 Все клиенты',
            'plan'   => "⭐ Тариф: {$this->audience_plan}",
            'status' => "🔘 Статус: {$this->audience_status}",
            'manual' => '✋ Выбранные вручную (' . count($this->audience_ids ?? []) . ')',
            default  => $this->audience,
        };
    }

    // Получаем список клиентов для рассылки
    public function getRecipients()
    {
        $query = Client::where('is_active', true);

        return match($this->audience) {
            'plan'   => $query->where('plan', $this->audience_plan)->get(),
            'status' => $query->where('status', $this->audience_status)->get(),
            'manual' => $query->whereIn('id', $this->audience_ids ?? [])->get(),
            default  => $query->get(), // all
        };
    }
}
