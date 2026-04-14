<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ExpirePendingOrdersCommand extends Command
{
    protected $signature = 'orders:enforce-deadlines';

    protected $description = 'Batalkan draft lewat batas; batalkan unpaid lewat expired_at hanya jika payment masih pending tanpa submitted (kuota lepas; registrasi payment-expired jadi cancelled)';

    public function handle(): int
    {
        $draftCount = 0;
        Order::query()->expiredDraft()->with('registration')->chunkById(100, function ($orders) use (&$draftCount) {
            foreach ($orders as $order) {
                if ($order->enforceExpiredDraftIfNeeded()) {
                    $draftCount++;
                }
            }
        });

        $pendingCount = 0;
        Order::query()->expiredPendingUnpaid()->with('registration')->chunkById(100, function ($orders) use (&$pendingCount) {
            foreach ($orders as $order) {
                if ($order->enforceExpiredPaymentWindowIfNeeded()) {
                    $pendingCount++;
                }
            }
        });

        if ($draftCount > 0 || $pendingCount > 0) {
            $this->info("Cancelled {$draftCount} draft order(s), {$pendingCount} unpaid pending order(s) (payment not submitted).");
        }

        return self::SUCCESS;
    }
}
