<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bots', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->string('style')->nullable();

            $table->json('knowledge')->nullable();

            $table->float('toxicity')->default(0);

            $table->float('verbosity')->default(0.5);

            $table->integer('weight')->default(10);

            $table->timestamp('cooldown_until')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};