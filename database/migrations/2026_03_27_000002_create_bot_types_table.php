<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_types', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();        // sarcastic, hype...
            $table->string('label');                 // 😏 Саркастик
            $table->text('system_prompt');           // основной промпт характера
            $table->text('behavior_prompt');         // поведенческие правила
            $table->text('emoji_instruction');       // инструкция по смайлам
            $table->json('emotes')->nullable();      // пул эмоутов
            $table->json('emoji')->nullable();       // пул эмодзи
            $table->json('ru_words')->nullable();    // русские слова/слэнг
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_types');
    }
};
