<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->enum('payment_method', ['bank_transfer', 'e_wallet', 'cash'])->default('bank_transfer');
            $table->decimal('amount', 10, 2);
            $table->dateTime('payment_date')->nullable();
            $table->string('payment_proof')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'paid', 'verified', 'rejected', 'refunded'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
