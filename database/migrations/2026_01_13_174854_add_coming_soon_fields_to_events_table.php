<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_coming_soon')->default(false)->after('end_date');
            $table->boolean('is_registration_coming_soon')->default(false)->after('registration_end');
            // Make date fields nullable to support coming soon events
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
            $table->dateTime('registration_start')->nullable()->change();
            $table->dateTime('registration_end')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['is_coming_soon', 'is_registration_coming_soon']);
            // Note: We don't revert nullable changes as it might cause data loss
        });
    }
};
