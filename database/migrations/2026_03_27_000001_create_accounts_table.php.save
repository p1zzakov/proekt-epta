<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            // Twitch данные
            $table->string('username')->unique();
            $table->string('twitch_id')->nullable()->unique();

            // Токены
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Статус
            $table->boolean('is_active')->default(true);
            $table->enum('status', [
                'available',  // готов к работе
                'busy',       // сейчас пишет сообщение
                'cooldown',   // отдыхает после сообщения
                'banned',     // забанен на Twitch
                'invalid',    // токен невалиден
            ])->default('available');

            // Использование
            $table->timestamp('last_used_at')->nullable();
            $table->integer('messages_sent')->default(0);
            $table->integer('messages_today')->default(0);
            $table->date('messages_today_date')->nullable();

            // Лимиты (Twitch: 20 сообщений/30 сек для обычных, 100 для верифицированных)
            $table->integer('rate_limit')->default(20);

            // Заметки (например: "куплен у ...", "фарм акк #3")
            $table->string('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
