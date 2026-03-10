<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->nullable()->constrained('organizers')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->string('category')->default('umur');
            $table->text('description')->nullable();
            $table->foreignId('racing_committee_id')->nullable()->constrained('racing_committees')->nullOnDelete();
            $table->foreignId('master_of_ceremony_id')->nullable()->constrained('master_of_ceremonies')->nullOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('poster')->nullable();
            $table->string('status')->default('draft');
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
