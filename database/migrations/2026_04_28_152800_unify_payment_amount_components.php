<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('admin_fee_amount', 14, 2)->default(0)->after('amount');
            $table->unsignedTinyInteger('unique_code')->nullable()->after('admin_fee_amount');
            $table->decimal('transfer_amount', 14, 2)->nullable()->after('unique_code');
            $table->index('transfer_amount');
        });

        Payment::query()
            ->with('registration.package')
            ->orderBy('id')
            ->chunkById(200, function ($payments): void {
                foreach ($payments as $payment) {
                    $package = $payment->registration?->package;
                    $baseAmount = $package ? (float) $package->price : (float) $payment->amount;
                    $adminFeeAmount = 0.0;
                    if ($package && ! $package->adminFeeIsIncludedInPrice()) {
                        $adminFeeAmount = (float) $package->admin_fee;
                    }

                    if ($payment->method === Payment::METHOD_MANUAL && $payment->manual_transfer_amount !== null) {
                        $transferAmount = (float) $payment->manual_transfer_amount;
                    } elseif ($payment->transfer_amount !== null) {
                        $transferAmount = (float) $payment->transfer_amount;
                    } else {
                        $transferAmount = (float) $payment->amount;
                    }

                    $uniqueCode = (int) round($transferAmount - ($baseAmount + $adminFeeAmount));
                    if ($uniqueCode < Payment::MANUAL_UNIQUE_SUFFIX_MIN || $uniqueCode > Payment::MANUAL_UNIQUE_SUFFIX_MAX) {
                        $uniqueCode = null;
                    }

                    $payment->forceFill([
                        'amount' => round($baseAmount, 2),
                        'admin_fee_amount' => round($adminFeeAmount, 2),
                        'unique_code' => $uniqueCode,
                        'transfer_amount' => round($transferAmount, 2),
                        // Keep compatibility with existing manual UI while transitioning.
                        'manual_transfer_amount' => $payment->method === Payment::METHOD_MANUAL
                            ? round($transferAmount, 2)
                            : $payment->manual_transfer_amount,
                    ])->save();
                }
            });
    }

    public function down(): void
    {
        Payment::query()
            ->where('method', Payment::METHOD_QRIS)
            ->whereNotNull('transfer_amount')
            ->orderBy('id')
            ->chunkById(200, function ($payments): void {
                foreach ($payments as $payment) {
                    $payment->forceFill([
                        'amount' => (float) $payment->transfer_amount,
                    ])->save();
                }
            });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['transfer_amount']);
            $table->dropColumn(['admin_fee_amount', 'unique_code', 'transfer_amount']);
        });
    }
};
