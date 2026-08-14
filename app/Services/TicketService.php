<?php

namespace App\Services;

use App\Mail\TicketIssuedMail;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\WhatsappNotificationLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class TicketService
{
    /**
     * Buat ticket untuk registration jika:
     * - Payment sudah verified (status success)
     * - Registration sudah approved
     * - Belum ada ticket.
     * Dipanggil setelah payment approve atau registration approve.
     *
     * Saat ticket baru dibuat pertama kali, kirim notifikasi
     * via WhatsApp (Whacenter) dan email ke orang tua.
     */
    public static function ensureTicketForRegistration(Registration $registration): ?Ticket
    {
        $registration->loadMissing(['payment', 'ticket', 'rider.user', 'event', 'bracket', 'package', 'order']);

        if ($registration->ticket) {
            return $registration->ticket;
        }

        $payment = $registration->payment;
        if (! $payment || ! $payment->isSuccess()) {
            return null;
        }

        if (! $registration->isApproved()) {
            return null;
        }

        $ticket = $registration->ticket()->create([]);

        self::sendTicketNotifications($ticket);

        return $ticket;
    }

    /**
     * Kirim ulang pesan WhatsApp e-ticket (template sama dengan pengiriman pertama).
     *
     * @return string|null Pesan error, atau null jika berhasil di-queue.
     */
    public static function resendTicketWhatsapp(Registration $registration): ?string
    {
        $registration->loadMissing(['ticket', 'rider.user', 'event.organizer.user', 'bracket', 'package', 'order']);
        $ticket = $registration->ticket;
        if (! $ticket) {
            return __('No e-ticket exists for this registration yet.');
        }

        $user = $registration->rider?->user;
        if (! $user?->whatsapp) {
            return __('This rider has no WhatsApp number on file.');
        }

        $waMessage = self::buildPaymentSuccessWhatsAppBody($registration, $ticket);

        $logId = null;
        if (WhatsappNotificationLog::tableExists()) {
            $logId = $registration->whatsappNotificationLogs()->create([
                'type' => WhatsappNotificationLog::TYPE_TICKET_RESENT,
                'recipient' => WhacenterService::normalizeWhatsApp($user->whatsapp),
                'status' => WhatsappNotificationLog::STATUS_QUEUED,
            ])->id;
        }
        app(WhacenterService::class)->queueMessage($user->whatsapp, $waMessage, $logId);

        return null;
    }

    /**
     * URL wa.me berisi pesan e-ticket (tanpa menambah click count).
     *
     * @return array{0: ?string, 1: ?string} [wa.me URL, error message]
     */
    public static function ticketWaMeUrl(Registration $registration): array
    {
        $registration->loadMissing(['ticket', 'rider.user', 'event.organizer.user', 'bracket', 'package', 'order']);
        $ticket = $registration->ticket;
        if (! $ticket) {
            return [null, __('No e-ticket exists for this registration yet.')];
        }

        $user = $registration->rider?->user;
        if (! $user?->whatsapp) {
            return [null, __('This rider has no WhatsApp number on file.')];
        }

        $number = WhacenterService::normalizeWhatsApp($user->whatsapp);
        $message = self::buildPaymentSuccessWhatsAppBody($registration, $ticket);
        $url = 'https://wa.me/'.$number.'?text='.rawurlencode($message);

        return [$url, null];
    }

    /**
     * Naikkan click count tombol kirim tiket manual (wa.me).
     *
     * @return array{0: ?int, 1: ?string, 2: ?string} [count, wa.me URL, error]
     */
    public static function recordManualWaMeTicketClick(Registration $registration): array
    {
        [$url, $error] = self::ticketWaMeUrl($registration);
        if ($error || ! $url) {
            return [null, null, $error ?? __('Unable to open WhatsApp.')];
        }

        $ticket = $registration->ticket;
        $ticket->increment('manual_wa_send_count');
        $ticket->refresh();

        return [(int) $ticket->manual_wa_send_count, $url, null];
    }

    /**
     * Isi teks WhatsApp untuk e-ticket (view `whatsapp.payment-success`).
     */
    public static function buildPaymentSuccessWhatsAppBody(Registration $registration, Ticket $ticket): string
    {
        $registration->loadMissing(['rider.user', 'event.organizer.user', 'bracket', 'package', 'order']);
        $user = $registration->rider?->user;
        $eventTitle = $registration->event?->title ?? config('app.name');
        $recipientName = $user?->name ?: $registration->rider?->name ?: '';
        $ticketUrl = route('tickets.show', $ticket->ticket_code);
        $qrUrl = route('tickets.qr', $ticket->ticket_code);

        return trim(View::make('whatsapp.payment-success', [
            'recipientName' => $recipientName,
            'eventTitle' => $eventTitle,
            'registration' => $registration,
            'ticketUrl' => $ticketUrl,
            'qrUrl' => $qrUrl,
        ])->render());
    }

    /**
     * Kirim notifikasi ticket terbit (payment success + registration approved):
     * - WhatsApp via Whacenter
     * - Email dengan link e-ticket dan QR code.
     */
    protected static function sendTicketNotifications(Ticket $ticket): void
    {
        $registration = $ticket->registration->loadMissing(['rider.user', 'event.organizer.user', 'bracket', 'package', 'order']);
        $user = $registration->rider?->user;

        if (! $user) {
            return;
        }

        if ($user->whatsapp) {
            $waMessage = self::buildPaymentSuccessWhatsAppBody($registration, $ticket);

            $logId = null;
            if (WhatsappNotificationLog::tableExists()) {
                $logId = $registration->whatsappNotificationLogs()->create([
                    'type' => WhatsappNotificationLog::TYPE_TICKET_ISSUED,
                    'recipient' => WhacenterService::normalizeWhatsApp($user->whatsapp),
                    'status' => WhatsappNotificationLog::STATUS_QUEUED,
                ])->id;
            }
            app(WhacenterService::class)->queueMessage($user->whatsapp, $waMessage, $logId);
        }

        if ($user->email) {
            Mail::to($user->email)->send(new TicketIssuedMail($ticket));
        }
    }
}
