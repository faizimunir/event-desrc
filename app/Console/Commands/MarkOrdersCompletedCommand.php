<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class MarkOrdersCompletedCommand extends Command
{
    protected $signature = 'orders:mark-completed';

    protected $description = 'Set order confirmed → completed setelah event.end_at lewat (reporting)';

    public function handle(): int
    {
        $n = Order::query()
            ->where('status', Order::STATUS_CONFIRMED)
            ->whereHas('registration.event', function ($q) {
                $q->whereNotNull('end_at')->where('end_at', '<', now());
            })
            ->update(['status' => Order::STATUS_COMPLETED]);

        if ($n > 0) {
            $this->info("Marked {$n} order(s) as completed.");
        }

        return self::SUCCESS;
    }
}
