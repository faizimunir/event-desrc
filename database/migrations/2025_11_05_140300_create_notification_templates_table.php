<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('cascade');
            $table->enum('type', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->enum('channel', ['email', 'whatsapp', 'both'])->default('both');
            $table->string('subject')->nullable(); // For email
            $table->text('content');
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};

