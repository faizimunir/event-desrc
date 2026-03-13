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
    protected $signature = 'organizers:pending-digest';

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
        if (! config('services.whacenter.device_id')) {
            $this->warn('WHACENTER_DEVICE_ID belum di-set, skip digest.');
            return self::SUCCESS;
        }

        $since = now()->subHour();

        // Pending orders (status pending_payment) yang baru dibuat dalam 1 jam terakhir
        $orders = Order::query()
            ->pendingPayment()
            ->where('created_at', '>=', $since)
            ->with(['registration.event.organizer.user'])
            ->get();

        // Pending payments yang baru dibuat dalam 1 jam terakhir
        $payments = Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->where('created_at', '>=', $since)
            ->with(['registration.event.organizer.user'])
            ->get();

        if ($orders->isEmpty() && $payments->isEmpty()) {
            $this->info('Tidak ada pending order/payment baru dalam 1 jam terakhir.');
            return self::SUCCESS;
        }

        // Group per admin organizer (user_id)
        $byOrganizer = [];

        foreach ($orders as $order) {
            $event = $order->registration?->event;
            $organizer = $event?->organizer;
            $user = $organizer?->user;

            if (! $user || ! $user->whatsapp) {
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

            if (! $user || ! $user->whatsapp) {
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

        if ($byOrganizer === []) {
            $this->info('Tidak ada organizer dengan WhatsApp untuk dikirimi digest.');
            return self::SUCCESS;
        }

        $totalSent = 0;

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
            $lines[] = 'Halo, ' . ($user->name ?? 'Admin') . '.';
            $lines[] = '';
            $lines[] = 'Ringkasan pendaftar yang masih menunggu pembayaran / review bukti dalam 1 jam terakhir:';
            $lines[] = '';

            foreach ($events as $evtData) {
                $event = $evtData['event'];
                $orderCount = $evtData['orders'] ?? 0;
                $paymentCount = $evtData['payments'] ?? 0;

                $title = $event?->title ?? '-';
                $eventLine = '• ' . $title . ': ';
                $parts = [];
                if ($orderCount > 0) {
                    $parts[] = $orderCount . ' pending order';
                }
                if ($paymentCount > 0) {
                    $parts[] = $paymentCount . ' pending payment';
                }

                if ($parts !== []) {
                    $eventLine .= implode(' + ', $parts);
                    $lines[] = $eventLine;
                }
            }

            $lines[] = '';
            $lines[] = 'Silakan cek dashboard DESRC untuk detail dan verifikasi pembayaran.';

            $message = implode("\n", $lines);

            if ($whacenter->sendMessage($user->whatsapp, $message)) {
                $this->info('Digest terkirim ke ' . $user->whatsapp);
                $totalSent++;
            } else {
                $this->error('Gagal mengirim digest ke ' . $user->whatsapp);
            }
        }

        $this->info("Total digest terkirim: {$totalSent}");

        return self::SUCCESS;
    }
}
