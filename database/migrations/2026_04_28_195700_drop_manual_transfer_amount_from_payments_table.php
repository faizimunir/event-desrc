<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'manual_transfer_amount')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('manual_transfer_amount');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'manual_transfer_amount')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('manual_transfer_amount', 14, 2)->nullable()->after('manual_account_id');
        });
    }
};
