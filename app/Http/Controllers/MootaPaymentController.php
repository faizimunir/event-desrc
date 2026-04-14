<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMootaWebhookEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MootaSignature;
use App\Services\WhacenterService;
use App\Services\WinpayQrisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MootaPaymentController extends Controller
{
    /**
     * Public: pilih pembayaran Moota.
     *
     * Catatan:
     * - Untuk transfer bank (mutasi), kita pakai nominal unik (moota_transfer_amount) agar bisa dicocokkan.
     * - Untuk Moota Dynamic QRIS, biasanya webhook membawa payment_detail.order_id/trx_id untuk pencocokan.
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

        $transferAmount = $this->allocateUniqueMootaAmount($baseAmount);

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

        $order->refresh();
        $payment->refresh();

        $winpay = app(WinpayQrisService::class);
        if ($winpay->isConfigured()) {
            try {
                $winpay->generateDynamicQris($order, $payment);
            } catch (\Throwable $e) {
                Log::error('Winpay QRIS generate failed', [
                    'order_code' => $order->order_code,
                    'payment_id' => $payment->id,
                    'message' => $e->getMessage(),
                ]);
                $payment->forceFill([
                    'winpay_qr_url' => null,
                    'winpay_qr_content' => null,
                    'winpay_contract_id' => null,
                    'winpay_partner_reference_no' => null,
                    'winpay_expired_at' => null,
                    'winpay_external_id' => null,
                    'winpay_raw' => null,
                ])->save();

                $flash = $this->winpayQrisUserErrorMessage($e->getMessage());

                return redirect()->route('payment.create', [
                    'order_code' => $order->order_code,
                    'whatsapp' => $validated['whatsapp'],
                    'payment_method' => 'qris',
                ])->with('error', $flash);
            }
        }

        return redirect()->route('payment.create', [
            'order_code' => $order->order_code,
            'whatsapp' => $validated['whatsapp'],
            'payment_method' => 'qris',
        ])->with('status', __('Transfer the exact amount shown below. Payment will be confirmed automatically when we receive it.'));
    }

    /**
     * Webhook: Moota (mutasi bank / Dynamic QRIS) (POST + HMAC Signature).
     */
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

        // Best practice (sesuai tips Moota): simpan dulu, balas cepat, proses via queue.
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

        ProcessMootaWebhookEvent::dispatch((int) $eventId);

        return response()->json(['message' => 'received'], 200);
    }

    /**
     * Nominal transfer = harga paket + sufiks unik (1–999) agar mutasi terpisah per order.
     */
    private function allocateUniqueMootaAmount(float $baseAmount): float
    {
        $base = (int) round($baseAmount);

        for ($i = 0; $i < 50; $i++) {
            $suffix = random_int(1, 999);
            $candidate = (float) ($base + $suffix);

            $exists = Payment::pendingTransferAmountExists($candidate);

            if (! $exists) {
                return $candidate;
            }
        }

        // Fallback: sufiks lebih besar jika tabrakan berlebihan
        return (float) ($base + random_int(1000, 9999));
    }

    private function winpayQrisUserErrorMessage(string $technicalMessage): string
    {
        $base = __('QRIS could not be generated. You can still pay using the bank transfer amount below.');

        if (Str::contains($technicalMessage, ['4014700', 'Invalid signature', '4015100'])) {
            return $base.' '.__('Winpay rejected the request signature. Use the private key PEM that belongs to this merchant account, and make sure the matching public key is registered in the Winpay dashboard (not a random test key).');
        }

        if (Str::contains($technicalMessage, ['4044716', 'Partner not found'])) {
            return $base.' '.__('Check WINPAY_PARTNER_ID (ID merchant in Winpay) and WINPAY_BASE_URL (sandbox vs production).');
        }

        if (Str::contains($technicalMessage, ['4094700', 'X-EXTERNAL-ID', '4094701', 'Duplicate partnerReferenceNo'])) {
            return $base.' '.__('Please tap “Use Moota” once more to get a new QRIS reference.');
        }

        if (Str::contains($technicalMessage, ['HTTP 404', 'Winpay: WINPAY_BASE_URL'])) {
            return $base.' '.__('The Winpay API URL is wrong or missing. Production must be https://snap.winpay.id (no /snap segment). After changing .env, run: php artisan config:clear');
        }

        if (config('app.debug')) {
            return $base.' '.$technicalMessage;
        }

        return $base.' '.__('If this keeps happening, contact the organizer.');
    }
}
