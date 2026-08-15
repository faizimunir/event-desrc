<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_result_categories', function (Blueprint $table) {
            $table->foreignId('bracket_id')
                ->nullable()
                ->after('event_id')
                ->constrained('event_brackets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('live_result_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bracket_id');
        });
    }
};
