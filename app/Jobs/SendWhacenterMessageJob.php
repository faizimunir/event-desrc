<?php

namespace App\Jobs;

use App\Models\WhatsappNotificationLog;
use App\Services\WhacenterService;
use Illuminate\Bus\Queueable;
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
        public ?int $whatsappNotificationLogId = null,
    ) {
        $this->onQueue(config('services.whacenter.queue', 'default'));
    }

    public function handle(WhacenterService $whacenter): void
    {
        $log = $this->whatsappNotificationLogId
            ? WhatsappNotificationLog::query()->find($this->whatsappNotificationLogId)
            : null;

        if (! config('services.whacenter.device_id')) {
            Log::warning('SendWhacenterMessageJob: WHACENTER_DEVICE_ID tidak di-set, pesan dilewati.');
            $log?->markFailed(__('Whacenter is not configured.'));

            return;
        }

        if (! $whacenter->sendMessage($this->number, $this->message)) {
            throw new \RuntimeException('Whacenter gagal mengirim pesan ke '.$this->number);
        }

        $log?->markSent();
    }

    public function failed(?\Throwable $e): void
    {
        if ($this->whatsappNotificationLogId === null) {
            return;
        }

        $log = WhatsappNotificationLog::query()->find($this->whatsappNotificationLogId);
        if (! $log || $log->status !== WhatsappNotificationLog::STATUS_QUEUED) {
            return;
        }

        $log->markFailed($e ? $e->getMessage() : __('Job failed after retries.'));
    }

    /**
     * Antrekan pengiriman dengan jeda acak (default 5–30 detik) sebelum diproses worker.
     */
    public static function dispatchWithRandomDelay(string $number, string $message, ?int $whatsappNotificationLogId = null): void
    {
        $min = max(0, (int) config('services.whacenter.delay_min_seconds', 5));
        $max = max($min, (int) config('services.whacenter.delay_max_seconds', 30));
        $seconds = random_int($min, $max);

        self::dispatch($number, $message, $whatsappNotificationLogId)->delay(now()->addSeconds($seconds));
    }
}
