<?php

namespace App\Services;

use App\Mail\PaymentLinkMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\WhatsappNotificationLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

/**
 * Kirim notifikasi "link pembayaran" (WhatsApp + email) ke orang tua rider.
 * Dipakai alur checkout publik dan bulk import registrasi (mode menunggu pembayaran).
 */
class PaymentLinkNotifier
{
    /**
     * @param  ?int  $deadlineMinutes  Lama link masih aktif (menit). Default: batas upload bukti standar.
     */
    public static function send(Order $order, Registration $reg, ?int $deadlineMinutes = null): void
    {
        $reg->loadMissing(['rider.user', 'event']);
        $user = $reg->rider->user;
        if (! $user) {
            return;
        }

        $event = $reg->event;
        $paymentLinkUrl = route('payment.create', array_filter([
            'order_code' => $order->order_code,
            'whatsapp' => $user->whatsapp ?: null,
            'payment_method' => $event->allowsQrisPayment() ? 'qris' : ($event->allowsManualPayment() ? 'manual' : null),
        ], static fn ($v) => $v !== null && $v !== ''));
        $eventTitle = $reg->event->title ?? config('app.name');
        $recipientName = $user->name ?: $reg->rider->name;
        $order->loadMissing('payments');
        $pending = $order->activePendingPayment();
        $qrisExactTotalIdr = null;
        if ($pending && $pending->method === Payment::METHOD_QRIS && (float) ($pending->transfer_amount ?? 0) > 0) {
            $qrisExactTotalIdr = 'Rp '.number_format((float) $pending->transfer_amount, 0, ',', '.');
        }

        if ($user->whatsapp) {
            $waMessage = trim(View::make('whatsapp.payment-link', [
                'recipientName' => $recipientName,
                'eventTitle' => $eventTitle,
                'registration' => $reg->loadMissing(['rider', 'bracket', 'package', 'event.organizer.users']),
                'paymentLinkUrl' => $paymentLinkUrl,
                'paymentProofDeadlineMinutes' => $deadlineMinutes ?? Payment::PAYMENT_PROOF_DEADLINE_MINUTES,
            ])->render());
            $logId = null;
            if (WhatsappNotificationLog::tableExists()) {
                $logId = $reg->whatsappNotificationLogs()->create([
                    'type' => WhatsappNotificationLog::TYPE_PAYMENT_LINK,
                    'recipient' => WhacenterService::normalizeWhatsApp($user->whatsapp),
                    'status' => WhatsappNotificationLog::STATUS_QUEUED,
                ])->id;
            }
            app(WhacenterService::class)->queueMessage($user->whatsapp, $waMessage, $logId);
        }

        if ($user->email) {
            Mail::to($user->email)->send(new PaymentLinkMail(
                $paymentLinkUrl,
                $eventTitle,
                $recipientName,
                $qrisExactTotalIdr,
            ));
        }
    }
}
