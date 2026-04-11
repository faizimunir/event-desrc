<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpirePendingOrdersCommand extends Command
{
    protected $signature = 'orders:enforce-deadlines';

    protected $description = 'Batalkan draft lewat batas; batalkan pending unpaid lewat expired_at + expire payment pending (kuota lepas via query)';

    public function handle(): int
    {
        $draftCount = 0;
        Order::query()->expiredDraft()->with('registration')->chunkById(100, function ($orders) use (&$draftCount) {
            foreach ($orders as $order) {
                $order->update([
                    'status' => Order::STATUS_CANCELLED,
                    'payment_status' => null,
                ]);
                $draftCount++;
            }
        });

        $pendingCount = 0;
        Order::query()->expiredPendingUnpaid()->with('registration')->chunkById(100, function ($orders) use (&$pendingCount) {
            foreach ($orders as $order) {
                DB::transaction(function () use ($order, &$pendingCount) {
                    $order->payments()->where('status', Payment::STATUS_PENDING)->update([
                        'status' => Payment::STATUS_EXPIRED,
                    ]);
                    $order->update([
                        'status' => Order::STATUS_CANCELLED,
                        'payment_status' => Order::PAYMENT_STATUS_EXPIRED,
                    ]);
                    $pendingCount++;
                });
            }
        });

        if ($draftCount > 0 || $pendingCount > 0) {
            $this->info("Cancelled {$draftCount} draft order(s), {$pendingCount} unpaid pending order(s).");
        }

        return self::SUCCESS;
    }
}
