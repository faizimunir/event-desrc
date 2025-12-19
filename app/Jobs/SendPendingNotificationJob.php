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

class SendPendingNotificationJob implements ShouldQueue
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

        // Send Email
        try {
            Mail::to($participant->email, $participant->name)
                ->send(new ParticipantNotificationMail($participant, 'pending'));
        } catch (\Exception $e) {
            \Log::error('Failed to send pending email: ' . $e->getMessage());
        }

        // Send WhatsApp
        try {
            $whatsappService = new WhatsAppService();
            $whatsappService->sendPendingMessage($participant);
        } catch (\Exception $e) {
            \Log::error('Failed to send pending WhatsApp: ' . $e->getMessage());
        }
    }
}

