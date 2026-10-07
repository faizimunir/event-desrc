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

        $device = $whacenter->deviceStatus(fresh: true);
        $this->info('Status device: '.($device['status'] ?? 'tidak diketahui'));
        if ($device['connected'] === false) {
            $this->error('Device tidak terhubung, pesan tidak dikirim.');

            return self::FAILURE;
        }

        foreach ($numbers as $number) {
            if ($sync) {
                $this->info("Mengirim sync ke {$number}...");
                $result = $whacenter->sendMessageDetailed($number, $message);
                if ($result['ok']) {
                    $this->info('  → Diterima Whacenter (message id: '.($result['message_id'] ?? '-').')');
                    if ($result['message_id'] !== null) {
                        sleep(5);
                        $status = $whacenter->messageStatus($result['message_id']);
                        $this->info('  → Status pesan: '.$status['state'].' ('.($status['delivery_status'] ?? '-').')');
                    }
                    $ok++;
                } else {
                    $this->error('  → Gagal: '.($result['error'] ?? 'unknown'));
                    $fail++;
                }
            } else {
                $this->info("Mengantrekan ke {$number} (serial + jeda acak, butuh worker whatsapp)...");
                $whacenter->queueMessage($number, $message);
                $ok++;
            }
        }

        $this->newLine();
        if ($sync) {
            $this->info("Selesai: {$ok} terkirim, {$fail} gagal.");
        } else {
            $this->info("Di-antrekan: {$ok} job (serial). Jalankan: php artisan queue:work redis --queue=whatsapp");
        }

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
