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
use Illuminate\Http\Request;
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
        $rawPaymentMethod = $request->old('payment_method', $request->query('payment_method'));
        $preferredPaymentMethod = in_array($rawPaymentMethod, ['manual', 'qris'], true) ? $rawPaymentMethod : null;

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
                    $queuePaymentLinkNotification = false;
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
                            // WA/email di bawah, setelah attempt QRIS/manual dibuat, supaya nominal unik sudah tercatat.
                            $queuePaymentLinkNotification = true;
                        }
                    }

                    if ($registration && $order && ! $order->isDraft()) {
                        $baseAmount = $reg->package ? (float) $reg->package->price : 0.0;
                        $adminFeeAmount = ($reg->package && ! $reg->package->adminFeeIsIncludedInPrice())
                            ? (float) $reg->package->admin_fee
                            : 0.0;
                        $expires = $order->expired_at;
                        $eventAllowsQris = $reg->event->allowsQrisPayment();
                        $eventAllowsManual = $reg->event->allowsManualPayment();
                        $wantManual = $preferredPaymentMethod === 'manual';
                        $wantQris = $preferredPaymentMethod === 'qris';
                        $active = $order->activePendingPayment();
                        $paymentMethodLocked = $active && (
                            $active->isSubmitted()
                            || ($active->isPending() && ! empty($active->transfer_proof_path))
                        );
                        $methodClash = ! $paymentMethodLocked && $active && (
                            ($wantManual && $active->method !== 'manual')
                            || ($wantQris && $active->method !== Payment::METHOD_QRIS)
                        );

                        $keepExistingManual = ! $methodClash && $active && $active->method === 'manual'
                            && $active->isPending() && $active->transfer_amount !== null;

                        // Saat metode sudah dipilih di halaman sebelumnya, langsung siapkan payment attempt
                        // agar halaman bayar bisa langsung menampilkan instruksi final (tanpa klik lanjutan).
                        $shouldCreateAttempt = $order->isPendingUnpaid()
                            && ! $paymentMethodLocked
                            && (! $active || $methodClash);

                        if ($paymentMethodLocked && $active && is_string($active->method) && $active->method !== '') {
                            $preferredPaymentMethod = $active->method;
                        }

                        if ($shouldCreateAttempt) {
                            if ($wantQris && $eventAllowsQris) {
                                $components = Payment::buildTransferComponentsForOrder($order, $baseAmount, $adminFeeAmount);
                                $order->createNewPaymentAttempt([
                                    'amount' => $components['amount'],
                                    'admin_fee_amount' => $components['admin_fee_amount'],
                                    'unique_code' => $components['unique_code'],
                                    'transfer_amount' => $components['transfer_amount'],
                                    'method' => Payment::METHOD_QRIS,
                                    'status' => Payment::STATUS_PENDING,
                                    'expires_at' => $expires,
                                ]);
                            } elseif ($wantManual && $eventAllowsManual && ! $keepExistingManual) {
                                $components = Payment::buildTransferComponentsForOrder($order, $baseAmount, $adminFeeAmount);
                                $order->createNewPaymentAttempt([
                                    'amount' => $components['amount'],
                                    'admin_fee_amount' => $components['admin_fee_amount'],
                                    'unique_code' => $components['unique_code'],
                                    'transfer_amount' => $components['transfer_amount'],
                                    'method' => 'manual',
                                    'status' => Payment::STATUS_PENDING,
                                    'expires_at' => $expires,
                                ]);
                            } elseif (! $wantQris && ! $wantManual && ! $keepExistingManual) {
                                // Tanpa `payment_method` di URL: default ke Moota jika QRIS diizinkan, supaya tidak
                                // (sebelumnya) jatuh ke manual lalu section QRIS "terkunci" (deltae sering bawa ?qris).
                                if ($eventAllowsQris) {
                                    $components = Payment::buildTransferComponentsForOrder($order, $baseAmount, $adminFeeAmount);
                                    $order->createNewPaymentAttempt([
                                        'amount' => $components['amount'],
                                        'admin_fee_amount' => $components['admin_fee_amount'],
                                        'unique_code' => $components['unique_code'],
                                        'transfer_amount' => $components['transfer_amount'],
                                        'method' => Payment::METHOD_QRIS,
                                        'status' => Payment::STATUS_PENDING,
                                        'expires_at' => $expires,
                                    ]);
                                } elseif ($eventAllowsManual) {
                                    $components = Payment::buildTransferComponentsForOrder($order, $baseAmount, $adminFeeAmount);
                                    $order->createNewPaymentAttempt([
                                        'amount' => $components['amount'],
                                        'admin_fee_amount' => $components['admin_fee_amount'],
                                        'unique_code' => $components['unique_code'],
                                        'transfer_amount' => $components['transfer_amount'],
                                        'method' => 'manual',
                                        'status' => Payment::STATUS_PENDING,
                                        'expires_at' => $expires,
                                    ]);
                                }
                            }
                        } elseif ($order->isPendingUnpaid() && $active && $active->isPending() && $active->expires_at === null) {
                            $active->update(['expires_at' => $expires]);
                        }

                        $order->refresh();
                        $ensureManual = $order->activePendingPayment();
                        if ($ensureManual && $ensureManual->method === 'manual' && $ensureManual->isPending()
                            && $ensureManual->transfer_amount === null) {
                            $components = Payment::buildTransferComponentsForOrder($order, $baseAmount, $adminFeeAmount);
                            $ensureManual->update([
                                'amount' => $components['amount'],
                                'admin_fee_amount' => $components['admin_fee_amount'],
                                'unique_code' => $components['unique_code'],
                                'transfer_amount' => $components['transfer_amount'],
                            ]);
                        } elseif ($ensureManual && $ensureManual->method === Payment::METHOD_QRIS && $ensureManual->isPending()
                            && (float) ($ensureManual->transfer_amount ?? 0) <= 0) {
                            $components = Payment::buildTransferComponentsForOrder($order, $baseAmount, $adminFeeAmount);
                            $ensureManual->update([
                                'amount' => $components['amount'],
                                'admin_fee_amount' => $components['admin_fee_amount'],
                                'unique_code' => $components['unique_code'],
                                'transfer_amount' => $components['transfer_amount'],
                            ]);
                        }

                        if ($queuePaymentLinkNotification) {
                            $order->refresh();
                            $this->sendPaymentLinkNotifications($order, $reg);
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
            'payment_method' => ['nullable', 'in:manual,qris'],
        ]);

        $order = Order::with('registration.rider.user')->where('order_code', $validated['order_code'])->firstOrFail();
        $order->enforceExpiredDraftIfNeeded();
        $order->enforceExpiredPaymentWindowIfNeeded();
        if ($order->isExpired() || $order->isCancelled()) {
            return redirect()->route('payment.create', array_filter([
                'order_code' => $order->order_code,
                'whatsapp' => $request->input('whatsapp'),
                'payment_method' => $validated['payment_method'] ?? null,
            ]))->withErrors(['order_code' => __('This order has expired or was cancelled.')])
                ->withInput();
        }
        $registration = $order->registration;
        $normalized = WhacenterService::normalizeWhatsApp($validated['whatsapp']);
        if ($registration->rider->user->whatsapp !== $normalized) {
            return redirect()->route('payment.create', array_filter([
                'order_code' => $order->order_code,
                'whatsapp' => $request->input('whatsapp'),
                'payment_method' => $validated['payment_method'] ?? null,
            ]))->withErrors(['whatsapp' => __('WhatsApp number does not match this order.')])
                ->withInput();
        }

        $params = array_filter([
            'order_code' => $order->order_code,
            'whatsapp' => $request->input('whatsapp'),
            'payment_method' => $validated['payment_method'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');

        return redirect()->route('payment.create', $params)->withInput();
    }

    /**
     * Public: polling status pembayaran untuk auto-refresh halaman payment.
     */
    public function status(Request $request)
    {
        $validated = $request->validate([
            'order_code' => ['required', 'string', 'exists:orders,order_code'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
        ]);

        $order = Order::with(['registration.rider.user', 'payments'])
            ->where('order_code', $validated['order_code'])
            ->firstOrFail();

        $order->enforceExpiredDraftIfNeeded();
        $order->enforceExpiredPaymentWindowIfNeeded();

        $registration = $order->registration;
        $normalized = ! empty($validated['whatsapp'])
            ? WhacenterService::normalizeWhatsApp($validated['whatsapp'])
            : null;
        $matchesWhatsapp = $normalized && $registration?->rider?->user?->whatsapp === $normalized;
        $ownedByVisitor = $order->isOwnedByCurrentVisitor();

        if (! $matchesWhatsapp && ! $ownedByVisitor) {
            abort(403);
        }

        $latestPayment = $order->payments()->latest('id')->first();

        return response()->json([
            'order_status' => $order->status,
            'is_paid' => (bool) $order->isPaid(),
            'payment_status' => $latestPayment?->status,
            'is_success' => (bool) ($order->isPaid() || $latestPayment?->isSuccess()),
        ]);
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

        $baseAmount = $registration->package ? (float) $registration->package->price : 0.0;
        $adminFeeAmount = ($registration->package && ! $registration->package->adminFeeIsIncludedInPrice())
            ? (float) $registration->package->admin_fee
            : 0.0;
        $components = Payment::buildTransferComponentsForOrder($order, $baseAmount, $adminFeeAmount);

        if ($order->isPaid()) {
            return redirect()->route('payment.create', ['order_code' => $order->order_code])
                ->with('error', __('This order is already paid.'))
                ->withInput();
        }

        $payment = $order->activePendingPayment();
        if (! $payment) {
            $payment = $order->createNewPaymentAttempt([
                'amount' => $components['amount'],
                'admin_fee_amount' => $components['admin_fee_amount'],
                'unique_code' => $components['unique_code'],
                'transfer_amount' => $components['transfer_amount'],
                'method' => 'manual',
                'status' => Payment::STATUS_PENDING,
                'expires_at' => $order->expired_at,
            ]);
        } elseif ($payment->transfer_amount === null && $payment->method === 'manual') {
            $payment->amount = $components['amount'];
            $payment->admin_fee_amount = $components['admin_fee_amount'];
            $payment->unique_code = $components['unique_code'];
            $payment->transfer_amount = $components['transfer_amount'];
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
        $payment->moota_mutation_id = null;
        $payment->moota_raw = null;
        $payment->admin_notes = null;
        $payment->reviewed_at = null;
        $payment->reviewed_by = null;
        $payment->status = Payment::STATUS_SUBMITTED;
        $payment->save();

        ManualTransferNotifier::transferProofSubmitted($registration);

        return redirect()->route('payment.create', array_filter([
            'order_code' => $order->order_code,
            'whatsapp' => $request->input('whatsapp'),
        ], static fn ($v) => $v !== null && $v !== ''))
            ->with('status', __('Transfer proof uploaded. We will verify and confirm your payment.'));
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

    /** Kirim payment link ke WhatsApp (Whacenter) dan email setelah user klik Confirm & Pay. */
    private function sendPaymentLinkNotifications(Order $order, Registration $reg): void
    {
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
            Mail::to($user->email)->send(new PaymentLinkMail(
                $paymentLinkUrl,
                $eventTitle,
                $recipientName,
                $qrisExactTotalIdr,
            ));
        }
    }
}
