<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ticket;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\Response;

class TicketController extends Controller
{
    /**
     * Public: tampilkan e-ticket via ticket_code (link langsung / share).
     */
    public function show(Ticket $ticket)
    {
        $ticket->loadMissing(['registration.event.location', 'registration.rider.user', 'registration.bracket', 'registration.package']);
        $reg = $ticket->registration;
        $event = $reg->event;
        $rider = $reg->rider;

        return view('tickets.show', compact('ticket', 'reg', 'event', 'rider'));
    }

    /**
     * Public: tampilkan e-ticket dari order (peserta yang punya order).
     */
    public function showFromOrder(Order $order)
    {
        if (! $order->isOwnedByCurrentVisitor()) {
            abort(403, __('You do not have access to this order.'));
        }
        $ticket = $order->registration->ticket;
        if (! $ticket) {
            return redirect()->route('orders.show', $order->order_code)
                ->with('info', __('Your ticket is not ready yet. Payment must be verified and registration approved.'));
        }

        return redirect()->route('tickets.show', $ticket->ticket_code);
    }

    /**
     * Public: QR code image (PNG) untuk ticket.
     */
    public function qr(Ticket $ticket): Response
    {
        $qrCode = QrCode::create($ticket->verification_url)
            ->setSize(280)
            ->setMargin(10);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="ticket-'.$ticket->ticket_code.'.png"',
        ]);
    }

    /**
     * Public: halaman verifikasi (saat QR di-scan).
     */
    public function verify(Ticket $ticket)
    {
        $ticket->load(['registration.event', 'registration.rider', 'registration.bracket', 'registration.package']);
        $reg = $ticket->registration;
        $event = $reg->event;
        $rider = $reg->rider;

        return view('tickets.verify', compact('ticket', 'reg', 'event', 'rider'));
    }
}
