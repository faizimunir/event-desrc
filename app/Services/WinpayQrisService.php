<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class WinpayQrisService
{
    public const QR_MPM_GENERATE_PATH = '/v1.0/qr/qr-mpm-generate';

    public function isConfigured(): bool
    {
        if (! config('winpay.enabled')) {
            return false;
        }

        $partnerId = (string) config('winpay.partner_id', '');
        if ($partnerId === '') {
            return false;
        }

        return $this->loadPrivateKeyPem() !== null;
    }

    /**
     * Generate QRIS dinamis (MPM) dan simpan ke payment. Amount mengikuti moota_transfer_amount.
     *
     * @throws RuntimeException jika response tidak sukses
     */
    public function generateDynamicQris(Order $order, Payment $payment): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        if ($payment->method !== 'moota' || $payment->moota_transfer_amount === null) {
            return;
        }

        $baseUrl = $this->normalizeWinpayBaseUrl((string) config('winpay.base_url'));
        $url = $baseUrl.self::QR_MPM_GENERATE_PATH;

        $partnerRef = $this->makePartnerReferenceNo($order, $payment);
        $externalId = $this->makeExternalId();

        $validity = $this->resolveValidityPeriod($order);
        $amountStr = number_format((float) $payment->moota_transfer_amount, 2, '.', '');

        $body = [
            'partnerReferenceNo' => $partnerRef,
            'amount' => [
                'value' => $amountStr,
                'currency' => 'IDR',
            ],
            'validityPeriod' => $validity->toIso8601String(),
            'additionalInfo' => [
                'isStatic' => false,
            ],
        ];

        $terminalId = config('winpay.terminal_id');
        if (is_string($terminalId) && $terminalId !== '') {
            $body['terminalId'] = $terminalId;
        }

        $payload = json_encode($body, JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new RuntimeException('Failed to encode Winpay QRIS payload.');
        }

        $timestamp = Carbon::now()->timezone(config('app.timezone', 'Asia/Jakarta'))->toIso8601String();

        $pem = $this->loadPrivateKeyPem();
        if ($pem === null) {
            throw new RuntimeException('Winpay private key is not configured.');
        }

        $priv = openssl_pkey_get_private($pem);
        if ($priv === false) {
            throw new RuntimeException('Invalid Winpay private key PEM.');
        }

        $stringToSign = WinpaySnapSignature::buildStringToSign('POST', self::QR_MPM_GENERATE_PATH, $payload, $timestamp);
        $signature = WinpaySnapSignature::signString($stringToSign, $priv);

        $response = Http::timeout(45)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-TIMESTAMP' => $timestamp,
                'X-Signature' => $signature,
                'X-PARTNER-ID' => (string) config('winpay.partner_id'),
                'X-EXTERNAL-ID' => $externalId,
                'CHANNEL-ID' => (string) config('winpay.channel_id', 'WEB'),
            ])
            ->withBody($payload, 'application/json')
            ->post($url);

        if (! $response->successful()) {
            Log::warning('Winpay QRIS: HTTP error', [
                'status' => $response->status(),
                'url' => $url,
                'body' => Str::limit($response->body(), 2000),
            ]);

            throw new RuntimeException('Winpay QRIS: HTTP '.$response->status());
        }

        $json = $response->json();
        if (! is_array($json)) {
            Log::warning('Winpay QRIS: invalid JSON response', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 2000),
            ]);

            throw new RuntimeException('Winpay QRIS: invalid response.');
        }

        $responseCode = (string) ($json['responseCode'] ?? '');
        if ($responseCode !== '2004700') {
            Log::warning('Winpay QRIS: non-success', [
                'responseCode' => $responseCode,
                'responseMessage' => $json['responseMessage'] ?? null,
                'response' => $json,
            ]);

            $msg = trim((string) ($json['responseMessage'] ?? ''));
            if ($msg === '') {
                $msg = $responseCode !== '' ? $responseCode : 'error';
            }
            throw new RuntimeException('Winpay QRIS ['.$responseCode.']: '.$msg);
        }

        $qrUrl = isset($json['qrUrl']) ? (string) $json['qrUrl'] : '';
        $qrContent = isset($json['qrContent']) && is_string($json['qrContent']) ? $json['qrContent'] : null;

        $contractId = null;
        $expiredAt = null;
        if (isset($json['additionalInfo']) && is_array($json['additionalInfo'])) {
            $contractId = isset($json['additionalInfo']['contractId'])
                ? (string) $json['additionalInfo']['contractId']
                : null;
            if (! empty($json['additionalInfo']['expiredAt'])) {
                try {
                    $expiredAt = Carbon::parse((string) $json['additionalInfo']['expiredAt']);
                } catch (\Throwable) {
                    $expiredAt = null;
                }
            }
        }

        if ($contractId === null && isset($json['contractId'])) {
            $contractId = (string) $json['contractId'];
        }

        if ($qrUrl === '' && ! $qrContent) {
            throw new RuntimeException('Winpay QRIS: missing qrUrl and qrContent.');
        }

        $payment->forceFill([
            'winpay_qr_url' => $qrUrl !== '' ? $qrUrl : null,
            'winpay_qr_content' => $qrContent,
            'winpay_contract_id' => $contractId,
            'winpay_partner_reference_no' => $partnerRef,
            'winpay_expired_at' => $expiredAt,
            'winpay_external_id' => $externalId,
            'winpay_raw' => $json,
        ])->save();
    }

    private function makePartnerReferenceNo(Order $order, Payment $payment): string
    {
        // Harus unik per request (Winpay 4094701 duplicate partnerReferenceNo).
        $suffix = substr(str_replace('-', '', (string) Str::uuid()), 0, 8);
        $base = $order->order_code.'-P'.$payment->id.'-'.$suffix;
        $base = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $base) ?? $base;
        $base = trim((string) $base, '-');
        if (strlen($base) < 5) {
            $base = 'WPAY-'.$payment->id.'-'.$suffix;
        }

        return Str::limit($base, 50, '');
    }

    /** Unik per request (hindari 409 X-EXTERNAL-ID same day). */
    private function makeExternalId(): string
    {
        return substr(str_replace('-', '', (string) Str::uuid()), 0, 32);
    }

    private function resolveValidityPeriod(Order $order): Carbon
    {
        $until = $order->expired_at
            ? Carbon::parse($order->expired_at)
            : now()->addDay();

        $max = now()->addMonths(3)->subMinute();
        $min = now()->addMinutes(2);

        if ($until->gt($max)) {
            $until = $max;
        }
        if ($until->lte($min)) {
            $until = $min;
        }

        return $until;
    }

    private function loadPrivateKeyPem(): ?string
    {
        $path = $this->resolvePrivateKeyPath((string) config('winpay.private_key_path', ''));
        if ($path !== null && is_readable($path)) {
            $contents = file_get_contents($path);

            return $contents !== false ? $contents : null;
        }

        $inline = config('winpay.private_key');
        if (is_string($inline) && $inline !== '') {
            return str_replace('\\n', "\n", $inline);
        }

        return null;
    }

    private function resolvePrivateKeyPath(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        $resolved = base_path($path);

        return is_readable($resolved) ? $resolved : null;
    }

    /**
     * Pastikan URL absolut. Produksi snap.winpay.id memakai /v1.0/... di akar host;
     * .../snap/v1.0/... mengembalikan HTTP 404.
     */
    private function normalizeWinpayBaseUrl(string $baseUrl): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('Winpay: WINPAY_BASE_URL is empty. Set it in .env (e.g. https://snap.winpay.id) and run php artisan config:clear.');
        }
        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            throw new RuntimeException('Winpay: WINPAY_BASE_URL must be a full URL starting with https:// (current value is not absolute).');
        }
        if (str_contains($baseUrl, 'snap.winpay.id') && str_ends_with($baseUrl, '/snap')) {
            return (string) preg_replace('#/snap$#', '', $baseUrl);
        }

        return $baseUrl;
    }
}
