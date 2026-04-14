<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align order/payment string statuses with manual-transfer flow vocabulary:
     * orders: pending → unpaid, confirmed → paid; payments with proof: pending → submitted.
     */
    public function up(): void
    {
        DB::table('orders')->where('status', 'pending')->update(['status' => 'unpaid']);
        DB::table('orders')->where('status', 'confirmed')->update(['status' => 'paid']);

        DB::table('payments')
            ->where('status', 'pending')
            ->whereNotNull('transfer_proof_path')
            ->update(['status' => 'submitted']);
    }

    public function down(): void
    {
        DB::table('payments')->where('status', 'submitted')->update(['status' => 'pending']);

        DB::table('orders')->where('status', 'paid')->update(['status' => 'confirmed']);
        DB::table('orders')->where('status', 'unpaid')->update(['status' => 'pending']);
    }
};
