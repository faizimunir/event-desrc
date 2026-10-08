<?php

namespace App\Jobs;

use App\Services\TicketService;
use App\Services\TicketWhatsappBroadcast;
use App\Services\WhacenterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Memasukkan pesan e-ticket ke antrean WhatsApp secara bertahap (per chunk) untuk satu event.
 *
 * Setiap chunk hanya membuat beberapa log + job kirim, lalu men-dispatch chunk berikutnya, sehingga
 * tidak ada proses panjang yang menahan worker dan pengiriman nyata tetap lewat jadwal serial
 * WhacenterService::queueMessage().
 */
class BulkSendTicketWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private const CHUNK_SIZE = 25;

    /** Jeda antar chunk (detik) supaya worker sempat memproses job kirim yang sudah jatuh tempo. */
    private const CHUNK_PAUSE_SECONDS = 3;

    /** Jeda cek ulang saat device terputus (detik) dan batas jumlah pengecekan sebelum run dibatalkan. */
    private const DEVICE_RECHECK_SECONDS = 60;

    private const MAX_DEVICE_WAITS = 30;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(
        public int $eventId,
        public string $runId,
        public string $target,
        public int $afterId = 0,
        public int $deviceWaits = 0,
    ) {
        $this->onConnection(config('services.whacenter.queue_connection', 'redis'));
        $this->onQueue(config('services.whacenter.queue', 'whatsapp'));
    }

    public function handle(WhacenterService $whacenter): void
    {
        $progress = TicketWhatsappBroadcast::progress($this->eventId);
        if ($progress === null
            || ($progress['run_id'] ?? null) !== $this->runId
            || ($progress['status'] ?? null) !== TicketWhatsappBroadcast::RUN_RUNNING) {
            return;
        }

        if (TicketWhatsappBroadcast::isCancelRequested($this->eventId, $this->runId)) {
            TicketWhatsappBroadcast::finish($this->eventId, $this->runId, TicketWhatsappBroadcast::RUN_CANCELLED);

            return;
        }

        if ($whacenter->deviceStatus()['connected'] === false) {
            $this->waitForDevice();

            return;
        }

        $registrations = TicketWhatsappBroadcast::bulkQuery($this->eventId, $this->target)
            ->where('registrations.id', '>', $this->afterId)
            ->with(['ticket', 'rider.user', 'event.organizer.users', 'bracket', 'package', 'order'])
            ->orderBy('registrations.id')
            ->limit(self::CHUNK_SIZE)
            ->get();

        $queued = 0;
        $skipped = 0;

        foreach ($registrations as $registration) {
            try {
                $error = TicketService::resendTicketWhatsapp($registration);
            } catch (\Throwable $e) {
                report($e);
                $error = $e->getMessage();
            }

            $error === null ? $queued++ : $skipped++;
        }

        TicketWhatsappBroadcast::updateProgress($this->eventId, $this->runId, [
            'processed' => ($progress['processed'] ?? 0) + $registrations->count(),
            'queued' => ($progress['queued'] ?? 0) + $queued,
            'skipped' => ($progress['skipped'] ?? 0) + $skipped,
        ]);

        if ($registrations->count() < self::CHUNK_SIZE) {
            TicketWhatsappBroadcast::finish($this->eventId, $this->runId, TicketWhatsappBroadcast::RUN_DONE);

            return;
        }

        dispatch(new self($this->eventId, $this->runId, $this->target, (int) $registrations->last()->id))
            ->delay(now()->addSeconds(self::CHUNK_PAUSE_SECONDS));
    }

    public function failed(?\Throwable $e): void
    {
        Log::error('Bulk e-ticket WhatsApp run failed', [
            'event_id' => $this->eventId,
            'run_id' => $this->runId,
            'error' => $e?->getMessage(),
        ]);

        TicketWhatsappBroadcast::finish(
            $this->eventId,
            $this->runId,
            TicketWhatsappBroadcast::RUN_ABORTED,
            __('The bulk send stopped because of an unexpected error.')
        );
    }

    /** Device terputus: jangan menambah antrean, cek lagi sebentar lagi, menyerah setelah batas tunggu. */
    private function waitForDevice(): void
    {
        if ($this->deviceWaits >= self::MAX_DEVICE_WAITS) {
            TicketWhatsappBroadcast::finish(
                $this->eventId,
                $this->runId,
                TicketWhatsappBroadcast::RUN_ABORTED,
                __('WhatsApp device was disconnected for too long. Reconnect it and start again.')
            );

            return;
        }

        TicketWhatsappBroadcast::updateProgress($this->eventId, $this->runId, []);

        dispatch(new self($this->eventId, $this->runId, $this->target, $this->afterId, $this->deviceWaits + 1))
            ->delay(now()->addSeconds(self::DEVICE_RECHECK_SECONDS));
    }
}
