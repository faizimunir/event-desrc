<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExpirePaymentProofDeadlinesCommand extends Command
{
    protected $signature = 'payments:expire-proof-deadlines';

    protected $description = 'Alias: pembatalan lewat waktu dipusatkan di orders:enforce-deadlines';

    public function handle(): int
    {
        return $this->call('orders:enforce-deadlines');
    }
}
