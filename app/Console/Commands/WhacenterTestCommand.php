<?php

namespace App\Console\Commands;

use App\Services\WhacenterService;
use Illuminate\Console\Command;

class WhacenterTestCommand extends Command
{
    protected $signature = 'whacenter:test
                            {numbers?* : Nomor WhatsApp (kosong = blast ke 2 nomor default)}
                            {--message= : Isi pesan (default: pesan test)}';

    protected $description = 'Kirim pesan WhatsApp test via Whacenter (default: blast ke 2 nomor)';

    private const DEFAULT_TEST_NUMBERS = ['081333033690', '08885307728'];

    public function handle(WhacenterService $whacenter): int
    {
        $message = $this->option('message') ?? 'Test dari desrc - Whacenter OK.';
        $numbers = $this->argument('numbers');
        if (empty($numbers)) {
            $numbers = self::DEFAULT_TEST_NUMBERS;
            $this->info('Blast ke ' . count($numbers) . ' nomor default.');
        }

        if (! config('services.whacenter.device_id')) {
            $this->error('WHACENTER_DEVICE_ID belum di-set di .env');
            return self::FAILURE;
        }

        $success = 0;
        $failed = 0;
        foreach ($numbers as $number) {
            $this->info("Mengirim ke {$number}...");
            if ($whacenter->sendMessage($number, $message)) {
                $this->info("  → OK");
                $success++;
            } else {
                $this->error("  → Gagal");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Selesai: {$success} terkirim, {$failed} gagal.");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
