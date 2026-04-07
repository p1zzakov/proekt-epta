<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ChatListener extends Command
{
    protected $signature = 'chat:listen';
    protected $description = 'Listen to chat';

    public function handle()
    {
        $this->info("Listening...");

        $redis = new \Predis\Client([
            'scheme' => 'tcp',
            'host' => 'redis',
            'port' => 6379,
        ]);

        $pubsub = $redis->pubSubLoop();

        $pubsub->subscribe('stream_chat_1');

        foreach ($pubsub as $message) {

            if ($message->kind === 'message') {

                $data = json_decode($message->payload, true);

                if (!$data) {
                    continue;
                }

                $bot = $data['bot'] ?? 'unknown';
                $text = $data['message'] ?? '';

                echo "[CHAT] {$bot}: {$text}\n";
            }
        }
    }
}