<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_admin_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->enum('fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('fee_percentage', 5, 2)->nullable(); // If percentage
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_admin_fees');
    }
};

