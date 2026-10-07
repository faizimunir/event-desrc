<?php

namespace App\Jobs;

use App\Models\WhatsappNotificationLog;
use App\Services\WhacenterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Memverifikasi lewat GET /api/messageStatus bahwa pesan yang sudah diterima Whacenter
 * benar-benar terkirim (sent/delivered/read). Ringan: satu GET per pengecekan, hanya untuk
 * pesan yang punya log, dengan jeda bertahap lalu berhenti.
 */
class CheckWhacenterMessageStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Jeda (detik) sebelum pengecekan ke-1, ke-2, dst. Jumlah elemen = jumlah maksimum pengecekan.
     *
     * @var array<int, int>
     */
    private const CHECK_DELAYS = [15, 30, 60, 120, 240, 300];

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public int $logId,
        public string $providerMessageId,
        public int $check = 1,
    ) {
        $this->onConnection(config('services.whacenter.queue_connection', 'redis'));
        $this->onQueue(config('services.whacenter.queue', 'whatsapp'));
    }

    public static function schedule(int $logId, string $providerMessageId, int $check = 1): void
    {
        $delay = self::CHECK_DELAYS[$check - 1] ?? null;
        if ($delay === null) {
            return;
        }

        dispatch(new self($logId, $providerMessageId, $check))->delay(now()->addSeconds($delay));
    }

    public function handle(WhacenterService $whacenter): void
    {
        $log = WhatsappNotificationLog::query()->find($this->logId);
        if (! $log || $log->status !== WhatsappNotificationLog::STATUS_QUEUED) {
            return;
        }

        $status = $whacenter->messageStatus($this->providerMessageId);
        $isLastCheck = $this->check >= count(self::CHECK_DELAYS);

        switch ($status['state']) {
            case 'confirmed':
                $log->markSent($this->providerMessageId, [
                    'delivery_status' => $status['delivery_status'],
                    'delivered_at' => $status['delivered_at'],
                    'read_at' => $status['read_at'],
                ]);

                return;

            case 'failed':
                $log->markFailed($status['reason'] ?? __('WhatsApp reported the message as failed.'));

                return;

            case 'unavailable':
                if ($isLastCheck) {
                    // Status API tidak bisa dijangkau; pesan sudah diterima Whacenter, jangan dilabeli gagal.
                    Log::warning('Whacenter: status pesan tidak dapat diverifikasi, dianggap terkirim.', [
                        'log_id' => $this->logId,
                        'reason' => $status['reason'],
                    ]);
                    $log->markSent($this->providerMessageId);

                    return;
                }
                break;

            default:
                if ($isLastCheck) {
                    $log->markFailed(__('Message was not confirmed as sent by WhatsApp.'));

                    return;
                }
        }

        self::schedule($this->logId, $this->providerMessageId, $this->check + 1);
    }

    public function failed(?\Throwable $e): void
    {
        Log::error('Whacenter: pengecekan status pesan error', [
            'log_id' => $this->logId,
            'error' => $e?->getMessage(),
        ]);
    }
}
