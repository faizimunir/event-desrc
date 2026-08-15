<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_rundowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('title')->nullable()->comment('Optional label e.g. ISHOMA; when empty, bracket names are shown');
            $table->timestamps();

            $table->index(['event_id', 'start_time']);
        });

        Schema::create('event_rundown_bracket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_rundown_id')->constrained('event_rundowns')->cascadeOnDelete();
            $table->foreignId('event_bracket_id')->constrained('event_brackets')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_rundown_id', 'event_bracket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rundown_bracket');
        Schema::dropIfExists('event_rundowns');
    }
};
