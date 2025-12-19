<?php

namespace App\Jobs;

use App\Models\Participant;
use App\Services\WhatsAppService;
use App\Mail\ParticipantNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;

class SendConfirmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $participant;

    /**
     * Create a new job instance.
     */
    public function __construct(Participant $participant)
    {
        $this->participant = $participant;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $participant = $this->participant->fresh(['package.event', 'category.event']);

        // Generate QR Code
        $qrCodePath = $this->generateQrCode($participant);

        // Send Email with QR Code
        try {
            Mail::to($participant->email, $participant->name)
                ->send(new ParticipantNotificationMail($participant, 'confirmed', $qrCodePath));
        } catch (\Exception $e) {
            \Log::error('Failed to send confirmation email: ' . $e->getMessage());
        }

        // Send WhatsApp
        try {
            $whatsappService = new WhatsAppService();
            
            // Send WhatsApp with QR Code image if available
            if ($qrCodePath && file_exists(public_path($qrCodePath))) {
                $qrCodeUrl = asset($qrCodePath);
                $message = $whatsappService->sendConfirmMessage($participant);
                // Note: Whacenter API might need different implementation for image
                // For now, send text message with QR code info
            } else {
                $whatsappService->sendConfirmMessage($participant);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send confirmation WhatsApp: ' . $e->getMessage());
        }
    }

    /**
     * Generate QR Code for participant
     */
    protected function generateQrCode(Participant $participant): ?string
    {
        try {
            // QR Code content: Registration number + event name
            $qrContent = json_encode([
                'registration_number' => $participant->registration_number,
                'event_id' => $participant->package->event_id,
                'participant_id' => $participant->id,
            ]);

            // Create QR Code using Endroid
            $qrCode = EndroidQrCode::create($qrContent)
                ->setSize(300)
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High);

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            // Save to storage
            $filename = 'qrcodes/' . $participant->registration_number . '.png';
            Storage::disk('public')->put($filename, $result->getString());

            // Return public path
            return 'storage/' . $filename;
        } catch (\Exception $e) {
            \Log::error('Failed to generate QR Code: ' . $e->getMessage());
            return null;
        }
    }
}

