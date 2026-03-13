<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket
    ) {
    }

    public function envelope(): Envelope
    {
        $registration = $this->ticket->registration()->with('event', 'rider.user')->first();
        $eventTitle = $registration?->event?->title ?? config('app.name');

        return new Envelope(
            subject: __('Your e-ticket is ready').' — '.$eventTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-issued',
            with: [
                'ticket' => $this->ticket,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

