<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Daftar order milik visitor untuk session saat ini saja.
     * Termasuk yang sudah paid agar status tampil benar.
     */
    public function index(Request $request)
    {
        $sessionId = $request->session()->getId();

        $query = Order::with(['registration.event', 'registration.rider', 'registration.bracket', 'registration.package', 'registration.payment'])
            ->where('session_id', $sessionId)
            ->latest();

        $orders = $query->paginate(15)->withQueryString();

        return view('orders-index', compact('orders'));
    }

    /**
     * Detail satu order (harus milik session/user).
     */
    public function show(Request $request, Order $order)
    {
        if (! $order->isOwnedByCurrentVisitor()) {
            abort(403, __('You do not have access to this order.'));
        }

        $order->load(['registration.event.location', 'registration.rider.user', 'registration.bracket', 'registration.package', 'registration.payment', 'registration.ticket']);

        return view('order-show', compact('order'));
    }
}
