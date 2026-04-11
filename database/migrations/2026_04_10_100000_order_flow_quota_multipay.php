<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['registration_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['registration_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('registration_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('registration_id')->references('id')->on('registrations')->cascadeOnDelete();
        });

        foreach (DB::table('payments')->whereNull('order_id')->cursor() as $row) {
            $orderId = DB::table('orders')->where('registration_id', $row->registration_id)->value('id');
            if ($orderId) {
                DB::table('payments')->where('id', $row->id)->update(['order_id' => $orderId]);
            }
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('confirmed_at');
        });

        DB::table('orders')->where('status', 'paid')->update([
            'status' => 'confirmed',
            'paid_at' => DB::raw('COALESCE(paid_at, updated_at, created_at)'),
        ]);

        DB::table('orders')->where('status', 'expired')->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'confirmed')->update(['status' => 'paid', 'paid_at' => null]);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['registration_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unique('registration_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('registration_id')->references('id')->on('registrations')->cascadeOnDelete();
        });
    }
};
