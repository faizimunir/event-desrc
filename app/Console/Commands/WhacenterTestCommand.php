<?php

namespace App\Console\Commands;

use App\Services\WhacenterService;
use Illuminate\Console\Command;

class WhacenterTestCommand extends Command
{
    protected $signature = 'whacenter:test
                            {numbers?* : Nomor WhatsApp (kosong = blast ke 2 nomor default)}
                            {--message= : Isi pesan (default: pesan test)}
                            {--sync : Kirim langsung tanpa antrian (debug)}';

    protected $description = 'Antrekan / kirim pesan WhatsApp test via Whacenter (default: antrian + jeda acak)';

    private const DEFAULT_TEST_NUMBERS = ['081333033690', '08885307728'];

    public function handle(WhacenterService $whacenter): int
    {
        $message = $this->option('message') ?? 'Test dari desrc - Whacenter OK.';
        $numbers = $this->argument('numbers');
        if (empty($numbers)) {
            $numbers = self::DEFAULT_TEST_NUMBERS;
            $this->info('Blast ke '.count($numbers).' nomor default.');
        }

        if (! config('services.whacenter.device_id')) {
            $this->error('WHACENTER_DEVICE_ID belum di-set di .env');

            return self::FAILURE;
        }

        $sync = (bool) $this->option('sync');
        $ok = 0;
        $fail = 0;

        foreach ($numbers as $number) {
            if ($sync) {
                $this->info("Mengirim sync ke {$number}...");
                if ($whacenter->sendMessage($number, $message)) {
                    $this->info('  → OK');
                    $ok++;
                } else {
                    $this->error('  → Gagal');
                    $fail++;
                }
            } else {
                $this->info("Mengantrekan ke {$number} (jeda acak, butuh queue worker)...");
                $whacenter->queueMessage($number, $message);
                $ok++;
            }
        }

        $this->newLine();
        if ($sync) {
            $this->info("Selesai: {$ok} terkirim, {$fail} gagal.");
        } else {
            $this->info("Di-antrekan: {$ok} job. Jalankan `php artisan queue:work` untuk memproses.");
        }

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
