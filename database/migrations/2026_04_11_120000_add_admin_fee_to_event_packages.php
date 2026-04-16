<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_packages', function (Blueprint $table) {
            $table->decimal('admin_fee', 12, 2)->default(0)->after('price');
            $table->boolean('admin_fee_included_in_price')->default(true)->after('admin_fee');
        });
    }

    public function down(): void
    {
        Schema::table('event_packages', function (Blueprint $table) {
            $table->dropColumn(['admin_fee', 'admin_fee_included_in_price']);
        });
    }
};
