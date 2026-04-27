<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\MootaWebhookService;
use App\Services\WhacenterService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MootaPaymentController extends Controller
{
    /**
     * Pilih / siapkan pembayaran QRIS (nominal unik, sama prinsip Herd/deltae).
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
        if ($payment && $payment->method !== Payment::METHOD_QRIS) {
            $amount = Payment::allocateUniqueQrisAmount((float) $baseAmount);
            $payment = $order->createNewPaymentAttempt([
                'amount' => $amount,
                'method' => Payment::METHOD_QRIS,
                'status' => Payment::STATUS_PENDING,
                'expires_at' => $order->expired_at,
            ]);
        } elseif (! $payment) {
            $amount = Payment::allocateUniqueQrisAmount((float) $baseAmount);
            $payment = $order->createNewPaymentAttempt([
                'amount' => $amount,
                'method' => Payment::METHOD_QRIS,
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

        $payAmount = $payment->amount !== null
            ? $payment->amount
            : Payment::allocateUniqueQrisAmount((float) $baseAmount);

        $payment->forceFill([
            'method' => Payment::METHOD_QRIS,
            'amount' => $payAmount,
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
        ])->with('status', __('Complete your payment using the QR code and exact amount below.'));
    }

    /** Webhook: sama alur `MootaWebhookController` di Herd/deltae. */
    public function webhook(Request $request, MootaWebhookService $service): Response
    {
        $secret = trim((string) config('services.moota.webhook_secret', ''));
        if ($secret === '') {
            Log::warning('moota.webhook.missing_secret', [
                'hint' => 'Set MOOTA_WEBHOOK_SECRET in .env (same as secret_token when creating the webhook in Moota), then run php artisan config:clear or php artisan config:cache.',
            ]);

            return response()->json([
                'error' => 'moota_webhook_secret_missing',
                'message' => 'MOOTA_WEBHOOK_SECRET is not set or empty. Add it to .env and refresh config cache.',
            ], 503);
        }

        $payload = $request->getContent();
        $signature = $request->header('Signature') ?? $request->header('signature');

        if (! $service->verifySignature($signature, $payload, $secret)) {
            Log::warning('moota.webhook.invalid_signature');

            return response('Unauthorized', 401);
        }

        $decoded = json_decode($payload, true);
        $mutations = $service->normalizePayload($decoded);

        foreach ($mutations as $mutation) {
            try {
                $service->processMutation($mutation);
            } catch (\Throwable $e) {
                Log::error('moota.webhook.process_error', [
                    'message' => $e->getMessage(),
                    'mutation_id' => $mutation['mutation_id'] ?? null,
                ]);

                return response('Internal error', 500);
            }
        }

        return response('OK', 200);
    }
}
