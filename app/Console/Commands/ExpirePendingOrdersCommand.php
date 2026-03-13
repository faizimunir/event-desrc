<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ExpirePendingOrdersCommand extends Command
{
    protected $signature = 'orders:expire-pending';

    protected $description = 'Mark orders that passed expired_at as expired and release bracket/package slots';

    public function handle(): int
    {
        $orders = Order::query()
            ->expiredPending()
            ->with('registration')
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            $order->update(['status' => Order::STATUS_EXPIRED]);
            $count++;
        }

        if ($count > 0) {
            $this->info("Marked {$count} order(s) as expired and released slot(s).");
        }

        return self::SUCCESS;
    }
}
