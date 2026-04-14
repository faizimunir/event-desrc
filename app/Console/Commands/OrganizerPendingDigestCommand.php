<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\WhacenterService;
use Illuminate\Console\Command;

class OrganizerPendingDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'organizers:pending-digest
                            {--check : Hanya cek data (tampilkan nomor WA admin, tidak kirim)}
                            {--hours=1 : Jangka waktu (jam) untuk data pending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim ringkasan pending order & payment ke admin organizer (per jam).';

    /**
     * Execute the console command.
     */
    public function handle(WhacenterService $whacenter): int
    {
        $checkOnly = (bool) $this->option('check');
        $hours = max(1, (int) $this->option('hours'));
        $since = now()->subHours($hours);

        if (! config('services.whacenter.device_id') && ! $checkOnly) {
            $this->warn('WHACENTER_DEVICE_ID belum di-set, skip digest.');

            return self::SUCCESS;
        }

        // Pending orders (draft / menunggu bayar) yang baru dibuat dalam N jam terakhir
        $orders = Order::query()
            ->pendingPayment()
            ->where('created_at', '>=', $since)
            ->with(['registration.event.organizer.user'])
            ->get();

        // Pending payments yang baru dibuat dalam N jam terakhir
        $payments = Payment::query()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])
            ->where('created_at', '>=', $since)
            ->with(['registration.event.organizer.user'])
            ->get();

        $this->info("Data: {$orders->count()} pending order(s), {$payments->count()} pending payment(s) (dalam {$hours} jam terakhir).");

        if ($orders->isEmpty() && $payments->isEmpty()) {
            $this->info('Tidak ada pending order/payment baru dalam jangka waktu tersebut.');

            return self::SUCCESS;
        }

        // Diagnostik: tampilkan event -> organizer -> user -> whatsapp untuk setiap order/payment
        $seen = [];
        foreach ($orders as $order) {
            $this->logAdminLookup($order->registration?->event, $order->id, 'order', $seen);
        }
        foreach ($payments as $payment) {
            $this->logAdminLookup($payment->registration?->event, $payment->id, 'payment', $seen);
        }

        // Group per admin organizer (user_id)
        $byOrganizer = [];
        $skippedNoUser = 0;
        $skippedNoWhatsapp = 0;

        foreach ($orders as $order) {
            $event = $order->registration?->event;
            $organizer = $event?->organizer;
            $user = $organizer?->user;

            if (! $user) {
                $skippedNoUser++;

                continue;
            }
            if (empty(trim((string) $user->whatsapp))) {
                $skippedNoWhatsapp++;

                continue;
            }

            $key = $user->id;
            $byOrganizer[$key] ??= [
                'user' => $user,
                'events' => [],
            ];

            $eventKey = $event?->id ?? 0;
            $byOrganizer[$key]['events'][$eventKey]['event'] = $event;
            $byOrganizer[$key]['events'][$eventKey]['orders'] = ($byOrganizer[$key]['events'][$eventKey]['orders'] ?? 0) + 1;
        }

        foreach ($payments as $payment) {
            $event = $payment->registration?->event;
            $organizer = $event?->organizer;
            $user = $organizer?->user;

            if (! $user) {
                $skippedNoUser++;

                continue;
            }
            if (empty(trim((string) $user->whatsapp))) {
                $skippedNoWhatsapp++;

                continue;
            }

            $key = $user->id;
            $byOrganizer[$key] ??= [
                'user' => $user,
                'events' => [],
            ];

            $eventKey = $event?->id ?? 0;
            $byOrganizer[$key]['events'][$eventKey]['event'] = $event;
            $byOrganizer[$key]['events'][$eventKey]['payments'] = ($byOrganizer[$key]['events'][$eventKey]['payments'] ?? 0) + 1;
        }

        $this->info("Organizer tanpa user_id: {$skippedNoUser} item di-skip. User tanpa WhatsApp: {$skippedNoWhatsapp} item di-skip.");

        if ($checkOnly) {
            $this->newLine();
            $this->info('--- Mode --check: tidak mengirim WA ---');
            if ($byOrganizer === []) {
                $this->warn('Tidak ada admin event dengan nomor WhatsApp yang ketemu. Pastikan: Event punya organizer_id, Organizer punya user_id, User punya whatsapp diisi.');
            } else {
                $this->table(
                    ['User ID', 'Nama', 'WhatsApp', 'Event(s)'],
                    collect($byOrganizer)->map(function ($data) {
                        $u = $data['user'];
                        $eventTitles = collect($data['events'])->pluck('event.title')->filter()->unique()->values()->join(', ');

                        return [$u->id, $u->name ?? '-', $u->whatsapp ?? '(kosong)', $eventTitles ?: '-'];
                    })->values()->all()
                );
            }

            return self::SUCCESS;
        }

        if ($byOrganizer === []) {
            $this->warn('Tidak ada organizer dengan WhatsApp untuk dikirimi digest. Jalankan dengan --check untuk cek data.');

            return self::SUCCESS;
        }

        $totalQueued = 0;

        foreach ($byOrganizer as $data) {
            $user = $data['user'];
            $events = $data['events'];

            // Lewati jika semua event hanya punya 0 order & 0 payment (harusnya tidak mungkin, tapi untuk jaga-jaga).
            $hasPending = collect($events)->some(function ($evt) {
                return ($evt['orders'] ?? 0) > 0 || ($evt['payments'] ?? 0) > 0;
            });
            if (! $hasPending) {
                continue;
            }

            $lines = [];
            $lines[] = 'Halo, '.($user->name ?? 'Admin').'.';
            $lines[] = '';
            $lines[] = 'Ringkasan pendaftar yang masih menunggu pembayaran / review bukti dalam 1 jam terakhir:';
            $lines[] = '';

            foreach ($events as $evtData) {
                $event = $evtData['event'];
                $orderCount = $evtData['orders'] ?? 0;
                $paymentCount = $evtData['payments'] ?? 0;

                $title = $event?->title ?? '-';
                $eventLine = '• '.$title.': ';
                $parts = [];
                if ($orderCount > 0) {
                    $parts[] = $orderCount.' pending order';
                }
                if ($paymentCount > 0) {
                    $parts[] = $paymentCount.' pending payment';
                }

                if ($parts !== []) {
                    $eventLine .= implode(' + ', $parts);
                    $lines[] = $eventLine;
                }
            }

            $lines[] = '';
            $lines[] = 'Silakan cek dashboard DESRC untuk detail dan verifikasi pembayaran.';

            $message = implode("\n", $lines);

            $whacenter->queueMessage($user->whatsapp, $message);
            $this->info('Digest di-antrekan untuk '.$user->whatsapp.' (jeda acak + worker).');
            $totalQueued++;
        }

        $this->info("Total digest di-antrekan: {$totalQueued} — pastikan `php artisan queue:work` berjalan.");

        return self::SUCCESS;
    }

    /**
     * Log diagnostik: event -> organizer -> user -> whatsapp (per event, sekali saja).
     */
    private function logAdminLookup(?\App\Models\Event $event, int $itemId, string $type, array &$seen): void
    {
        if (! $event) {
            $this->line("  [{$type} #{$itemId}] Event: (null)");

            return;
        }
        $key = $event->id;
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $organizer = $event->organizer;
        $user = $organizer?->user;
        $wa = $user && trim((string) $user->whatsapp) !== '' ? $user->whatsapp : '(kosong/tidak ada)';
        $this->line("  Event: {$event->title} (id={$event->id}) | Organizer: ".($organizer?->name ?? 'null').' (id='.($organizer?->id ?? 'null').') | User: '.($user?->name ?? 'null').' (id='.($user?->id ?? 'null').") | WA: {$wa}");
    }
}
