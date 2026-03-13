<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_id', 32)->nullable()->unique()->after('id');
        });

        foreach (DB::table('orders')->get() as $order) {
            DB::table('orders')->where('id', $order->id)->update([
                'order_id' => 'ORD-'.Str::ulid(),
            ]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_id', 32)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_id');
        });
    }
};
