<?php

namespace App\Services;

use App\Jobs\BulkSendTicketWhatsappJob;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Models\WhatsappNotificationLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Pengiriman massal & pemantauan notifikasi WhatsApp e-ticket per event.
 *
 * Beban server dijaga dengan: (1) pengiriman dipecah per chunk lewat job (bukan di request web),
 * (2) setiap pesan tetap lewat jadwal serial WhacenterService::queueMessage(), dan
 * (3) status "terakhir" tiap pendaftar dihitung dengan satu subquery terindeks, tanpa N+1.
 */
class TicketWhatsappBroadcast
{
    public const STATE_NOT_SENT = 'not_sent';

    public const STATE_FAILED = 'failed';

    public const STATE_QUEUED = 'queued';

    public const STATE_SENT = 'sent';

    /** Nilai filter "semua status" pada pengiriman massal. */
    public const STATE_ALL = 'all';

    /** @var list<string> */
    public const STATES = [
        self::STATE_NOT_SENT,
        self::STATE_FAILED,
        self::STATE_QUEUED,
        self::STATE_SENT,
    ];

    /** Target yang boleh dikirim massal (queued sengaja tidak: sedang berjalan). */
    public const BULK_TARGETS = [
        self::STATE_NOT_SENT,
        self::STATE_FAILED,
        self::STATE_ALL,
    ];

    public const RUN_RUNNING = 'running';

    public const RUN_DONE = 'done';

    public const RUN_CANCELLED = 'cancelled';

    public const RUN_ABORTED = 'aborted';

    /** Run dianggap macet (worker mati) bila tidak ada progres selama ini. */
    private const RUN_STALE_MINUTES = 15;

    private const PROGRESS_TTL_HOURS = 24;

    /** @return list<string> */
    public static function ticketLogTypes(): array
    {
        return [
            WhatsappNotificationLog::TYPE_TICKET_ISSUED,
            WhatsappNotificationLog::TYPE_TICKET_RESENT,
        ];
    }

    /**
     * Event yang boleh dikelola user (selaras dengan EventPolicy): admin-level melihat semua,
     * organizer hanya event miliknya.
     */
    public static function accessibleEvents(User $user): Builder
    {
        return Event::query()->when(
            ! $user->hasRole('super_admin') && ! $user->hasRole('admin') && ! $user->hasRole('committee'),
            fn (Builder $query) => $query->whereHas(
                'organizer',
                fn (Builder $organizer) => $organizer->whereHas('users', fn (Builder $u) => $u->whereKey($user->id))
            )
        );
    }

    /**
     * SQL status WA e-ticket terakhir per pendaftar (not_sent bila belum ada log sama sekali).
     *
     * @return array{0: string, 1: list<string>} [expression, bindings]
     */
    private static function stateExpression(): array
    {
        $types = self::ticketLogTypes();
        $placeholders = implode(', ', array_fill(0, count($types), '?'));

        $sql = "COALESCE((SELECT wnl.status FROM whatsapp_notification_logs AS wnl "
            ."WHERE wnl.registration_id = registrations.id AND wnl.type IN ({$placeholders}) "
            ."ORDER BY wnl.id DESC LIMIT 1), '".self::STATE_NOT_SENT."')";

        return [$sql, $types];
    }

    /** Pendaftar yang sudah punya e-ticket dan masih berstatus approved. */
    public static function eligibleQuery(int $eventId): Builder
    {
        return Registration::query()
            ->where('registrations.event_id', $eventId)
            ->where('registrations.status', Registration::STATUS_APPROVED)
            ->whereHas('ticket');
    }

    public static function whereState(Builder $query, string $state): Builder
    {
        [$sql, $bindings] = self::stateExpression();

        return $query->whereRaw("{$sql} = ?", [...$bindings, $state]);
    }

