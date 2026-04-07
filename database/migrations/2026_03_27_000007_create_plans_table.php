<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2);           // цена за единицу периода

            // Тип периода
            $table->enum('billing_period', [
                'hour',    // за час
                'day',     // за день
                'week',    // за неделю
                'month',   // за месяц
                'stream',  // за стрим (разовая)
            ])->default('stream');

            // Мин/макс единиц которые может купить клиент
            $table->integer('min_units')->default(1);
            $table->integer('max_units')->default(1);  // 0 = без лимита

            $table->string('description')->nullable();
            $table->json('features');
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('button_text')->default('Начать');
            $table->string('badge')->nullable();

            // Режим ботов
            $table->enum('bot_mode', ['viewers', 'manual', 'ai'])->default('viewers');

            // Лимиты
            $table->integer('max_viewers')->default(0);
            $table->integer('max_bots')->default(0);
            $table->integer('max_streams')->default(1);
            $table->integer('stream_duration')->default(4); // часов на 1 стрим

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};