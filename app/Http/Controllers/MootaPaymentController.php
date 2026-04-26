<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMootaWebhookEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MootaSignature;
use App\Services\MootaWebhookProcessor;
use App\Services\WhacenterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MootaPaymentController extends Controller
{
    /**
     * Public: pilih pembayaran Moota.
     *
     * Catatan:
     * - Untuk transfer bank / QRIS statis, kita pakai nominal unik (moota_transfer_amount)
     *   agar bisa dicocokkan otomatis dari webhook Moota.
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'order_code' => ['required', 'string', 'exists:orders,order_code'],
            'whatsapp' => ['required', 'string', 'max:20'],
        ]);

        $order = Order::with(['registration.event.accounts', 'registration.rider.user', 'registration.package', 'payments'])
            ->where('order_code', $validated['order_code'])
            ->firstOrFail();

        if ($order->isExpired() || $order->isCancelled()) {
            return redirect()->route('payment.create')
                ->withErrors(['order_code' => __('This order has expired or was cancelled.')])
                ->withInput();
        }

        $registration = $order->registration;
        if (! $registration->event->allowsQrisPayment()) {
            return redirect()->route('payment.create', [
                'order_code' => $validated['order_code'],
                'whatsapp' => $validated['whatsapp'],
            ])->with('error', __('QRIS / automatic payment is not available for this event.'));
        }
        $normalized = WhacenterService::normalizeWhatsApp($validated['whatsapp']);
        $matchesWhatsapp = $normalized && $registration->rider->user->whatsapp === $normalized;
        $ownedByVisitor = $order->isOwnedByCurrentVisitor();
        if (! $matchesWhatsapp && ! $ownedByVisitor) {
            return redirect()->route('payment.create')
                ->withErrors(['whatsapp' => __('WhatsApp number does not match this order.')])
                ->withInput();
        }

        if ($order->isDraft()) {
            if (! $order->finalizeForPayment()) {
                return redirect()->route('payment.create', [
                    'order_code' => $validated['order_code'],
                    'whatsapp' => $validated['whatsapp'],
                ])->with('error', __('There is no remaining quota for this bracket or package.'));
            }
            $order->refresh();
        }

        $baseAmount = round($registration->package ? $registration->package->payableAmount() : 0, 2);

        $payment = $order->activePendingPayment();
        if ($payment && $payment->method !== 'moota') {
            $payment = $order->createNewPaymentAttempt([
                'amount' => $baseAmount,
                'method' => 'moota',
                'status' => Payment::STATUS_PENDING,
                'expires_at' => $order->expired_at,
            ]);
        } elseif (! $payment) {
            $payment = $order->createNewPaymentAttempt([
                'amount' => $baseAmount,
                'method' => 'moota',
                'status' => Payment::STATUS_PENDING,
                'expires_at' => $order->expired_at,
            ]);
        }

        if ($payment->isSuccess()) {
            return redirect()->route('payment.create', [
                'order_code' => $order->order_code,
                'whatsapp' => $validated['whatsapp'],
                'payment_method' => 'qris',
            ])->with('status', __('Your payment has been verified.'));
        }

        $transferAmount = $payment->moota_transfer_amount !== null
            ? (float) $payment->moota_transfer_amount
            : Payment::stableMootaTransferAmountForOrder($order, $baseAmount);

        $payment->forceFill([
            'method' => 'moota',
            'amount' => $baseAmount,
            'moota_transfer_amount' => $transferAmount,
            'status' => Payment::STATUS_PENDING,
            'expires_at' => $order->expired_at,
            'transfer_proof_path' => null,
            'manual_account_id' => null,
            'manual_transfer_amount' => null,
            'admin_notes' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ])->save();

        return redirect()->route('payment.create', [
            'order_code' => $order->order_code,
            'whatsapp' => $validated['whatsapp'],
            'payment_method' => 'qris',
        ])->with('status', __('Transfer the exact amount shown below. Payment will be confirmed automatically when we receive it.'));
    }

    /** Webhook: Moota (mutasi bank / QRIS statis) (POST + HMAC Signature). */
    public function webhook(Request $request)
    {
        $secret = (string) config('moota.webhook_secret');
        if ($secret === '') {
            abort(503, 'MOOTA_WEBHOOK_SECRET is empty. Set it in server .env to the same secret as the Moota webhook, then run php artisan config:clear (or config:cache).');
        }

        $raw = $request->getContent();
        $signature = $request->header('Signature');

        if (! MootaSignature::verify($raw, $secret, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $user = config('moota.webhook_user');
        $token = config('moota.webhook_token');
        if ($user !== null && $user !== '' && $request->header('X-MOOTA-USER') !== $user) {
            return response()->json(['message' => 'Invalid webhook user'], 401);
        }
        if ($token !== null && $token !== '' && $request->header('X-MOOTA-WEBHOOK') !== $token) {
            return response()->json(['message' => 'Invalid webhook token'], 401);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid JSON'], 400);
        }

        $eventId = DB::table('moota_webhook_events')->insertGetId([
            'signature' => $signature,
            'moota_user' => $request->header('X-MOOTA-USER'),
            'moota_webhook' => $request->header('X-MOOTA-WEBHOOK'),
            'headers' => json_encode($request->headers->all()),
            'raw_body' => $raw,
            'payload' => json_encode($payload),
            'status' => 'received',
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $eventId = (int) $eventId;

        if (config('moota.webhook_sync', true)) {
            try {
                app(MootaWebhookProcessor::class)->processEventId($eventId);
            } catch (\Throwable $e) {
                Log::error('moota.webhook.sync_failed', [
                    'moota_webhook_event_id' => $eventId,
                    'message' => $e->getMessage(),
                ]);

                return response()->json(['message' => 'Processing failed'], 500);
            }
        } else {
            ProcessMootaWebhookEvent::dispatch($eventId);
        }

        return response()->json(['message' => 'received'], 200);
    }
}
