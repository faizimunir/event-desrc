<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align payment status with gateway-style: pending, success, failed, expired, cancelled.
     * Migrate legacy values: approved → success, rejected → failed.
     */
    public function up(): void
    {
        DB::table('payments')
            ->where('status', 'approved')
            ->update(['status' => 'success']);

        DB::table('payments')
            ->where('status', 'rejected')
            ->update(['status' => 'failed']);
    }

    public function down(): void
    {
        DB::table('payments')
            ->where('status', 'success')
            ->update(['status' => 'approved']);

        DB::table('payments')
            ->where('status', 'failed')
            ->update(['status' => 'rejected']);
    }
};
