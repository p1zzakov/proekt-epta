<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();

            $table->string('title');                // Тема / заголовок
            $table->text('message');                // Текст сообщения

            // Каналы отправки
            $table->boolean('send_email')->default(false);
            $table->boolean('send_telegram')->default(false);
            $table->boolean('send_push')->default(false);

            // Аудитория
            $table->enum('audience', ['all', 'plan', 'status', 'manual'])->default('all');
            $table->string('audience_plan')->nullable();    // free/basic/pro/enterprise
            $table->string('audience_status')->nullable();  // active/suspended/banned
            $table->json('audience_ids')->nullable();       // массив client_id для manual

            // Статус рассылки
            $table->enum('status', ['draft', 'sending', 'done', 'failed'])->default('draft');
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });

        // Push уведомления для клиентов
        Schema::create('client_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('broadcast_id')->nullable()->constrained('broadcasts')->onDelete('set null');
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_notifications');
        Schema::dropIfExists('broadcasts');
    }
};
