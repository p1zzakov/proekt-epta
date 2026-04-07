<?php

namespace App\Jobs;

use App\Models\Broadcast;
use App\Models\Client;
use App\Models\ClientNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(private int $broadcastId) {}

    public function handle(): void
    {
        $broadcast = Broadcast::find($this->broadcastId);
        if (!$broadcast) return;

        $broadcast->status = 'sending';
        $broadcast->save();

        $recipients = $broadcast->getRecipients();
        $broadcast->total_recipients = $recipients->count();
        $broadcast->save();

        $sent   = 0;
        $failed = 0;

        foreach ($recipients as $client) {
            try {
                // Push уведомление в ЛК
                if ($broadcast->send_push) {
                    ClientNotification::create([
                        'client_id'    => $client->id,
                        'broadcast_id' => $broadcast->id,
                        'title'        => $broadcast->title,
                        'message'      => $broadcast->message,
                    ]);
                }

                // Email
                if ($broadcast->send_email && $client->email) {
                    $this->sendEmail($client, $broadcast);
                }

                // Telegram
                if ($broadcast->send_telegram && $client->telegram) {
                    $this->sendTelegram($client, $broadcast);
                }

                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Broadcast send failed', [
                    'broadcast' => $broadcast->id,
                    'client'    => $client->id,
                    'error'     => $e->getMessage(),
                ]);
            }

            // Небольшая пауза чтобы не спамить
            usleep(100000); // 0.1 сек
        }

        $broadcast->update([
            'status'       => 'done',
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'sent_at'      => now(),
        ]);

        Log::info('Broadcast completed', [
            'id'     => $broadcast->id,
            'sent'   => $sent,
            'failed' => $failed,
        ]);
    }

    private function sendEmail(Client $client, Broadcast $broadcast): void
    {
        Mail::send([], [], function ($mail) use ($client, $broadcast) {
            $mail->to($client->email, $client->name)
                 ->subject($broadcast->title)
                 ->html("
                    <div style='font-family:monospace;background:#0a0a0f;color:#e2e8f0;padding:24px;border-radius:8px;max-width:600px;'>
                        <div style='font-size:20px;font-weight:bold;color:#a855f7;margin-bottom:16px;'>ViewLab</div>
                        <div style='font-size:16px;font-weight:bold;margin-bottom:12px;'>{$broadcast->title}</div>
                        <div style='color:#9ca3af;line-height:1.6;'>{$broadcast->message}</div>
                        <div style='margin-top:24px;padding-top:16px;border-top:1px solid #2a2a3a;font-size:11px;color:#6b7280;'>
                            viewlab.top — Bot Engine
                        </div>
                    </div>
                 ");
        });
    }

    private function sendTelegram(Client $client, Broadcast $broadcast): void
    {
        $botToken = config('bot.telegram_bot_token');
        if (!$botToken) return;

        $chatId = $client->telegram;
        // Убираем @ если есть
        $chatId = ltrim($chatId, '@');

        $text = "*{$broadcast->title}*\n\n{$broadcast->message}";

        Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
