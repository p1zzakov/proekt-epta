<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ollama
    |--------------------------------------------------------------------------
    */
    'ollama_url'     => env('OLLAMA_URL', 'http://localhost:11434'),
    'ollama_model'   => env('OLLAMA_MODEL', 'qwen2.5:7b'),
    'ollama_timeout' => env('OLLAMA_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Bot Engine
    |--------------------------------------------------------------------------
    */
    'response_delay_min' => env('BOT_DELAY_MIN', 8),
    'response_delay_max' => env('BOT_DELAY_MAX', 45),
    'cooldown_min'       => env('BOT_COOLDOWN_MIN', 30),
    'cooldown_max'       => env('BOT_COOLDOWN_MAX', 120),

    /*
    |--------------------------------------------------------------------------
    | Twitch
    |--------------------------------------------------------------------------
    */
    'twitch_client_id'     => env('TWITCH_CLIENT_ID', ''),
    'twitch_client_secret' => env('TWITCH_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot
    |--------------------------------------------------------------------------
    */
    'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN', ''),

    // Cooldown между сообщениями одного аккаунта (секунды)
    'account_cooldown_min' => env('ACCOUNT_COOLDOWN_MIN', 60),
    'account_cooldown_max' => env('ACCOUNT_COOLDOWN_MAX', 180),
];