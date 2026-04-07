<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Основные данные
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('telegram')->nullable();
            $table->string('twitch_channel')->nullable();

            // Баланс
            $table->decimal('balance', 10, 2)->default(0);

            // Тариф
            $table->string('plan')->default('free'); // free, basic, pro, enterprise
            $table->timestamp('plan_expires_at')->nullable();

            // Статус
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active');

            // Верификация email
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verify_token')->nullable();

            // Метаданные
            $table->string('remember_token')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->text('notes')->nullable(); // заметки админа

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
