<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('phone_verified')->default(false)->after('is_active');
        });

        Schema::create('channel_settings', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->unique();
            $table->string('channel_id')->nullable();
            $table->boolean('followers_only')->default(false);
            $table->integer('followers_only_minutes')->default(0);
            $table->boolean('requires_phone')->default(false);
            $table->boolean('subs_only')->default(false);
            $table->boolean('slow_mode')->default(false);
            $table->integer('slow_seconds')->default(0);
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('phone_verified');
        });
        Schema::dropIfExists('channel_settings');
    }
};
