<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\WhatsappNotificationLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class ManualTransferNotifier
{
    public static function transferProofSubmitted(Registration $registration): void
    {
        $registration->loadMissing(['rider.user', 'event']);
        $user = $registration->rider?->user;
        if (! $user) {
            return;
        }

        $eventTitle = $registration->event?->title ?? config('app.name');
        $name = $user->name ?: $registration->rider?->name ?: '';

        if ($user->whatsapp) {
            $wa = trim(View::make('whatsapp.transfer-proof-submitted', [
                'recipientName' => $name,
                'eventTitle' => $eventTitle,
            ])->render());
            $logId = null;
            if (WhatsappNotificationLog::tableExists()) {
                $logId = $registration->whatsappNotificationLogs()->create([
                    'type' => WhatsappNotificationLog::TYPE_TRANSFER_PROOF_SUBMITTED,
                    'recipient' => WhacenterService::normalizeWhatsApp($user->whatsapp),
                    'status' => WhatsappNotificationLog::STATUS_QUEUED,
                ])->id;
            }
            app(WhacenterService::class)->queueMessage($user->whatsapp, $wa, $logId);
        }

        if ($user->email) {
            Mail::send('emails.transfer-proof-submitted', [
                'name' => $name,
                'eventTitle' => $eventTitle,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject(__('Transfer proof received'));
            });
        }
    }

    public static function registrationRejected(Registration $registration, bool $hadSuccessfulPayment, ?string $reason = null): void
    {
        $registration->loadMissing(['rider.user', 'event']);
        $user = $registration->rider?->user;
        if (! $user) {
            return;
        }

        $eventTitle = $registration->event?->title ?? config('app.name');
        $name = $user->name ?: $registration->rider?->name ?: '';

        if ($user->whatsapp) {
            $wa = trim(View::make('whatsapp.registration-rejected', [
                'recipientName' => $name,
                'eventTitle' => $eventTitle,
                'hadSuccessfulPayment' => $hadSuccessfulPayment,
                'reason' => $reason,
            ])->render());
            $logId = null;
            if (WhatsappNotificationLog::tableExists()) {
                $logId = $registration->whatsappNotificationLogs()->create([
                    'type' => WhatsappNotificationLog::TYPE_REGISTRATION_REJECTED,
                    'recipient' => WhacenterService::normalizeWhatsApp($user->whatsapp),
                    'status' => WhatsappNotificationLog::STATUS_QUEUED,
                ])->id;
            }
            app(WhacenterService::class)->queueMessage($user->whatsapp, $wa, $logId);
        }

        if ($user->email) {
            Mail::send('emails.registration-rejected', [
                'name' => $name,
                'eventTitle' => $eventTitle,
                'hadSuccessfulPayment' => $hadSuccessfulPayment,
                'reason' => $reason,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject(__('Registration rejected'));
            });
        }
    }

    public static function paymentRejected(Registration $registration, ?string $reason = null): void
    {
        $registration->loadMissing(['rider.user', 'event']);
        $user = $registration->rider?->user;
        if (! $user) {
            return;
        }

        $eventTitle = $registration->event?->title ?? config('app.name');
        $name = $user->name ?: $registration->rider?->name ?: '';

        if ($user->whatsapp) {
            $wa = trim(View::make('whatsapp.payment-rejected', [
                'recipientName' => $name,
                'eventTitle' => $eventTitle,
                'reason' => $reason,
            ])->render());
            $logId = null;
            if (WhatsappNotificationLog::tableExists()) {
                $logId = $registration->whatsappNotificationLogs()->create([
                    'type' => WhatsappNotificationLog::TYPE_PAYMENT_REJECTED,
                    'recipient' => WhacenterService::normalizeWhatsApp($user->whatsapp),
                    'status' => WhatsappNotificationLog::STATUS_QUEUED,
                ])->id;
            }
            app(WhacenterService::class)->queueMessage($user->whatsapp, $wa, $logId);
        }

        if ($user->email) {
            Mail::send('emails.payment-rejected', [
                'name' => $name,
                'eventTitle' => $eventTitle,
                'reason' => $reason,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject(__('Payment could not be verified'));
            });
        }
    }

    public static function paymentExpired(Registration $registration): void
    {
        $registration->loadMissing(['rider.user', 'event']);
        $user = $registration->rider?->user;
        if (! $user) {
            return;
        }

        $eventTitle = $registration->event?->title ?? config('app.name');
        $name = $user->name ?: $registration->rider?->name ?: '';

        if ($user->whatsapp) {
            $wa = trim(View::make('whatsapp.payment-expired', [
                'recipientName' => $name,
                'eventTitle' => $eventTitle,
            ])->render());
            $logId = null;
            if (WhatsappNotificationLog::tableExists()) {
                $logId = $registration->whatsappNotificationLogs()->create([
                    'type' => WhatsappNotificationLog::TYPE_PAYMENT_EXPIRED,
                    'recipient' => WhacenterService::normalizeWhatsApp($user->whatsapp),
                    'status' => WhatsappNotificationLog::STATUS_QUEUED,
                ])->id;
            }
            app(WhacenterService::class)->queueMessage($user->whatsapp, $wa, $logId);
        }

        if ($user->email) {
            Mail::send('emails.payment-expired', [
                'name' => $name,
                'eventTitle' => $eventTitle,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject(__('Payment window expired'));
            });
        }
    }
}
