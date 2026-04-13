<?php

namespace App\Jobs;

use App\Services\WhacenterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhacenterMessageJob implements ShouldQueueAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public int $timeout = 60;

    /**
     * @param  string  $number  Nomor WA (akan dinormalisasi di service)
     */
    public function __construct(
        public string $number,
        public string $message,
    ) {
        $this->onQueue(config('services.whacenter.queue', 'default'));
    }

    public function handle(WhacenterService $whacenter): void
    {
        if (! config('services.whacenter.device_id')) {
            Log::warning('SendWhacenterMessageJob: WHACENTER_DEVICE_ID tidak di-set, pesan dilewati.');

            return;
        }

        if (! $whacenter->sendMessage($this->number, $this->message)) {
            throw new \RuntimeException('Whacenter gagal mengirim pesan ke '.$this->number);
        }
    }

    /**
     * Antrekan pengiriman dengan jeda acak (default 5–30 detik) sebelum diproses worker.
     */
    public static function dispatchWithRandomDelay(string $number, string $message): void
    {
        $min = max(0, (int) config('services.whacenter.delay_min_seconds', 5));
        $max = max($min, (int) config('services.whacenter.delay_max_seconds', 30));
        $seconds = random_int($min, $max);

        self::dispatch($number, $message)->delay(now()->addSeconds($seconds));
    }
}
