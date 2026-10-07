<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'uses_system_registration')) {
                $table->boolean('uses_system_registration')
                    ->default(true)
                    ->after('show_participants_publicly');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'uses_system_registration')) {
                $table->dropColumn('uses_system_registration');
            }
        });
    }
};
