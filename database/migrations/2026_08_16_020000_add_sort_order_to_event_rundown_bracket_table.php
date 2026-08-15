<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rundown_bracket', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('event_bracket_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_rundown_bracket', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
