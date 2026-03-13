<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $paymentLinkUrl,
        public string $eventTitle,
        public string $recipientName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Payment link') . ' — ' . $this->eventTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-link',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
