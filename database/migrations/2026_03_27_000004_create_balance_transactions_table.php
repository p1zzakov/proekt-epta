<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');

            // Тип операции
            $table->enum('type', [
                'deposit',   // пополнение
                'withdraw',  // списание
                'bonus',     // бонус от админа
                'refund',    // возврат
            ]);

            $table->decimal('amount', 10, 2);         // сумма операции
            $table->decimal('balance_after', 10, 2);  // баланс после операции
            $table->string('description')->nullable(); // описание
            $table->string('reference')->nullable();   // номер платежа / ID транзакции

            // Кто провёл (если вручную)
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
    }
};
