<?php

namespace App\Mail;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParticipantNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $participant;
    public $type;
    public $qrCodePath;

    /**
     * Create a new message instance.
     */
    public function __construct(Participant $participant, string $type = 'pending', ?string $qrCodePath = null)
    {
        $this->participant = $participant;
        $this->type = $type;
        $this->qrCodePath = $qrCodePath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $participant = $this->participant->load(['package.event', 'category.event']);
        $event = $participant->package->event ?? $participant->category->event;

        $subject = match($this->type) {
            'pending' => 'Konfirmasi Registrasi - ' . $event->name,
            'confirmed' => 'Pembayaran Dikonfirmasi - ' . $event->name,
            'rejected' => 'Pembayaran Ditolak - ' . $event->name,
            default => 'Notifikasi Event - ' . $event->name,
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.participant-notification',
            with: [
                'participant' => $this->participant,
                'type' => $this->type,
                'qrCodePath' => $this->qrCodePath,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->qrCodePath && file_exists(public_path($this->qrCodePath))) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromPath(public_path($this->qrCodePath))
                ->as('qrcode.png')
                ->withMime('image/png');
        }

        return $attachments;
    }
}