    public static function whereStateNot(Builder $query, string $state): Builder
    {
        [$sql, $bindings] = self::stateExpression();

        return $query->whereRaw("{$sql} <> ?", [...$bindings, $state]);
    }

    public static function whereHasWhatsapp(Builder $query): Builder
    {
        return $query->whereHas(
            'rider.user',
            fn (Builder $user) => $user->whereNotNull('whatsapp')->where('whatsapp', '!=', '')
        );
    }

    /**
     * Pendaftar yang akan dikirimi pada run massal: punya nomor WA dan tidak sedang dalam antrean.
     *
     * @param  string  $target  salah satu BULK_TARGETS
     */
    public static function bulkQuery(int $eventId, string $target): Builder
    {
        $query = self::whereHasWhatsapp(self::eligibleQuery($eventId));
        self::whereStateNot($query, self::STATE_QUEUED);

        if ($target !== self::STATE_ALL) {
            self::whereState($query, $target);
        }

        return $query;
    }

    /**
     * Ringkasan pendaftar ber-e-ticket (dua query teragregasi):
     * - `counts`: jumlah per status terakhir + total
     * - `no_whatsapp`: pendaftar tanpa nomor WA (tidak bisa dikirimi)
     * - `sendable`: jumlah yang akan dikirim per target massal (punya WA, tidak sedang antre)
     *
     * @return array{
     *     counts: array{total: int, not_sent: int, failed: int, queued: int, sent: int},
     *     no_whatsapp: int,
     *     sendable: array{not_sent: int, failed: int, all: int}
     * }
     */
    public static function summary(int $eventId): array
    {
        $all = self::countByState(self::eligibleQuery($eventId));
        $withWhatsapp = self::countByState(self::whereHasWhatsapp(self::eligibleQuery($eventId)));

        return [
            'counts' => $all,
            'no_whatsapp' => $all['total'] - $withWhatsapp['total'],
            'sendable' => [
                self::STATE_NOT_SENT => $withWhatsapp[self::STATE_NOT_SENT],
                self::STATE_FAILED => $withWhatsapp[self::STATE_FAILED],
                self::STATE_ALL => $withWhatsapp['total'] - $withWhatsapp[self::STATE_QUEUED],
            ],
        ];
    }

    /** @return array{total: int, not_sent: int, failed: int, queued: int, sent: int} */
    private static function countByState(Builder $query): array
    {
        [$sql, $bindings] = self::stateExpression();

        $rows = $query
            ->selectRaw("{$sql} AS wa_state, COUNT(*) AS aggregate", $bindings)
            ->groupBy('wa_state')
            ->pluck('aggregate', 'wa_state');

        $counts = ['total' => 0];
        foreach (self::STATES as $state) {
            $counts[$state] = (int) ($rows[$state] ?? 0);
            $counts['total'] += $counts[$state];
        }

        return $counts;
    }

    /**
     * Perkiraan lama pengiriman serial (detik), mengikuti jeda acak & istirahat per batch.
     */
    public static function estimateSeconds(int $messages): int
    {
        if ($messages <= 0) {
            return 0;
        }

        $min = max(0, (int) config('services.whacenter.delay_min_seconds', 15));
        $max = max($min, (int) config('services.whacenter.delay_max_seconds', 45));
        $batchSize = max(0, (int) config('services.whacenter.batch_size', 20));
        $restMin = max(0, (int) config('services.whacenter.batch_rest_min_seconds', 60));
        $restMax = max($restMin, (int) config('services.whacenter.batch_rest_max_seconds', 180));

        $seconds = $messages * (($min + $max) / 2);
        if ($batchSize > 0) {
            $seconds += intdiv($messages, $batchSize) * (($restMin + $restMax) / 2);
        }

        return (int) round($seconds);
    }

    /** @return array<string, mixed>|null */
    public static function progress(int $eventId): ?array
    {
        $progress = Cache::get(self::progressKey($eventId));

        return is_array($progress) ? $progress : null;
    }

