<?php

namespace App\Jobs;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRegistrationEmail implements ShouldQueue
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

        // Bank transfer information (configure this in config or env)
        $bankInfo = [
            'bank_name' => config('app.bank_name'),
            'account_number' => config('app.bank_account'),
            'account_name' => config('app.bank_account_name'),
        ];

        $subject = 'Konfirmasi Registrasi - ' . $event->name;
        
        $emailContent = "Halo {$participant->name},\n\n";
        $emailContent .= "Terima kasih telah mendaftar untuk event berikut:\n\n";
        $emailContent .= "Event: {$event->name}\n";
        $emailContent .= "Kategori: {$category->name}\n";
        $emailContent .= "Paket: {$package->name}\n";
        $emailContent .= "Lokasi: {$event->location}\n";
        $emailContent .= "Tanggal: " . \Carbon\Carbon::parse($event->start_date)->format('d M Y') . "\n\n";
        $emailContent .= "Total Pembayaran: Rp " . number_format($package->price, 0, ',', '.') . "\n";
        $emailContent .= "Kode Unik: {$participant->unique_code}\n\n";
        $emailContent .= "Total Transfer: Rp " . number_format($package->price + (int)$participant->unique_code, 0, ',', '.') . "\n\n";
        $emailContent .= "Informasi Transfer Bank:\n";
        $emailContent .= "Bank: {$bankInfo['bank_name']}\n";
        $emailContent .= "No. Rekening: {$bankInfo['account_number']}\n";
        $emailContent .= "Atas Nama: {$bankInfo['account_name']}\n\n";
        $emailContent .= "Silakan lakukan pembayaran sesuai nominal di atas. Setelah pembayaran, upload bukti pembayaran di halaman pembayaran.\n\n";
        $emailContent .= "Terima kasih.";

        // Send email using Laravel Mail
        try {
            Mail::raw($emailContent, function ($mail) use ($participant, $subject) {
                $mail->to($participant->email, $participant->name)
                    ->subject($subject);
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send registration email: ' . $e->getMessage());
        }
    }
}
