<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'show_participants_publicly')) {
                $table->boolean('show_participants_publicly')->default(true)->after('has_live_result');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'show_participants_publicly')) {
                $table->dropColumn('show_participants_publicly');
            }
        });
    }
};
