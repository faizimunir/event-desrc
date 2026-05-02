<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sejajarkan dengan model deltae: metode = qris, kolom `amount` = total bayar (nominal + sufiks unik),
 * bukan memisah base vs moota_transfer_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Payment::query()->where('method', 'moota')->chunkById(200, function ($payments) {
            foreach ($payments as $p) {
                $p->update([
                    'method' => Payment::METHOD_QRIS,
                    'amount' => $p->moota_transfer_amount !== null
                        ? (float) $p->moota_transfer_amount
                        : (float) $p->amount,
                ]);
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('moota_transfer_amount');
        });

        Schema::dropIfExists('moota_settlement_records');
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('moota_transfer_amount', 14, 2)->nullable()->after('paid_at');
        });

        Payment::query()->where('method', Payment::METHOD_QRIS)->chunkById(200, function ($payments) {
            foreach ($payments as $p) {
                $p->update(['moota_transfer_amount' => $p->amount]);
            }
        });

        Payment::query()->where('method', Payment::METHOD_QRIS)->update(['method' => 'moota']);
    }
};
