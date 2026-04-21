<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('cooldown_until');
            $table->unsignedBigInteger('account_id')->nullable()->after('is_active');
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn(['is_active', 'account_id']);
        });
    }
};
