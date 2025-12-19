<?php

namespace App\Jobs;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendRegistrationWhatsApp implements ShouldQueue
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
        $participant = $this->participant->load(['package.event', 'category.event']);
        $package = $participant->package;
        $category = $participant->category;
        $event = $package->event ?? $category->event;

        // Bank transfer information
        $bankInfo = [
            'bank_name' => config('app.bank_name', 'Bank BCA'),
            'account_number' => config('app.bank_account', '1234567890'),
            'account_name' => config('app.bank_account_name', 'Event Registration'),
        ];

        $message = "Halo {$participant->name},\n\n";
        $message .= "Terima kasih telah mendaftar untuk event berikut:\n\n";
        $message .= "Event: {$event->name}\n";
        $message .= "Kategori: {$category->name}\n";
        $message .= "Paket: {$package->name}\n";
        $message .= "Lokasi: {$event->location}\n";
        $message .= "Tanggal: " . \Carbon\Carbon::parse($event->start_date)->format('d M Y') . "\n\n";
        $message .= "Total Pembayaran: Rp " . number_format($package->price, 0, ',', '.') . "\n";
        $message .= "Kode Unik: {$participant->unique_code}\n\n";
        $message .= "Total Transfer: Rp " . number_format($package->price + (int)$participant->unique_code, 0, ',', '.') . "\n\n";
        $message .= "Informasi Transfer Bank:\n";
        $message .= "Bank: {$bankInfo['bank_name']}\n";
        $message .= "No. Rekening: {$bankInfo['account_number']}\n";
        $message .= "Atas Nama: {$bankInfo['account_name']}\n\n";
        $message .= "Silakan lakukan pembayaran sesuai nominal di atas. Setelah pembayaran, upload bukti pembayaran di halaman pembayaran.\n\n";
        $message .= "Terima kasih.";

        // Send WhatsApp via Whacenter API
        try {
            $whacenterApiKey = config('services.whacenter.api_key', '');
            $whacenterDeviceId = config('services.whacenter.device_id', '');
            
            if ($whacenterApiKey && $whacenterDeviceId) {
                $response = Http::post('https://app.whacenter.com/api/send', [
                    'device_id' => $whacenterDeviceId,
                    'number' => $participant->phone,
                    'message' => $message,
                ]);

                if (!$response->successful()) {
                    \Log::error('Failed to send WhatsApp: ' . $response->body());
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send WhatsApp: ' . $e->getMessage());
        }
    }
}
