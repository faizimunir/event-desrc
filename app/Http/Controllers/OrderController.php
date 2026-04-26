<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Daftar order milik visitor (session atau user), sama kriteria dengan badge navbar.
     * Default: hanya pending payment (sesuai angka badge); ?all=1 untuk riwayat lengkap.
     */
    public function index(Request $request)
    {
        $sessionId = $request->session()->getId();
        $showAll = $request->boolean('all');

        Order::enforceExpiredDraftsForVisitor($sessionId, $request->user()?->id);
        Order::enforceExpiredPaymentWindowsForVisitor($sessionId, $request->user()?->id);

        $query = Order::with(['registration.event', 'registration.rider', 'registration.bracket', 'registration.package', 'registration.payment', 'payments'])
            ->forCurrentVisitor($sessionId, $request->user()?->id)
            ->excludeAbandonedDraftTimeout()
            ->when(! $showAll, fn ($q) => $q->pendingPayment())
            ->latest();

        $orders = $query->paginate(15)->withQueryString();

        return view('orders-index', compact('orders', 'showAll'));
    }

    /**
     * Detail satu order (harus milik session/user).
     */
    public function show(Request $request, Order $order)
    {
        if (! $order->isOwnedByCurrentVisitor()) {
            abort(403, __('You do not have access to this order.'));
        }

        $order->enforceExpiredDraftIfNeeded();
        $order->enforceExpiredPaymentWindowIfNeeded();

        $order->load(['registration.event.location', 'registration.event.accounts', 'registration.rider.user', 'registration.rider.media', 'registration.bracket', 'registration.package', 'registration.payment', 'registration.ticket', 'payments']);

        $freshPayment = $request->boolean('change_payment_method');

        // If order is pending/unpaid and payment method already chosen, send user straight to payment page.
        if ($freshPayment) {
            return view('order-show', compact('order', 'freshPayment'));
        }

        $payment = $order->registration?->payment;
        $method = $payment?->method;
        $hasChosenMethod = is_string($method) && $method !== '';

        if ($order->isPendingUnpaid() && $payment && $payment->isPending() && $hasChosenMethod) {
            $e = $order->registration?->event;

            return redirect()->route('payment.create', array_filter([
                'order_code' => $order->order_code,
                'whatsapp' => $order->registration?->rider?->user?->whatsapp ?: null,
                'payment_method' => $e?->allowsQrisPayment() ? 'qris' : ($e?->allowsManualPayment() ? 'manual' : null),
            ], static fn ($v) => $v !== null && $v !== ''));
        }

        $freshPayment = false;

        return view('order-show', compact('order', 'freshPayment'));
    }
}
