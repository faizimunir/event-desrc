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

    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(
        public string $number,
        public string $message,
        public ?int $whatsappNotificationLogId = null,
    ) {
        $this->onQueue(config('services.whacenter.queue', 'whatsapp'));
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