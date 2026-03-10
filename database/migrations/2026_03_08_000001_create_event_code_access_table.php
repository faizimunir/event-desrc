<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_code_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name')->nullable()->comment('e.g. Early Bird, Komunitas Internal');
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->unsignedInteger('usage_limit')->nullable()->comment('Max number of times this code can be used');
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_code_access');
    }
};
