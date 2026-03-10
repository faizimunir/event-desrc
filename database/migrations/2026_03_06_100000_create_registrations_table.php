<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bracket_id')->constrained('event_brackets')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('event_packages')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('number_plate')->nullable()->comment('Override per registration if needed');
            $table->timestamps();

            $table->unique(['event_id', 'rider_id', 'bracket_id'], 'registrations_event_rider_bracket_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
