<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Winpay tidak punya tabel dedicated di codebase ini; data ada di kolom payments.winpay_*.
     * Migration ini membersihkan DB yang pernah menjalankan migration Winpay (atau tabel custom bernama winpay).
     */
    public function up(): void
    {
        Schema::dropIfExists('winpay');

        if (! Schema::hasTable('payments')) {
            return;
        }

        if (Schema::hasColumn('payments', 'method')) {
            DB::table('payments')
                ->whereIn('method', ['winpay_va', 'winpay_qris', 'winpay_ewallet'])
                ->update(['method' => 'manual']);
        }

        $columns = [
            'winpay_channel',
            'winpay_trx_id',
            'winpay_external_id',
            'winpay_contract_id',
            'winpay_partner_service_id',
            'winpay_customer_no',
            'winpay_virtual_account_no',
            'winpay_qr_url',
            'winpay_qr_content',
            'winpay_app_redirect_url',
            'winpay_web_redirect_url',
            'winpay_partner_reference_no',
            'winpay_expired_at',
            'winpay_payment_request_id',
            'winpay_reference_no',
            'winpay_raw',
        ];

        $toDrop = array_values(array_filter(
            $columns,
            fn (string $col) => Schema::hasColumn('payments', $col),
        ));

        if ($toDrop !== []) {
            Schema::table('payments', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn($toDrop);
            });
        }
    }

    public function down(): void
    {
        // Winpay dihapus dari aplikasi; tidak mengembalikan kolom/tabel.
    }
};
