<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_package_reward', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_package_id')->constrained('event_packages')->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('rewards')->cascadeOnDelete();
            $table->string('photo_reward')->nullable();
            $table->timestamps();

            $table->unique(['event_package_id', 'reward_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_package_reward');
    }
};
