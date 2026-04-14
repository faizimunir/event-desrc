<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'winpay_qr_url')) {
                $table->string('winpay_qr_url', 1024)->nullable()->after('moota_raw');
            }
            if (! Schema::hasColumn('payments', 'winpay_qr_content')) {
                $table->text('winpay_qr_content')->nullable()->after('winpay_qr_url');
            }
            if (! Schema::hasColumn('payments', 'winpay_contract_id')) {
                $table->string('winpay_contract_id', 191)->nullable()->after('winpay_qr_content');
            }
            if (! Schema::hasColumn('payments', 'winpay_partner_reference_no')) {
                $table->string('winpay_partner_reference_no', 64)->nullable()->after('winpay_contract_id');
            }
            if (! Schema::hasColumn('payments', 'winpay_expired_at')) {
                $table->timestamp('winpay_expired_at')->nullable()->after('winpay_partner_reference_no');
            }
            if (! Schema::hasColumn('payments', 'winpay_external_id')) {
                $table->string('winpay_external_id', 64)->nullable()->after('winpay_expired_at');
            }
            if (! Schema::hasColumn('payments', 'winpay_raw')) {
                $table->json('winpay_raw')->nullable()->after('winpay_external_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        $cols = [
            'winpay_qr_url',
            'winpay_qr_content',
            'winpay_contract_id',
            'winpay_partner_reference_no',
            'winpay_expired_at',
            'winpay_external_id',
            'winpay_raw',
        ];

        $toDrop = array_values(array_filter(
            $cols,
            fn (string $c) => Schema::hasColumn('payments', $c),
        ));

        if ($toDrop !== []) {
            Schema::table('payments', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn($toDrop);
            });
        }
    }
};
