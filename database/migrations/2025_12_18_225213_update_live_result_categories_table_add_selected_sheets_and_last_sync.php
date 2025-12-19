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
        Schema::table('live_result_categories', function (Blueprint $table) {
            $table->json('selected_sheets')->nullable()->after('spreadsheet_id');
            $table->timestamp('last_sync')->nullable()->after('selected_sheets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_result_categories', function (Blueprint $table) {
            $table->dropColumn(['selected_sheets', 'last_sync']);
        });
    }
};
