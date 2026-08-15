<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'live_result_layout')) {
                $table->string('live_result_layout', 20)
                    ->default('table')
                    ->after('has_live_result');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'live_result_layout')) {
                $table->dropColumn('live_result_layout');
            }
        });
    }
};
