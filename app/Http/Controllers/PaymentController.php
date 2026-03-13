<?php

namespace App\Http\Controllers;

use App\Mail\PaymentLinkMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Registration;
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
        $bank = Payment::getManualBankInfo();
        $orderCode = $request->old('order_code', $request->query('order_code'));
        $whatsapp = $request->old('whatsapp', $request->query('whatsapp'));

        if ($orderCode) {
            $order = Order::with(['registration.event.account', 'registration.rider.user', 'registration.bracket', 'registration.package'])
                ->where('order_code', $orderCode)->first();
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
                    // Konfirmasi order (batas 15 menit sudah terlewati dengan klik Pay now)
                    if (! $order->confirmed_at) {
                        $order->update([
                            'confirmed_at' => now(),
                            'expired_at' => null,
                        ]);
                        $this->sendPaymentLinkNotifications($order, $reg);
                    }
                    // Buat payment pending dengan batas upload bukti 30 menit
                    $payment = $reg->payment;
                    if (! $payment) {
                        $payment = Payment::create([
                            'registration_id' => $reg->id,
                            'amount' => $reg->package?->price ?? 0,
                            'status' => Payment::STATUS_PENDING,
                            'expires_at' => now()->addMinutes(Payment::PAYMENT_PROOF_DEADLINE_MINUTES),
                        ]);
                    } elseif ($payment->isPending() && ! $payment->expires_at) {
                        $payment->update([
                            'expires_at' => now()->addMinutes(Payment::PAYMENT_PROOF_DEADLINE_MINUTES),
                        ]);
                    } elseif ($payment->isExpired() || $payment->isCancelled() || $payment->isFailed()) {
                        $payment->update([
                            'status' => Payment::STATUS_PENDING,
                            'expires_at' => now()->addMinutes(Payment::PAYMENT_PROOF_DEADLINE_MINUTES),
                            'transfer_proof_path' => null,
                            'admin_notes' => null,
                            'reviewed_at' => null,
                            'reviewed_by' => null,
                        ]);
                    }
                }
            }
        }

        $account = $registration?->event?->account;

        return view('payments.create', compact('registration', 'bank', 'account', 'orderCode', 'whatsapp', 'orderExpiredOrCancelled'));
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
        $validated = $request->validate([
            'order_code' => ['required', 'string', 'exists:orders,order_code'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'transfer_proof' => ['required', 'file', 'image', 'max:5120'], // 5MB
        ]);

        $order = Order::with('registration.rider.user', 'registration.package')->where('order_code', $validated['order_code'])->firstOrFail();
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

        $amount = $registration->package?->price ?? 0;

        $payment = $registration->payment;

        if ($payment) {
            if ($payment->isSuccess() || $payment->isFailed() || $payment->isCancelled()) {
                return redirect()->route('payment.create', ['order_code' => $order->order_code])
                    ->with('error', __('This payment has already been processed.'))
                    ->withInput();
            }
            if ($payment->isExpired()) {
                $payment->status = Payment::STATUS_PENDING;
                $payment->admin_notes = null;
                $payment->reviewed_at = null;
                $payment->reviewed_by = null;
            }
            if ($payment->transfer_proof_path && Storage::disk('public')->exists($payment->transfer_proof_path)) {
                Storage::disk('public')->delete($payment->transfer_proof_path);
            }
        } else {
            $payment = new Payment;
            $payment->registration_id = $registration->id;
            $payment->amount = $amount;
            $payment->status = Payment::STATUS_PENDING;
            $payment->expires_at = now()->addMinutes(Payment::PAYMENT_PROOF_DEADLINE_MINUTES);
        }

        $path = $request->file('transfer_proof')->store('payments/proofs', 'public');
        $payment->transfer_proof_path = $path;
        $payment->admin_notes = null;
        $payment->reviewed_at = null;
        $payment->reviewed_by = null;
        $payment->save();

        return redirect()->route('payment.create', [
            'order_code' => $order->order_code,
            'whatsapp' => $request->input('whatsapp'),
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

        if (! $payment->isPending()) {
            return redirect()->route('events.registrations.show', [$registration->event, $registration])
                ->with('error', __('Payment is not pending.'));
        }

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment->update([
            'status' => Payment::STATUS_SUCCESS,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        $payment->registration->order?->update(['status' => Order::STATUS_PAID]);

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

        if (! $payment->isPending()) {
            return redirect()->route('events.registrations.show', [$registration->event, $registration])
                ->with('error', __('Payment is not pending.'));
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

        $registration = $payment->registration;

        $payment->update(['status' => Payment::STATUS_EXPIRED]);

        $newOrder = Order::createNewOrderForRegistration($registration);

        return redirect()->route('payments.index')
            ->with('status', __('Payment marked as expired. New order code: :order_code. Send new payment link to the customer.', ['order_code' => $newOrder->order_code]))
            ->with('new_order_code', $newOrder->order_code);
    }

    /**
     * Kirim payment link ke WhatsApp (Whacenter) dan email setelah user klik Confirm & Pay.
     */
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
                'registration' => $reg->loadMissing(['rider', 'bracket', 'package']),
                'paymentLinkUrl' => $paymentLinkUrl,
                'paymentProofDeadlineMinutes' => Payment::PAYMENT_PROOF_DEADLINE_MINUTES,
            ])->render());
            app(WhacenterService::class)->sendMessage($user->whatsapp, $waMessage);
        }

        if ($user->email) {
            Mail::to($user->email)->send(new PaymentLinkMail($paymentLinkUrl, $eventTitle, $recipientName));
        }
    }
}
