<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxies', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['http', 'https', 'socks5'])->default('socks5');
            $table->string('host');
            $table->integer('port');
            $table->string('username')->nullable();
            $table->string('password')->nullable();

            // Статус
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['available', 'in_use', 'dead'])->default('available');

            // Статистика
            $table->integer('fail_count')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->integer('response_time_ms')->nullable();

            // Заметки
            $table->string('note')->nullable();

            $table->timestamps();
        });

        // Добавляем proxy_id в accounts
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('proxy_id')->nullable()->constrained('proxies')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['proxy_id']);
            $table->dropColumn('proxy_id');
        });
        Schema::dropIfExists('proxies');
    }
};
