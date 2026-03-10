<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('gender_rule')->nullable()->comment('boys or girls');
            $table->string('rule_type')->nullable();
            $table->unsignedSmallInteger('birth_year_start')->nullable();
            $table->unsignedSmallInteger('birth_year_end')->nullable();
            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->date('age_ref_date')->nullable();
            $table->unsignedInteger('quota')->nullable()->comment('Max riders that can register for this bracket');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_brackets');
    }
};
