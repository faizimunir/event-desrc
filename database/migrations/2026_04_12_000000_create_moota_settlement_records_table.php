<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moota_settlement_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('moota_webhook_event_id')->nullable();
            $table->string('mutation_id')->unique();
            $table->string('type', 8)->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('order_code')->nullable();
            $table->string('account_number')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->index(['order_code', 'created_at']);
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moota_settlement_records');
    }
};
