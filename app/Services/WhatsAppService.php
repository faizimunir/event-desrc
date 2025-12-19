<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiKey;
    protected $deviceId;
    protected $baseUrl = 'https://app.whacenter.com/api';

    public function __construct()
    {
        $this->apiKey = config('services.whacenter.api_key');
        $this->deviceId = config('services.whacenter.device_id');
    }

    /**
     * Send pending payment message
     */
    public function sendPendingMessage(Participant $participant): bool
    {
        $participant->load(['package.event', 'category.event']);
        $event = $participant->package->event ?? $participant->category->event;
        
        // Get template from database or use default
        $template = NotificationTemplate::where('event_id', $event->id)
            ->where('type', 'pending')
            ->where('channel', 'whatsapp')
            ->orWhere(function ($query) {
                $query->whereNull('event_id')
                      ->where('type', 'pending')
                      ->where('channel', 'whatsapp')
                      ->where('is_default', true);
            })
            ->where('status', 'active')
            ->first();

        $message = $this->buildMessage($participant, $template, 'pending');

        return $this->send($participant->phone, $message);
    }

    /**
     * Send confirmation message
     */
    public function sendConfirmMessage(Participant $participant): bool
    {
        $participant->load(['package.event', 'category.event']);
        $event = $participant->package->event ?? $participant->category->event;
        
        // Get template from database or use default
        $template = NotificationTemplate::where('event_id', $event->id)
            ->where('type', 'confirmed')
            ->where('channel', 'whatsapp')
            ->orWhere(function ($query) {
                $query->whereNull('event_id')
                      ->where('type', 'confirmed')
                      ->where('channel', 'whatsapp')
                      ->where('is_default', true);
            })
            ->where('status', 'active')
            ->first();

        $message = $this->buildMessage($participant, $template, 'confirmed');

        return $this->send($participant->phone, $message);
    }

    /**
     * Send cancellation message
     */
    public function sendCancelMessage(Participant $participant): bool
    {
        $participant->load(['package.event', 'category.event']);
        $event = $participant->package->event ?? $participant->category->event;
        
        // Get template from database or use default
        $template = NotificationTemplate::where('event_id', $event->id)
            ->where('type', 'rejected')
            ->where('channel', 'whatsapp')
            ->orWhere(function ($query) {
                $query->whereNull('event_id')
                      ->where('type', 'rejected')
                      ->where('channel', 'whatsapp')
                      ->where('is_default', true);
            })
            ->where('status', 'active')
            ->first();

        $message = $this->buildMessage($participant, $template, 'rejected');

        return $this->send($participant->phone, $message);
    }

    /**
     * Build message from template with placeholders
     */
    protected function buildMessage(Participant $participant, $template, $type): string
    {
        $package = $participant->package;
        $category = $participant->category;
        $event = $package->event ?? $category->event;
        $payment = $participant->payment;

        $content = $template ? $template->content : $this->getDefaultTemplate($type);

        // Replace placeholders
        $replacements = [
            '{name}' => $participant->name,
            '{nickname}' => $participant->nickname ?? '',
            '{event_name}' => $event->name,
            '{category_name}' => $category->name,
            '{package_name}' => $package->name,
            '{amount}' => number_format($package->price, 0, ',', '.'),
            '{unique_code}' => $participant->unique_code,
            '{registration_number}' => $participant->registration_number,
            '{total_transfer}' => number_format($package->price + (int)$participant->unique_code, 0, ',', '.'),
            '{location}' => $event->location,
            '{date}' => \Carbon\Carbon::parse($event->start_date)->format('d M Y'),
            '{bank_name}' => config('app.bank_name', 'Bank BCA'),
            '{bank_account}' => config('app.bank_account', '1234567890'),
            '{bank_account_name}' => config('app.bank_account_name', 'Event Registration'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Get default template if no template found
     */
    protected function getDefaultTemplate(string $type): string
    {
        $participant = $this->participant ?? null;
        
        switch ($type) {
            case 'pending':
                return "Halo {name},\n\nTerima kasih telah mendaftar untuk event berikut:\n\nEvent: {event_name}\nKategori: {category_name}\nPaket: {package_name}\nLokasi: {location}\nTanggal: {date}\n\nTotal Pembayaran: Rp {amount}\nKode Unik: {unique_code}\n\nTotal Transfer: Rp {total_transfer}\n\nInformasi Transfer Bank:\nBank: {bank_name}\nNo. Rekening: {bank_account}\nAtas Nama: {bank_account_name}\n\nSilakan lakukan pembayaran sesuai nominal di atas. Setelah pembayaran, upload bukti pembayaran di halaman pembayaran.\n\nTerima kasih.";
            
            case 'confirmed':
                return "Halo {name},\n\nPembayaran Anda telah dikonfirmasi!\n\nEvent: {event_name}\nKategori: {category_name}\nPaket: {package_name}\nNo. Registrasi: {registration_number}\n\nAnda telah terdaftar resmi untuk event ini. Kami akan mengirimkan informasi lebih lanjut melalui email.\n\nTerima kasih.";
            
            case 'rejected':
                return "Halo {name},\n\nMaaf, pembayaran Anda untuk event {event_name} tidak dapat kami terima.\n\nSilakan hubungi admin untuk informasi lebih lanjut.\n\nTerima kasih.";
            
            default:
                return '';
        }
    }

    /**
     * Send WhatsApp message via Whacenter API
     */
    protected function send(string $phoneNumber, string $message): bool
    {
        if (!$this->apiKey || !$this->deviceId) {
            Log::warning('WhatsApp API key or device ID is not configured.');
            return false;
        }

        try {
            // Format phone number (remove +, spaces, etc)
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '62' . substr($phoneNumber, 1);
            } elseif (substr($phoneNumber, 0, 2) !== '62') {
                $phoneNumber = '62' . $phoneNumber;
            }

            $response = Http::timeout(30)->post($this->baseUrl . '/send', [
                'api_key' => $this->apiKey,
                'device_id' => $this->deviceId,
                'number' => $phoneNumber,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['status']) && $result['status'] === true) {
                    return true;
                }
                Log::error('WhatsApp API error: ' . json_encode($result));
                return false;
            }

            Log::error('WhatsApp API failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send WhatsApp message with image
     */
    public function sendWithImage(string $phoneNumber, string $message, string $imagePath): bool
    {
        if (!$this->apiKey || !$this->deviceId) {
            Log::warning('WhatsApp API key or device ID is not configured.');
            return false;
        }

        try {
            // Format phone number
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '62' . substr($phoneNumber, 1);
            } elseif (substr($phoneNumber, 0, 2) !== '62') {
                $phoneNumber = '62' . $phoneNumber;
            }

            $response = Http::timeout(30)->post($this->baseUrl . '/send', [
                'api_key' => $this->apiKey,
                'device_id' => $this->deviceId,
                'number' => $phoneNumber,
                'message' => $message,
                'file' => $imagePath, // Full URL to image
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['status']) && $result['status'] === true) {
                    return true;
                }
                Log::error('WhatsApp API error: ' . json_encode($result));
                return false;
            }

            Log::error('WhatsApp API failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message with image: ' . $e->getMessage());
            return false;
        }
    }
}

