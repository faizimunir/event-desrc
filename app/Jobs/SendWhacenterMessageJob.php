<?php

namespace App\Jobs;

use App\Models\WhatsappNotificationLog;
use App\Services\WhacenterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhacenterMessageJob implements ShouldQueueAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public int $timeout = 90;

    public function __construct(
        public string $number,
        public string $message,
        public ?int $whatsappNotificationLogId = null,
    ) {
        $this->onConnection(config('services.whacenter.queue_connection', 'redis'));
        $this->onQueue(config('services.whacenter.queue', 'whatsapp'));
    }

    /**
     * Pastikan hanya satu kirim WA berjalan (worker whatsapp harus --queue=whatsapp, concurrency 1).
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('whacenter-send'))
                ->releaseAfter(15)
                ->expireAfter(180),
        ];
    }

    public function handle(WhacenterService $whacenter): void
    {
        $log = $this->whatsappNotificationLogId
            ? WhatsappNotificationLog::query()->find($this->whatsappNotificationLogId)
            : null;

        if (! config('services.whacenter.device_id')) {
            Log::warning('Whacenter: device ID belum dikonfigurasi.');

            $log?->markFailed(__('Whacenter is not configured.'));

            return;
        }

        Log::info('Whacenter: sending message', [
            'number' => $this->number,
        ]);

        if (! $whacenter->sendMessage($this->number, $this->message)) {
            throw new \RuntimeException(
                'Whacenter gagal mengirim pesan ke '.$this->number
            );
        }

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
