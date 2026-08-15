<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rundowns', function (Blueprint $table) {
            $table->timestamp('actual_started_at')->nullable()->after('title');
            $table->timestamp('actual_ended_at')->nullable()->after('actual_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_rundowns', function (Blueprint $table) {
            $table->dropColumn(['actual_started_at', 'actual_ended_at']);
        });
    }
};
