<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('moota_webhook_events');
    }

    public function down(): void
    {
        Schema::create('moota_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('signature')->nullable();
            $table->string('moota_user')->nullable();
            $table->string('moota_webhook')->nullable();
            $table->json('headers')->nullable();
            $table->longText('raw_body');
            $table->json('payload')->nullable();
            $table->string('status')->default('received');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }
};
