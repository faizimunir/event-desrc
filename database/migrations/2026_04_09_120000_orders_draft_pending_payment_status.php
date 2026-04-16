<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status', 32)->nullable()->after('status');
        });

        // Legacy: pending_payment + belum konfirmasi → draft
        DB::table('orders')
            ->where('status', 'pending_payment')
            ->whereNull('confirmed_at')
            ->update(['status' => 'draft']);

        // Legacy: pending_payment + sudah konfirmasi → pending + unpaid
        DB::table('orders')
            ->where('status', 'pending_payment')
            ->whereNotNull('confirmed_at')
            ->update([
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

        DB::table('orders')
            ->where('status', 'paid')
            ->update(['payment_status' => 'paid']);
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'draft')->update(['status' => 'pending_payment']);
        DB::table('orders')->where('status', 'pending')->update(['status' => 'pending_payment']);
        DB::table('orders')->update(['payment_status' => null]);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
