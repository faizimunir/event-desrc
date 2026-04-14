<?php

namespace App\Http\Controllers;

use App\Mail\PaymentLinkMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\WhatsappNotificationLog;
use App\Services\ManualTransferNotifier;
use App\Services\TicketService;
use App\Services\WhacenterService;
use App\Services\WinpayQrisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Public: form bayar manual — verifikasi order_code (+ WhatsApp jika perlu), tampilkan rekening & form upload bukti.
     */
    public function create(Request $request)
    {
        $registration = null;
        $orderExpiredOrCancelled = false;
        $allowsManual = false;
        $allowsQris = false;
        $manualAccounts = collect();
        $bank = Payment::getManualBankInfo();
        $orderCode = $request->old('order_code', $request->query('order_code'));
        $whatsapp = $request->old('whatsapp', $request->query('whatsapp'));
        $preferredPaymentMethod = $request->query('payment_method');
        if (! in_array($preferredPaymentMethod, ['manual', 'qris'], true)) {
            $preferredPaymentMethod = null;
        }

        if ($orderCode) {
            $order = Order::with(['registration.event.accounts', 'registration.rider.user', 'registration.bracket', 'registration.package', 'payments'])
                ->where('order_code', $orderCode)->first();
            if ($order) {
                $order->enforceExpiredDraftIfNeeded();
                $order->enforceExpiredPaymentWindowIfNeeded();
            }
            if ($order && ($order->isExpired() || $order->isCancelled())) {
                $orderExpiredOrCancelled = true;
                $order = null;
            }
            if ($order) {
                $reg = $order->registration;
                $normalized = $whatsapp ? WhacenterService::normalizeWhatsApp($whatsapp) : null;
                $matchesWhatsapp = $normalized && $reg->rider->user->whatsapp === $normalized;
                $ownedByVisitor = $order->isOwnedByCurrentVisitor();
                if ($matchesWhatsapp || $ownedByVisitor) {
                    $registration = $reg;
                    if (! $whatsapp && $reg->rider->user->whatsapp) {
                        $whatsapp = $reg->rider->user->whatsapp;
                    }
                    // Resmikan order (draft → pending): FOR UPDATE bracket/package/order di dalam transaksi
                    if ($order->isDraft()) {
                        if (! $order->finalizeForPayment()) {
                            $registration = null;
                            $orderExpiredOrCancelled = true;
                            $request->session()->flash('error', __('There is no remaining quota for this bracket or package.'));
                        } else {
                            $order->refresh();
                            $this->sendPaymentLinkNotifications($order, $reg);
                        }
                    }

                    if ($registration && $order && ! $order->isDraft()) {
                        $freshPayment = $request->boolean('fresh_payment');
                        $amount = $reg->package ? $reg->package->payableAmount() : 0;
                        $expires = $order->expired_at;
                        $wantManual = $preferredPaymentMethod === 'manual';
                        $wantQris = $preferredPaymentMethod === 'qris';
                        $active = $order->activePendingPayment();
                        $methodClash = $active && (
                            ($wantManual && $active->method !== 'manual')
                            || ($wantQris && $active->method !== 'moota')
                        );

                        // QRIS/Moota: baris payment dibuat di MootaPaymentController@confirm (nominal unik).
                        if ($order->isPendingUnpaid() && ! $wantQris && ($freshPayment || $methodClash || ! $active)) {
                            $order->createNewPaymentAttempt([
                                'amount' => $amount,
                                'method' => 'manual',
                                'status' => Payment::STATUS_PENDING,
                                'expires_at' => $expires,
                                'manual_transfer_amount' => Payment::allocateUniqueManualTransferAmount((float) $amount),
                            ]);
                        } elseif ($order->isPendingUnpaid() && $active && $active->isPending() && $active->expires_at === null) {
                            $active->update(['expires_at' => $expires]);
                        }

                        $order->refresh();
                        $ensureManual = $order->activePendingPayment();
                        if ($ensureManual && $ensureManual->method === 'manual' && $ensureManual->isPending()
                            && $ensureManual->manual_transfer_amount === null) {
                            $ensureManual->update([
                                'manual_transfer_amount' => Payment::allocateUniqueManualTransferAmount((float) $amount),
                            ]);
                        }
                    }
                }
            }
        }

        if ($registration) {
            $registration->event->loadMissing('accounts');
            $allowsManual = $registration->event->allowsManualPayment();
            $allowsQris = $registration->event->allowsQrisPayment();
            $manualAccounts = $registration->event->accounts;
            if ($preferredPaymentMethod === 'manual' && ! $allowsManual) {
                $preferredPaymentMethod = null;
            }
            if ($preferredPaymentMethod === 'qris' && ! $allowsQris) {
                $preferredPaymentMethod = null;
            }

            $this->ensureWinpayQrisWhenViewingPaymentPage($registration);

            $registration->unsetRelation('payment');
        }

        return view('payments.create', compact(
            'registration',
            'bank',
            'manualAccounts',
            'allowsManual',
            'allowsQris',
            'preferredPaymentMethod',
            'orderCode',
            'whatsapp',
            'orderExpiredOrCancelled'
        ));
    }

    /**
     * Public: verifikasi order_code + whatsapp (POST untuk cek, lalu redirect ke form dengan data).
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'order_code' => ['required', 'string', 'exists:orders,order_code'],
            'whatsapp' => ['required', 'string', 'max:20'],
        ]);

        $order = Order::with('registration.rider.user')->where('order_code', $validated['order_code'])->firstOrFail();
        $order->enforceExpiredDraftIfNeeded();
        $order->enforceExpiredPaymentWindowIfNeeded();
        if ($order->isExpired() || $order->isCancelled()) {
            return redirect()->route('payment.create')
                ->withErrors(['order_code' => __('This order has expired or was cancelled.')])
                ->withInput();
        }
        $registration = $order->registration;
        $normalized = WhacenterService::normalizeWhatsApp($validated['whatsapp']);
        if ($registration->rider->user->whatsapp !== $normalized) {
            return redirect()->route('payment.create')
                ->withErrors(['whatsapp' => __('WhatsApp number does not match this order.')])
                ->withInput();
        }

        return redirect()->route('payment.create', [
            'order_code' => $order->order_code,
            'whatsapp' => $request->input('whatsapp'),
        ])->withInput();
    }

    /**
     * Public: simpan upload bukti transfer (create atau update payment pending).
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_code' => ['required', 'string', 'exists:orders,order_code'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'transfer_proof' => ['required', 'file', 'image', 'max:5120'], // 5MB
        ]);

        $order = Order::with(['registration.event.accounts', 'registration.rider.user', 'registration.package'])
            ->where('order_code', $request->input('order_code'))
            ->firstOrFail();
        $order->enforceExpiredDraftIfNeeded();
        $order->enforceExpiredPaymentWindowIfNeeded();
        if ($order->isExpired() || $order->isCancelled()) {
            return redirect()->route('payment.create')
                ->withErrors(['order_code' => __('This order has expired or was cancelled.')])
                ->withInput();
        }
        $registration = $order->registration;
        $normalized = WhacenterService::normalizeWhatsApp($request->input('whatsapp'));
        if ($registration->rider->user->whatsapp !== $normalized) {
            return redirect()->route('payment.create')
                ->withErrors(['whatsapp' => __('WhatsApp number does not match this order.')])
                ->withInput();
        }

        if ($order->isDraft()) {
            if (! $order->finalizeForPayment()) {
                return redirect()->route('payment.create', [
                    'order_code' => $order->order_code,
                    'whatsapp' => $request->input('whatsapp'),
                ])->with('error', __('There is no remaining quota for this bracket or package.'));
            }
            $order->refresh();
        }

        $event = $registration->event;
        if (! $event->allowsManualPayment()) {
            return redirect()->route('payment.create', [
                'order_code' => $order->order_code,
                'whatsapp' => $request->input('whatsapp'),
            ])->with('error', __('Manual transfer is not available for this event.'));
        }

        if ($event->accounts->isEmpty()) {
            return redirect()->route('payment.create', [
                'order_code' => $order->order_code,
                'whatsapp' => $request->input('whatsapp'),
            ])->with('error', __('No bank account is configured for this event.'));
        }

        $allowedAccountIds = $event->accounts->pluck('id')->all();
        $accountRules = $event->accounts->count() > 1
            ? ['required', 'integer', Rule::in($allowedAccountIds)]
            : ['nullable', 'integer'];

        $validatedProof = $request->validate([
            'manual_account_id' => $accountRules,
        ]);

        $manualAccountId = $event->accounts->count() === 1
            ? (int) $event->accounts->first()->id
            : (int) $validatedProof['manual_account_id'];

        if (! $event->accounts->pluck('id')->contains($manualAccountId)) {
            return redirect()->route('payment.create', [
                'order_code' => $order->order_code,
                'whatsapp' => $request->input('whatsapp'),
            ])->with('error', __('Invalid bank account selection.'));
        }

        $amount = $registration->package ? $registration->package->payableAmount() : 0;
        $manualTransferAmount = Payment::allocateUniqueManualTransferAmount((float) $amount);

        if ($order->isPaid()) {
            return redirect()->route('payment.create', ['order_code' => $order->order_code])
                ->with('error', __('This order is already paid.'))
                ->withInput();
        }

        $payment = $order->activePendingPayment();
        if (! $payment) {
            $payment = $order->createNewPaymentAttempt([
                'amount' => $amount,
                'method' => 'manual',
                'status' => Payment::STATUS_PENDING,
                'expires_at' => $order->expired_at,
                'manual_transfer_amount' => $manualTransferAmount,
            ]);
        } elseif ($payment->manual_transfer_amount === null && $payment->method === 'manual') {
            $payment->manual_transfer_amount = $manualTransferAmount;
        }

        if ($payment->isSuccess() || $payment->isFailed() || $payment->isCancelled()
            || $payment->isVoid() || $payment->isRefunded() || $payment->isExpired()) {
            return redirect()->route('payment.create', ['order_code' => $order->order_code])
                ->with('error', __('This payment has already been processed.'))
                ->withInput();
        }

        if ($payment->transfer_proof_path && Storage::disk('public')->exists($payment->transfer_proof_path)) {
            Storage::disk('public')->delete($payment->transfer_proof_path);
        }

        $path = $request->file('transfer_proof')->store('payments/proofs', 'public');
        $payment->transfer_proof_path = $path;
        $payment->method = 'manual';
        $payment->manual_account_id = $manualAccountId;
        $payment->moota_transfer_amount = null;
        $payment->moota_mutation_id = null;
        $payment->moota_raw = null;
        $payment->winpay_qr_url = null;
        $payment->winpay_qr_content = null;
        $payment->winpay_contract_id = null;
        $payment->winpay_partner_reference_no = null;
        $payment->winpay_expired_at = null;
        $payment->winpay_external_id = null;
        $payment->winpay_raw = null;
        $payment->admin_notes = null;
        $payment->reviewed_at = null;
        $payment->reviewed_by = null;
        $payment->status = Payment::STATUS_SUBMITTED;
        $payment->save();

        ManualTransferNotifier::transferProofSubmitted($registration);

        return redirect()->route('payment.create', [
            'order_code' => $order->order_code,
            'whatsapp' => $request->input('whatsapp'),
            'payment_method' => 'manual',
        ])->with('status', __('Transfer proof uploaded. We will verify and confirm your payment.'));
    }

    /**
     * Admin: daftar pembayaran (filter status).
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);

        $query = Payment::with(['registration.event', 'registration.rider', 'registration.bracket', 'registration.package', 'reviewedByUser']);

        $status = $request->query('status');
        if ($status && in_array($status, Payment::STATUSES, true)) {
            $query->where('status', $status);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return view('payments.index', compact('payments'));
    }

    /**
     * Admin: approve pembayaran (cek uang masuk, lalu approve).
     */
    public function approve(Request $request, Payment $payment)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        $registration = $payment->registration;

        if (! $payment->isPending() && ! $payment->isSubmitted()) {
            return redirect()->route('events.registrations.show', [$registration->event, $registration])
                ->with('error', __('Payment is not pending.'));
        }

        if ($payment->order && $payment->order->isPaid()) {
            return redirect()->route('events.registrations.show', [$registration->event, $registration])
                ->with('error', __('This order is already paid.'));
        }

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment->update([
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now(),
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        $ord = $payment->order;
        if ($ord && ! $ord->isPaid()) {
            $ord->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
            ]);
        }

        TicketService::ensureTicketForRegistration($payment->registration);

        return redirect()->route('events.registrations.show', [$registration->event, $registration])
            ->with('status', __('Payment approved.'));
    }

    /**
     * Admin: reject pembayaran (jika tidak ada uang masuk / bukti tidak valid).
     */
    public function reject(Request $request, Payment $payment)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        $registration = $payment->registration;

        if (! $payment->isPending() && ! $payment->isSubmitted()) {
            return redirect()->route('events.registrations.show', [$registration->event, $registration])
                ->with('error', __('Payment cannot be rejected in its current state.'));
        }

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        ManualTransferNotifier::paymentRejected($registration, $validated['admin_notes'] ?? null);

        return redirect()->route('events.registrations.show', [$registration->event, $registration])
            ->with('status', __('Payment rejected.'));
    }

    /**
     * Admin: tandai payment expired dan buat order baru (order_code baru). Order lama tidak dipakai lagi.
     */
    public function expire(Request $request, Payment $payment)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        if ($payment->isSuccess()) {
            return redirect()->route('payments.index')
                ->with('error', __('Cannot expire a successful payment.'));
        }

        $payment->update(['status' => Payment::STATUS_EXPIRED]);

        return redirect()->route('payments.index')
            ->with('status', __('Payment attempt marked as expired. Order is not cancelled by this action; use order deadline or cancel order if needed.'));
    }

    /**
     * Kirim payment link ke WhatsApp (Whacenter) dan email setelah user klik Confirm & Pay.
     */
    /**
     * Jika user sudah punya pembayaran Moota + nominal unik tapi QRIS Winpay belum ada
     * (mis. gagal saat POST, lalu .env diperbaiki), coba generate sekali saat GET halaman bayar.
     */
    private function ensureWinpayQrisWhenViewingPaymentPage(Registration $registration): void
    {
        $order = $registration->order;
        if (! $order || $order->isExpired() || $order->isCancelled()) {
            return;
        }

        $payment = $order->activePendingPayment();
        if (! $payment || $payment->method !== 'moota' || $payment->moota_transfer_amount === null) {
            return;
        }

        if (is_string($payment->winpay_qr_url) && $payment->winpay_qr_url !== '') {
            return;
        }

        $winpay = app(WinpayQrisService::class);
        if (! $winpay->isConfigured()) {
            return;
        }

        try {
            $winpay->generateDynamicQris($order, $payment);
        } catch (\Throwable $e) {
            Log::warning('Winpay QRIS lazy generate on payment page failed', [
                'order_code' => $order->order_code,
                'payment_id' => $payment->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function sendPaymentLinkNotifications(Order $order, Registration $reg): void
    {
        $user = $reg->rider->user;
        if (! $user) {
            return;
        }

        $paymentLinkUrl = route('payment.create', [
            'order_code' => $order->order_code,
            'whatsapp' => $user->whatsapp ?? '',
        ]);
        $eventTitle = $reg->event->title ?? config('app.name');
        $recipientName = $user->name ?: $reg->rider->name;

        if ($user->whatsapp) {
            $waMessage = trim(View::make('whatsapp.payment-link', [
                'recipientName' => $recipientName,
                'eventTitle' => $eventTitle,
                'registration' => $reg->loadMissing(['rider', 'bracket', 'package', 'event.organizer.user']),
                'paymentLinkUrl' => $paymentLinkUrl,
                'paymentProofDeadlineMinutes' => Payment::PAYMENT_PROOF_DEADLINE_MINUTES,
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
            Mail::to($user->email)->send(new PaymentLinkMail($paymentLinkUrl, $eventTitle, $recipientName));
        }
    }
}
