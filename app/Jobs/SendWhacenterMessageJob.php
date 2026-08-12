<?php

namespace App\Jobs;

use App\Models\WhatsappNotificationLog;
use App\Services\WhacenterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhacenterMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(
        public string $number,
        public string $message,
        public ?int $whatsappNotificationLogId = null,
    ) {
        $this->onQueue(config('services.whacenter.queue', 'whatsapp'));
    }

    public function handle(
        WhacenterService $whacenter,
        CacheRepository $cache,
    ): void {
        $log = $this->whatsappNotificationLogId
            ? WhatsappNotificationLog::query()->find($this->whatsappNotificationLogId)
            : null;

        if (! config('services.whacenter.device_id')) {
            Log::warning('Whacenter: device ID belum dikonfigurasi.');

            $log?->markFailed(__('Whacenter is not configured.'));

            return;
        }

        /*
         * Ambil waktu pengiriman terakhir.
         */
        $key = config(
            'services.whacenter.rate_limit_key',
            'whacenter:last_sent_at'
        );

        $lastSentAt = $cache->get($key);

        if ($lastSentAt !== null) {
            $min = max(
                30,
                (int) config('services.whacenter.delay_min_seconds', 30)
            );

            $max = max(
                $min,
                (int) config('services.whacenter.delay_max_seconds', 300)
            );

            $requiredDelay = random_int($min, $max);

            $elapsed = now()->timestamp - (int) $lastSentAt;

            if ($elapsed < $requiredDelay) {
                $wait = $requiredDelay - $elapsed;

                Log::info('Whacenter: rate limit waiting', [
                    'number' => $this->number,
                    'wait_seconds' => $wait,
                    'elapsed_seconds' => $elapsed,
                ]);

                sleep($wait);
            }
        }

        /*
         * Kirim pesan.
         */
        Log::info('Whacenter: sending message', [
            'number' => $this->number,
        ]);

        if (! $whacenter->sendMessage($this->number, $this->message)) {
            throw new \RuntimeException(
                'Whacenter gagal mengirim pesan ke '.$this->number
            );
        }

        /*
         * Catat waktu pengiriman sukses.
         */
        $cache->put(
            $key,
            now()->timestamp,
            now()->addHours(24)
        );

        $log?->markSent();

        Log::info('Whacenter: message sent', [
            'number' => $this->number,
        ]);
    }

    public function failed(?\Throwable $e): void
    {
        if ($this->whatsappNotificationLogId === null) {
            return;
        }

        $log = WhatsappNotificationLog::query()
            ->find($this->whatsappNotificationLogId);

        if (! $log || $log->status !== WhatsappNotificationLog::STATUS_QUEUED) {
            return;
        }

        $log->markFailed(
            $e
                ? $e->getMessage()
                : __('Job failed.')
        );
    }
}