<?php

namespace App\Jobs;

use App\Models\WhatsappNotificationLog;
use App\Services\WhacenterService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhacenterMessageJob implements ShouldQueueAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Jeda cek ulang saat device belum terhubung (detik). */
    private const DEVICE_RECHECK_SECONDS = 60;

    /** Dipakai hanya jika job tidak punya deadline (payload lama). */
    public int $tries = 3;

    /** Kegagalan kirim yang sebenarnya (bukan menunggu device) sebelum job dinyatakan gagal. */
    public int $maxExceptions = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public int $timeout = 90;

    /**
     * Batas waktu (unix timestamp) job boleh menunggu device terhubung / retry.
     * Diisi oleh WhacenterService::queueMessage().
     */
    public ?int $deadlineTimestamp = null;

    public function __construct(
        public string $number,
        public string $message,
        public ?int $whatsappNotificationLogId = null,
    ) {
        $this->onConnection(config('services.whacenter.queue_connection', 'redis'));
        $this->onQueue(config('services.whacenter.queue', 'whatsapp'));
    }

    /**
     * Dengan deadline, job boleh di-release berkali-kali (menunggu device) tanpa menghabiskan
     * jatah percobaan; kegagalan kirim dibatasi oleh $maxExceptions.
     */
    public function retryUntil(): ?\DateTimeInterface
    {
        return $this->deadlineTimestamp !== null
            ? Carbon::createFromTimestamp($this->deadlineTimestamp)
            : null;
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

        if ($whacenter->deviceStatus()['connected'] === false) {
            $this->waitForDevice();

            return;
        }

        Log::info('Whacenter: sending message', [
            'number' => $this->number,
        ]);

        $result = $whacenter->sendMessageDetailed($this->number, $this->message);

        if (! $result['ok']) {
            if ($result['device_down']) {
                $whacenter->forgetDeviceStatus();
                $this->waitForDevice();

                return;
            }

            throw new \RuntimeException(
                'Whacenter gagal mengirim pesan: '.($result['error'] ?? 'unknown error')
            );
        }

        Log::info('Whacenter: message accepted', [
            'number' => $this->number,
            'message_id' => $result['message_id'],
        ]);

        $this->trackDelivery($log, $result['message_id']);
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
            $e instanceof MaxAttemptsExceededException
                ? __('Message expired before it could be sent (WhatsApp device not connected).')
                : ($e ? $e->getMessage() : __('Job failed.'))
        );
    }

    /** Device belum terhubung: tahan pesan (bukan dihitung gagal) dan cek lagi nanti. */
    private function waitForDevice(): void
    {
        Log::warning('Whacenter: device tidak terhubung, pesan ditahan.', [
            'number' => $this->number,
            'recheck_in' => self::DEVICE_RECHECK_SECONDS,
        ]);

        $this->release(self::DEVICE_RECHECK_SECONDS);
    }

    /**
     * Pesan sudah diterima Whacenter. Status sukses baru ditetapkan setelah messageStatus
     * mengonfirmasi (sent/delivered/read); tanpa message id / kolom tracking, pakai perilaku lama.
     * Tidak boleh melempar exception di sini agar pesan tidak terkirim dobel lewat retry.
     */
    private function trackDelivery(?WhatsappNotificationLog $log, ?string $messageId): void
    {
        if ($log === null) {
            return;
        }

        try {
            if ($messageId !== null
                && config('services.whacenter.verify_delivery', true)
                && WhatsappNotificationLog::supportsDeliveryTracking()) {
                $log->markAccepted($messageId);
                CheckWhacenterMessageStatusJob::schedule($log->id, $messageId);

                return;
            }

            $log->markSent($messageId);
        } catch (\Throwable $e) {
            Log::error('Whacenter: gagal mencatat status pengiriman', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
