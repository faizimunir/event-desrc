<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Daftar order milik visitor: by session_id (guest) atau user_id (logged-in).
     */
    public function index(Request $request)
    {
        $query = Order::with(['registration.event', 'registration.rider', 'registration.bracket', 'registration.package', 'registration.payment'])
            ->pendingPayment();

        $sessionId = $request->session()->getId();
        $userId = $request->user()?->id;

        $query->where(function ($q) use ($sessionId, $userId) {
            if ($sessionId) {
                $q->orWhere('session_id', $sessionId);
            }
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
            if (! $sessionId && ! $userId) {
                $q->whereRaw('1 = 0'); // no orders
            }
        });

        $orders = $query->latest()->paginate(15)->withQueryString();

        return view('orders.index', compact('orders'));
    }

    /**
     * Detail satu order (harus milik session/user).
     */
    public function show(Request $request, Order $order)
    {
        if (! $order->isOwnedByCurrentVisitor()) {
            abort(403, __('You do not have access to this order.'));
        }

        $order->load(['registration.event.location', 'registration.rider.user', 'registration.bracket', 'registration.package', 'registration.payment']);

        return view('orders.show', compact('order'));
    }
}
