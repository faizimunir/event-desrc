<?php

namespace App\Console\Commands;

use App\Models\WhatsappNotificationLog;
use App\Services\WhacenterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Jaring pengaman untuk log WhatsApp yang tertahan di "queued" ketika worker antrean tidak berjalan.
 * Berjalan lewat scheduler (bukan worker), jadi tetap bekerja walau worker mati.
 */
class WhacenterWatchdogCommand extends Command
{
    protected $signature = 'whacenter:watchdog {--dry-run : Hanya tampilkan, tidak mengubah data}';

    protected $description = 'Selesaikan log WhatsApp yang macet di status queued (worker mati / job hilang)';

    /** Verifikasi normal selesai < ~13 menit; lewat dari ini dianggap macet. */
    private const VERIFY_STALE_MINUTES = 20;

    /** Jika status API tetap tidak bisa dijangkau selama ini, pesan dianggap terkirim (sudah diterima Whacenter). */
    private const VERIFY_GIVE_UP_MINUTES = 60;

    private const BATCH_LIMIT = 100;

    public function handle(WhacenterService $whacenter): int
    {
        if (! WhatsappNotificationLog::tableExists()) {
            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');

        $resolved = $this->resolveAcceptedButUnverified($whacenter, $dry);
        $expired = $this->failExpiredUnsent($dry);

        if ($expired > 0) {
            Log::error('Whacenter watchdog: pesan tidak diproses sebelum kedaluwarsa. Cek worker `queue:work redis --queue=whatsapp`.', [
                'failed' => $expired,
            ]);
        }

        $this->info("Watchdog: {$resolved} diverifikasi ulang, {$expired} ditandai gagal (kedaluwarsa).".($dry ? ' [dry-run]' : ''));

        return self::SUCCESS;
    }

    /** Pesan sudah diterima Whacenter tetapi job verifikasi tidak pernah selesai. */
    private function resolveAcceptedButUnverified(WhacenterService $whacenter, bool $dry): int
    {
        if (! WhatsappNotificationLog::supportsDeliveryTracking()) {
            return 0;
        }

        $logs = WhatsappNotificationLog::query()
            ->where('status', WhatsappNotificationLog::STATUS_QUEUED)
            ->whereNotNull('provider_message_id')
            ->where('updated_at', '<', now()->subMinutes(self::VERIFY_STALE_MINUTES))
            ->orderBy('id')
            ->limit(self::BATCH_LIMIT)
            ->get();

        $count = 0;

        foreach ($logs as $log) {
            $status = $whacenter->messageStatus($log->provider_message_id);
            $count++;

            if ($dry) {
                $this->line("#{$log->id} → {$status['state']}");

                continue;
            }

            match ($status['state']) {
                'confirmed' => $log->markSent($log->provider_message_id, [
                    'delivery_status' => $status['delivery_status'],
                    'delivered_at' => $status['delivered_at'],
                    'read_at' => $status['read_at'],
                ]),
                'failed' => $log->markFailed($status['reason'] ?? __('WhatsApp reported the message as failed.')),
                'unavailable' => $log->updated_at->lt(now()->subMinutes(self::VERIFY_GIVE_UP_MINUTES))
                    ? $log->markSent($log->provider_message_id)
                    : null,
                default => $log->markFailed(__('Message was not confirmed as sent by WhatsApp.')),
            };
        }

        return $count;
    }

    /** Pesan yang tidak pernah diterima Whacenter dan sudah melewati batas waktunya. */
    private function failExpiredUnsent(bool $dry): int
    {
        $grace = max(0, (int) config('services.whacenter.watchdog_grace_minutes', 5));
        $stale = max(1, (int) config('services.whacenter.stale_after_minutes', 360));

        $query = WhatsappNotificationLog::query()
            ->where('status', WhatsappNotificationLog::STATUS_QUEUED);

        if (WhatsappNotificationLog::supportsDeliveryTracking()) {
            $query->whereNull('provider_message_id');
        }

        if (WhatsappNotificationLog::supportsScheduleTracking()) {
            $query->where(function ($q) use ($grace, $stale) {
                $q->where('expires_at', '<', now()->subMinutes($grace))
                    ->orWhere(function ($q) use ($stale) {
                        $q->whereNull('expires_at')->where('created_at', '<', now()->subMinutes($stale));
                    });
            });
        } else {
            $query->where('created_at', '<', now()->subMinutes($stale));
        }

        $logs = $query->orderBy('id')->limit(self::BATCH_LIMIT)->get();

        if (! $dry) {
            foreach ($logs as $log) {
                $log->markFailed(__('Message was not processed before it expired (queue worker may be down).'));
            }
        } else {
            foreach ($logs as $log) {
                $this->line("#{$log->id} kedaluwarsa");
            }
        }

        return $logs->count();
    }
}
