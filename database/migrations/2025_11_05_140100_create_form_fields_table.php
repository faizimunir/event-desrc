<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            $table->string('name');
            $table->string('label');
            $table->enum('type', ['text', 'textarea', 'email', 'tel', 'date', 'select', 'checkbox', 'radio', 'number'])->default('text');
            $table->text('options')->nullable(); // JSON for select/radio options
            $table->text('help_text')->nullable();
            $table->boolean('required')->default(false);
            $table->integer('order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};

