<?php

namespace App\Services;

use App\Jobs\SendWhacenterMessageJob;
use App\Models\WhatsappNotificationLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class WhacenterService
{
    private const OTP_CACHE_PREFIX = 'activation_otp:';

    private const OTP_ATTEMPTS_CACHE_PREFIX = 'activation_otp_attempts:';

    private const OTP_TTL_MINUTES = 10;

    private const OTP_LENGTH = 6;

    private const NEXT_SEND_CACHE_KEY = 'whacenter:next_send_at';

    private const SCHEDULE_LOCK_KEY = 'whacenter:schedule-lock';

    private const BATCH_COUNT_CACHE_KEY = 'whacenter:batch_count';

    private const DEVICE_STATUS_CACHE_KEY = 'whacenter:device_status';

    /**
     * Normalize nomor WA ke format 62xxx (tanpa + atau 0 di depan).
     */
    public static function normalizeWhatsApp(string $input): string
    {
        $n = preg_replace('/\D/', '', $input);
        if (Str::startsWith($n, '62')) {
            return $n;
        }
        if (Str::startsWith($n, '0')) {
            return '62'.Str::after($n, '0');
        }

        return '62'.$n;
    }

    /**
     * Kirim pesan teks ke nomor WhatsApp via Whacenter API.
     */
    public function sendMessage(string $number, string $message): bool
    {
        return $this->sendMessageDetailed($number, $message)['ok'];
    }

    /**
     * Status koneksi device (GET /api/statusDevice), di-cache singkat agar tidak membebani API
     * ketika banyak job diproses berurutan.
     *
     * `connected`: true (CONNECTED), false (NOT CONNECTED / SUSPEND), null (tidak diketahui,
     * mis. API status sedang error — pemanggil sebaiknya tetap mencoba kirim).
     *
     * @return array{connected: bool|null, status: string|null}
     */
    public function deviceStatus(bool $fresh = false): array
    {
        $deviceId = config('services.whacenter.device_id');
        if (! $deviceId) {
            return ['connected' => false, 'status' => 'NOT CONFIGURED'];
        }

        if ($fresh) {
            Cache::forget(self::DEVICE_STATUS_CACHE_KEY);
        }

        return Cache::remember(
            self::DEVICE_STATUS_CACHE_KEY,
            $this->deviceStatusCacheSeconds(),
            fn () => $this->fetchDeviceStatus((string) $deviceId)
        );
    }

    public function forgetDeviceStatus(): void
    {
        Cache::forget(self::DEVICE_STATUS_CACHE_KEY);
    }

    /**
     * Status pesan keluar (GET /api/messageStatus?id=...).
     *
     * state: confirmed (sent/delivered/read), pending (belum ada status), failed, unavailable (API status error).
     *
     * @return array{state: string, delivery_status: string|null, delivered_at: string|null, read_at: string|null, reason: string|null}
     */
    public function messageStatus(string $providerMessageId): array
    {
        $result = [
            'state' => 'unavailable',
            'delivery_status' => null,
            'delivered_at' => null,
            'read_at' => null,
            'reason' => null,
        ];

        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->get($this->baseUrl().'/api/messageStatus', [
                    'device_id' => config('services.whacenter.device_id'),
                    'id' => $providerMessageId,
                    'limit' => 1,
                ]);
        } catch (\Throwable $e) {
            $result['reason'] = 'HTTP request failed: '.Str::limit($e->getMessage(), 150);

            return $result;
        }

        $json = $response->json();
        if (! $response->successful() || ! is_array($json) || ($json['status'] ?? null) !== true) {
            $result['reason'] = 'messageStatus HTTP '.$response->status();

            return $result;
        }

        $row = collect($json['data'] ?? [])
            ->first(fn ($item) => is_array($item) && (string) ($item['id'] ?? '') === $providerMessageId);

        if (! is_array($row)) {
            $result['state'] = 'pending';

            return $result;
        }

        $status = strtolower((string) ($row['message_status'] ?? ''));
        $result['delivery_status'] = $status !== '' ? $status : null;
        $result['delivered_at'] = $row['delivered_at'] ?? null;
        $result['read_at'] = $row['read_at'] ?? null;

        if (in_array($status, ['sent', 'delivered', 'read'], true)) {
            $result['state'] = 'confirmed';
        } elseif (preg_match('/fail|error|reject|cancel/', $status)) {
            $result['state'] = 'failed';
            $result['reason'] = 'Status pesan: '.$status;
        } else {
            $result['state'] = 'pending';
        }

        return $result;
    }

    /**
     * @return array{connected: bool|null, status: string|null}
     */
    private function fetchDeviceStatus(string $deviceId): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->get($this->baseUrl().'/api/statusDevice', ['device_id' => $deviceId]);
        } catch (\Throwable $e) {
            Log::warning('Whacenter: statusDevice request failed', ['error' => $e->getMessage()]);

            return ['connected' => null, 'status' => null];
        }

        $json = $response->json();
        if (! $response->successful() || ! is_array($json)) {
            Log::warning('Whacenter: statusDevice non-success', ['status' => $response->status()]);

            return ['connected' => null, 'status' => null];
        }

        if (($json['status'] ?? null) !== true) {
            return ['connected' => false, 'status' => 'NOT CONNECTED'];
        }

        $status = strtoupper((string) ($json['data']['status'] ?? ''));

        return $status === ''
            ? ['connected' => null, 'status' => null]
            : ['connected' => $status === 'CONNECTED', 'status' => $status];
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.whacenter.base_url', 'https://app.whacenter.com'), '/');
    }

    private function deviceStatusCacheSeconds(): int
    {
        return max(5, (int) config('services.whacenter.device_status_cache_seconds', 30));
    }

    /**
     * Kirim pesan dan kembalikan hasil terstruktur.
     *
     * Whacenter dapat membalas HTTP 200 dengan body `{"status": false, ...}` (mis. device
     * tidak terhubung), jadi sukses hanya jika JSON `status` bernilai true.
     * "ok" berarti pesan DITERIMA Whacenter; konfirmasi terkirim ada di messageStatus().
     *
     * @return array{ok: bool, message_id: string|null, error: string|null, device_down: bool}
     */
    public function sendMessageDetailed(string $number, string $message): array
    {
        $deviceId = config('services.whacenter.device_id');
        if (! $deviceId) {
            return ['ok' => false, 'message_id' => null, 'error' => 'WHACENTER_DEVICE_ID belum dikonfigurasi', 'device_down' => false];
        }

        $normalized = self::normalizeWhatsApp($number);
        $url = $this->baseUrl().'/api/send';

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($url, [
                    'device_id' => $deviceId,
                    'number' => $normalized,
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::error('Whacenter: HTTP request failed', [
                'number' => $normalized,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message_id' => null, 'error' => 'HTTP request failed: '.Str::limit($e->getMessage(), 200), 'device_down' => false];
        }

        $json = $response->json();
        $apiStatus = is_array($json) ? ($json['status'] ?? null) : null;
        $apiMessage = is_array($json) && isset($json['message']) && is_scalar($json['message'])
            ? (string) $json['message']
            : null;

        if (! $response->successful()) {
            Log::warning('Whacenter: API non-success', [
                'number' => $normalized,
                'url' => $url,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return [
                'ok' => false,
                'message_id' => null,
                'error' => 'HTTP '.$response->status().($apiMessage ? ': '.Str::limit($apiMessage, 200) : ''),
                'device_down' => false,
            ];
        }

        if ($apiStatus !== true && $apiStatus !== 'true' && $apiStatus !== 1) {
            Log::warning('Whacenter: API rejected message', [
                'number' => $normalized,
                'url' => $url,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return [
                'ok' => false,
                'message_id' => null,
                'error' => $apiMessage
                    ? 'Whacenter: '.Str::limit($apiMessage, 200)
                    : 'Whacenter: respons tidak valid',
                'device_down' => $apiMessage !== null && stripos($apiMessage, 'device not connected') !== false,
            ];
        }

        $rawId = is_array($json['data'] ?? null) ? ($json['data']['id'] ?? null) : null;
        $messageId = is_scalar($rawId) && (string) $rawId !== '' ? Str::limit((string) $rawId, 64, '') : null;

        Log::info('Whacenter: API ok', [
            'number' => $normalized,
            'status' => $response->status(),
            'message_id' => $messageId,
            'body' => Str::limit($response->body(), 300),
        ]);

        return ['ok' => true, 'message_id' => $messageId, 'error' => null, 'device_down' => false];
    }

    /**
     * Antrekan pesan WA ke queue `whatsapp` secara serial.
     * Setiap pesan (termasuk pertama) dijadwalkan dengan jeda acak (default 15–45 dtk)
     * setelah slot sebelumnya / sekarang. Pada lonjakan beruntun, setiap `batch_size` pesan
     * ditambah jeda istirahat (default 60–180 dtk) agar pola kirim tetap wajar dan aman dari banned.
     *
     * @param  int|null  $whatsappNotificationLogId  Optional log row to update when send completes or fails.
     * @param  int|null  $ttlMinutes  Batas waktu (setelah jadwal kirim) sebelum job dianggap gagal
     *                                jika device belum terhubung. Default: services.whacenter.job_ttl_minutes.
     * @param  bool  $priority  Jalur cepat (mis. OTP): dikirim segera tanpa antre di jadwal serial,
     *                          dan tidak menggeser jadwal pesan lain. Hanya untuk volume kecil yang sudah di-throttle.
     */
    public function queueMessage(
        string $number,
        string $message,
        ?int $whatsappNotificationLogId = null,
        ?int $ttlMinutes = null,
        bool $priority = false
    ): void {
        $delaySeconds = $priority ? 0 : $this->reserveSerialSlot();

        $connection = config('services.whacenter.queue_connection', 'redis');
        $queue = config('services.whacenter.queue', 'whatsapp');

        Log::info('Whacenter queue dispatch', [
            'number' => $number,
            'connection' => $connection,
            'queue' => $queue,
            'delay' => $delaySeconds,
        ]);

        $ttl = max(1, $ttlMinutes ?? (int) config('services.whacenter.job_ttl_minutes', 180));
        $scheduledAt = now()->addSeconds($delaySeconds);
        $expiresAt = $scheduledAt->copy()->addMinutes($ttl);

        $job = new SendWhacenterMessageJob($number, $message, $whatsappNotificationLogId);
        $job->deadlineTimestamp = $expiresAt->timestamp;

        $pending = dispatch($job)
            ->onConnection($connection)
            ->onQueue($queue);

        if ($delaySeconds > 0) {
            $pending->delay($scheduledAt);
        }

        if ($whatsappNotificationLogId !== null) {
            WhatsappNotificationLog::recordSchedule($whatsappNotificationLogId, $scheduledAt, $expiresAt);
        }
    }

    /**
     * Ambil slot kirim berikutnya pada jadwal serial dan kembalikan jeda (detik) dari sekarang.
     */
    private function reserveSerialSlot(): int
    {
        $min = max(0, (int) config('services.whacenter.delay_min_seconds', 15));
        $max = max($min, (int) config('services.whacenter.delay_max_seconds', 45));
        $batchSize = max(0, (int) config('services.whacenter.batch_size', 20));
        $restMin = max(0, (int) config('services.whacenter.batch_rest_min_seconds', 60));
        $restMax = max($restMin, (int) config('services.whacenter.batch_rest_max_seconds', 180));

        return Cache::lock(self::SCHEDULE_LOCK_KEY, 10)->block(
            5,
            function () use ($min, $max, $batchSize, $restMin, $restMax) {
                $now = now()->timestamp;
                $next = (int) Cache::get(self::NEXT_SEND_CACHE_KEY, $now);

                // Antrean kosong = lonjakan baru, hitungan batch mulai dari nol.
                $count = $next > $now ? (int) Cache::get(self::BATCH_COUNT_CACHE_KEY, 0) : 0;
                $count++;

                // Jeda acak selalu di depan (termasuk pesan pertama), lalu serial ke pesan berikutnya.
                $gap = random_int($min, $max);
                if ($batchSize > 0 && $count >= $batchSize) {
                    $gap += random_int($restMin, $restMax);
                    $count = 0;
                }

                $sendAt = max($next, $now) + $gap;

                Cache::put(self::NEXT_SEND_CACHE_KEY, $sendAt, now()->addDay());
                Cache::put(self::BATCH_COUNT_CACHE_KEY, $count, now()->addDay());

                return max(0, $sendAt - $now);
            }
        );
    }

    /**
     * Minta OTP aktivasi dengan pengaman: cooldown per nomor, batas per jam per nomor dan per IP,
     * serta pengecekan device. OTP baru dibuat & dikirim (jalur cepat) hanya jika semua lolos.
     *
     * reason: null (terkirim), 'throttled' (lihat retry_after), 'device_unavailable'.
     *
     * @return array{sent: bool, reason: string|null, retry_after: int}
     */
    public function requestOtp(string $whatsapp, ?string $ip = null): array
    {
        $number = self::normalizeWhatsApp($whatsapp);

        /** @var array<string, array{0: int, 1: int}> $limits key => [maxAttempts, decaySeconds] */
        $limits = [
            'otp-cooldown:'.$number => [1, max(1, (int) config('services.whacenter.otp_cooldown_seconds', 60))],
            'otp-hourly:'.$number => [max(1, (int) config('services.whacenter.otp_max_per_hour', 5)), 3600],
        ];
        if ($ip !== null && $ip !== '') {
            $limits['otp-ip:'.sha1($ip)] = [max(1, (int) config('services.whacenter.otp_max_per_ip_per_hour', 10)), 3600];
        }

        foreach ($limits as $key => [$max, $decay]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return ['sent' => false, 'reason' => 'throttled', 'retry_after' => RateLimiter::availableIn($key)];
            }
        }

        if ($this->deviceStatus()['connected'] === false) {
            return ['sent' => false, 'reason' => 'device_unavailable', 'retry_after' => 0];
        }

        foreach ($limits as $key => [$max, $decay]) {
            RateLimiter::hit($key, $decay);
        }

        $this->generateAndSendOtp($whatsapp);

        return ['sent' => true, 'reason' => null, 'retry_after' => 0];
    }

    /**
     * Buat OTP, simpan di cache, kirim ke WA lewat jalur cepat (tanpa menunggu antrean serial).
     * Job kedaluwarsa bersamaan dengan masa berlaku OTP agar kode basi tidak terkirim.
     * Gunakan requestOtp() agar throttle ikut berlaku.
     */
    public function generateAndSendOtp(string $whatsapp): void
    {
        $number = self::normalizeWhatsApp($whatsapp);
        $otp = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        Cache::put(self::OTP_CACHE_PREFIX.$number, $otp, now()->addMinutes(self::OTP_TTL_MINUTES));
        Cache::forget(self::OTP_ATTEMPTS_CACHE_PREFIX.$number);

        $message = __('Your activation code is :code. Valid for :minutes minutes.', [
            'code' => $otp,
            'minutes' => self::OTP_TTL_MINUTES,
        ]);

        $this->queueMessage($whatsapp, $message, null, self::OTP_TTL_MINUTES, priority: true);
    }

    /**
     * Verifikasi OTP. Salah berkali-kali (default 5x) membatalkan kode agar tidak bisa ditebak.
     */
    public function verifyOtp(string $whatsapp, string $code): bool
    {
        $number = self::normalizeWhatsApp($whatsapp);
        $key = self::OTP_CACHE_PREFIX.$number;
        $stored = Cache::get($key);

        if ($stored === null) {
            return false;
        }

        if (! hash_equals((string) $stored, $code)) {
            $attemptsKey = self::OTP_ATTEMPTS_CACHE_PREFIX.$number;
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;

            if ($attempts >= max(1, (int) config('services.whacenter.otp_max_verify_attempts', 5))) {
                $this->clearOtp($whatsapp);
            } else {
                Cache::put($attemptsKey, $attempts, now()->addMinutes(self::OTP_TTL_MINUTES));
            }

            return false;
        }

        $this->clearOtp($whatsapp);

        return true;
    }

    public function clearOtp(string $whatsapp): void
    {
        $number = self::normalizeWhatsApp($whatsapp);

        Cache::forget(self::OTP_CACHE_PREFIX.$number);
        Cache::forget(self::OTP_ATTEMPTS_CACHE_PREFIX.$number);
    }
}