    public static function isRunActive(?array $progress): bool
    {
        return $progress !== null
            && ($progress['status'] ?? null) === self::RUN_RUNNING
            && ($progress['updated_at'] ?? 0) > now()->subMinutes(self::RUN_STALE_MINUTES)->timestamp;
    }

    public static function isCancelRequested(int $eventId, string $runId): bool
    {
        return Cache::get(self::cancelKey($eventId)) === $runId;
    }

    /**
     * Mulai pengiriman massal. Hanya membuat penanda progres dan mendispatch job pertama;
     * pembuatan pesan dilakukan job per chunk agar request web tetap ringan.
     *
     * @return array{ok: bool, error: string|null, total: int}
     */
    public static function start(Event $event, string $target, User $user): array
    {
        if (! in_array($target, self::BULK_TARGETS, true)) {
            return ['ok' => false, 'error' => __('Invalid target.'), 'total' => 0];
        }

        if (app(WhacenterService::class)->deviceStatus(fresh: true)['connected'] === false) {
            return [
                'ok' => false,
                'error' => __('WhatsApp device is not connected. Reconnect it before sending in bulk.'),
                'total' => 0,
            ];
        }

        return Cache::lock('wa-ticket-bulk-start:'.$event->id, 10)->block(3, function () use ($event, $target, $user) {
            if (self::isRunActive(self::progress($event->id))) {
                return ['ok' => false, 'error' => __('A bulk send for this event is still running.'), 'total' => 0];
            }

            $total = self::bulkQuery($event->id, $target)->count();
            if ($total === 0) {
                return ['ok' => false, 'error' => __('There are no participants to send to.'), 'total' => 0];
            }

            $runId = (string) Str::uuid();
            Cache::forget(self::cancelKey($event->id));
            Cache::put(self::progressKey($event->id), [
                'run_id' => $runId,
                'status' => self::RUN_RUNNING,
                'target' => $target,
                'total' => $total,
                'processed' => 0,
                'queued' => 0,
                'skipped' => 0,
                'reason' => null,
                'started_by' => $user->name,
                'started_at' => now()->timestamp,
                'updated_at' => now()->timestamp,
                'finished_at' => null,
            ], now()->addHours(self::PROGRESS_TTL_HOURS));

            dispatch(new BulkSendTicketWhatsappJob($event->id, $runId, $target));

            return ['ok' => true, 'error' => null, 'total' => $total];
        });
    }

    /** Minta run berhenti; pesan yang sudah masuk antrean tetap diproses. */
    public static function requestCancel(int $eventId): bool
    {
        $progress = self::progress($eventId);
        if (! self::isRunActive($progress)) {
            return false;
        }

        Cache::put(self::cancelKey($eventId), $progress['run_id'], now()->addHours(self::PROGRESS_TTL_HOURS));

        return true;
    }

    /**
     * Perbarui progres run yang sedang berjalan. Diabaikan bila run sudah digantikan.
     *
     * @param  array<string, mixed>  $changes
     */
    public static function updateProgress(int $eventId, string $runId, array $changes): void
    {
        $progress = self::progress($eventId);
        if ($progress === null || ($progress['run_id'] ?? null) !== $runId) {
            return;
        }

        Cache::put(
            self::progressKey($eventId),
            [...$progress, ...$changes, 'updated_at' => now()->timestamp],
            now()->addHours(self::PROGRESS_TTL_HOURS)
        );
    }

    public static function finish(int $eventId, string $runId, string $status, ?string $reason = null): void
    {
        self::updateProgress($eventId, $runId, [
            'status' => $status,
            'reason' => $reason,
            'finished_at' => now()->timestamp,
        ]);
    }

    private static function progressKey(int $eventId): string
    {
        return 'wa-ticket-bulk:'.$eventId;
    }

    private static function cancelKey(int $eventId): string
    {
        return 'wa-ticket-bulk:'.$eventId.':cancel';
    }
}
