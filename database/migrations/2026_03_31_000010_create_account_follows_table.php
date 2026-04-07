<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->string('channel');        // логин канала
            $table->string('channel_id');     // twitch ID канала
            $table->timestamp('followed_at');
            $table->timestamps();

            $table->unique(['account_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_follows');
    }
};
