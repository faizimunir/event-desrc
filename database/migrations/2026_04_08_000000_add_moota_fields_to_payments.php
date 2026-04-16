<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('expires_at');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('moota_transfer_amount', 14, 2)->nullable()->after('paid_at');
            $table->string('moota_mutation_id')->nullable()->after('moota_transfer_amount');
            $table->json('moota_raw')->nullable()->after('moota_mutation_id');

            $table->unique('moota_mutation_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['moota_mutation_id']);
            $table->dropColumn(['moota_transfer_amount', 'moota_mutation_id', 'moota_raw']);
        });
    }
};
