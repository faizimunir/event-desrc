<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_bracket_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_bracket_id')->constrained('event_brackets')->cascadeOnDelete();
            $table->foreignId('event_level_id')->constrained('event_levels')->cascadeOnDelete();
            $table->string('name_original');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_bracket_levels');
    }
};
