<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * checked_in_at used MySQL CURRENT_TIMESTAMP (often UTC) while created_at
     * was written by Laravel in the app timezone. Align historical rows.
     */
    public function up(): void
    {
        DB::table('event_checkin')
            ->whereColumn('checked_in_at', '!=', 'created_at')
            ->update([
                'checked_in_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        //
    }
};
