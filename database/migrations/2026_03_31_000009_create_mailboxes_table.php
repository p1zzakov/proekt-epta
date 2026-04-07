<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailboxes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();        // admin@viewlab.top
            $table->string('name')->nullable();       // Отображаемое имя
            $table->string('password_hash');          // SHA512-CRYPT хэш
            $table->boolean('is_active')->default(true);
            $table->integer('messages_count')->default(0);
            $table->timestamp('last_login_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailboxes');
    }
};
