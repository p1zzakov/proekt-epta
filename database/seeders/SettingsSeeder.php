<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Ollama ──
            ['key' => 'ollama_url',     'value' => env('OLLAMA_URL', 'http://host.docker.internal:11434'), 'type' => 'string',  'group' => 'ollama',   'label' => 'Ollama URL',          'description' => 'Адрес сервера Ollama'],
            ['key' => 'ollama_model',   'value' => env('OLLAMA_MODEL', 'qwen2.5:7b'),                      'type' => 'string',  'group' => 'ollama',   'label' => 'Модель',               'description' => 'Название модели (qwen2.5:7b, llama3, mistral...)'],
            ['key' => 'ollama_timeout', 'value' => env('OLLAMA_TIMEOUT', '30'),                            'type' => 'integer', 'group' => 'ollama',   'label' => 'Таймаут (сек)',        'description' => 'Максимальное время ожидания ответа от Ollama'],

            // ── Bot Engine ──
            ['key' => 'bot_delay_min',    'value' => env('BOT_DELAY_MIN', '8'),    'type' => 'integer', 'group' => 'bot', 'label' => 'Мин. задержка ответа (сек)',  'description' => 'Минимальная задержка перед ответом бота'],
            ['key' => 'bot_delay_max',    'value' => env('BOT_DELAY_MAX', '45'),   'type' => 'integer', 'group' => 'bot', 'label' => 'Макс. задержка ответа (сек)', 'description' => 'Максимальная задержка перед ответом бота'],
            ['key' => 'bot_cooldown_min', 'value' => env('BOT_COOLDOWN_MIN', '30'),'type' => 'integer', 'group' => 'bot', 'label' => 'Мин. кулдаун бота (сек)',     'description' => 'Минимальный кулдаун после ответа бота'],
            ['key' => 'bot_cooldown_max', 'value' => env('BOT_COOLDOWN_MAX', '120'),'type'=>'integer',  'group' => 'bot', 'label' => 'Макс. кулдаун бота (сек)',    'description' => 'Максимальный кулдаун после ответа бота'],
            ['key' => 'account_cooldown_min', 'value' => env('ACCOUNT_COOLDOWN_MIN', '60'), 'type' => 'integer', 'group' => 'bot', 'label' => 'Мин. кулдаун аккаунта (сек)', 'description' => 'Минимальная пауза между сообщениями одного аккаунта'],
            ['key' => 'account_cooldown_max', 'value' => env('ACCOUNT_COOLDOWN_MAX', '180'),'type' => 'integer', 'group' => 'bot', 'label' => 'Макс. кулдаун аккаунта (сек)', 'description' => 'Максимальная пауза между сообщениями одного аккаунта'],

            // ── Twitch ──
            ['key' => 'twitch_client_id',     'value' => env('TWITCH_CLIENT_ID', ''),     'type' => 'string', 'group' => 'twitch', 'label' => 'Twitch Client ID',     'description' => 'Client ID из dev.twitch.tv'],
            ['key' => 'twitch_client_secret', 'value' => env('TWITCH_CLIENT_SECRET', ''), 'type' => 'string', 'group' => 'twitch', 'label' => 'Twitch Client Secret', 'description' => 'Client Secret из dev.twitch.tv'],

            // ── Telegram ──
            ['key' => 'telegram_bot_token', 'value' => env('TELEGRAM_BOT_TOKEN', ''), 'type' => 'string', 'group' => 'telegram', 'label' => 'Telegram Bot Token', 'description' => 'Токен бота из @BotFather'],

            // ── Общие ──
            ['key' => 'app_name',        'value' => 'ViewLab',        'type' => 'string',  'group' => 'general', 'label' => 'Название сервиса',    'description' => 'Отображается в интерфейсе и письмах'],
            ['key' => 'support_email',   'value' => '',               'type' => 'string',  'group' => 'general', 'label' => 'Email поддержки',     'description' => 'Куда пишут клиенты'],
            ['key' => 'maintenance_mode','value' => '0',              'type' => 'boolean', 'group' => 'general', 'label' => 'Режим обслуживания',  'description' => 'Закрыть ЛК для клиентов'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }

        $this->command->info('✅ Настройки созданы: ' . count($settings));
    }
}
