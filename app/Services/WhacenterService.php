<?php

namespace App\Services;

use App\Jobs\SendWhacenterMessageJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhacenterService
{
    private const OTP_CACHE_PREFIX = 'activation_otp:';

    private const OTP_TTL_MINUTES = 10;

    private const OTP_LENGTH = 6;

    private const NEXT_SEND_CACHE_KEY = 'whacenter:next_send_at';

    private const SCHEDULE_LOCK_KEY = 'whacenter:schedule-lock';

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
        $deviceId = config('services.whacenter.device_id');
        if (! $deviceId) {
            return false;
        }

        $normalized = self::normalizeWhatsApp($number);
        $baseUrl = rtrim(config('services.whacenter.base_url', 'https://app.whacenter.com'), '/');
        $url = $baseUrl.'/api/send';

        $response = Http::asForm()->post($url, [
            'device_id' => $deviceId,
            'number' => $normalized,
            'message' => $message,
        ]);

        return $response->successful();
    }

    /**
     * Antrekan pesan WA ke queue `whatsapp` secara serial.
     * Setiap pesan (termasuk pertama) dijadwalkan dengan jeda acak (default 30–300 dtk)
     * setelah slot sebelumnya / sekarang.
     *
     * @param  int|null  $whatsappNotificationLogId  Optional log row to update when send completes or fails.
     */
    public function queueMessage(
        string $number,
        string $message,
        ?int $whatsappNotificationLogId = null
    ): void {
        $min = max(0, (int) config('services.whacenter.delay_min_seconds', 30));
        $max = max($min, (int) config('services.whacenter.delay_max_seconds', 300));
        $gap = random_int($min, $max);

        $delaySeconds = Cache::lock(self::SCHEDULE_LOCK_KEY, 10)->block(5, function () use ($gap) {
            $now = now()->timestamp;
            $next = (int) Cache::get(self::NEXT_SEND_CACHE_KEY, $now);
            // Jeda acak selalu di depan (termasuk pesan pertama), lalu serial ke pesan berikutnya.
            $sendAt = max($next, $now) + $gap;

            Cache::put(self::NEXT_SEND_CACHE_KEY, $sendAt, now()->addDay());

            return max(0, $sendAt - $now);
        });

        $connection = config('services.whacenter.queue_connection', 'redis');
        $queue = config('services.whacenter.queue', 'whatsapp');

        Log::info('Whacenter queue dispatch', [
            'number' => $number,
            'connection' => $connection,
            'queue' => $queue,
            'delay' => $delaySeconds,
            'gap' => $gap,
        ]);

        $pending = SendWhacenterMessageJob::dispatch(
            $number,
            $message,
            $whatsappNotificationLogId
        )
            ->onConnection($connection)
            ->onQueue($queue);

        if ($delaySeconds > 0) {
            $pending->delay(now()->addSeconds($delaySeconds));
        }
    }

    /**
     * Generate OTP, simpan di cache, kirim ke WA. Return kode OTP (untuk testing/log).
     */
    public function generateAndSendOtp(string $whatsapp): string
    {
        $otp = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        $key = self::OTP_CACHE_PREFIX.self::normalizeWhatsApp($whatsapp);
        Cache::put($key, $otp, now()->addMinutes(self::OTP_TTL_MINUTES));

        $message = __('Your activation code is :code. Valid for :minutes minutes.', [
            'code' => $otp,
            'minutes' => self::OTP_TTL_MINUTES,
        ]);
        $this->queueMessage($whatsapp, $message);

        return $otp;
    }

    public function verifyOtp(string $whatsapp, string $code): bool
    {
        $key = self::OTP_CACHE_PREFIX.self::normalizeWhatsApp($whatsapp);
        $stored = Cache::get($key);
        if ($stored === null || ! hash_equals($stored, $code)) {
            return false;
        }
        Cache::forget($key);

        return true;
    }

    public function clearOtp(string $whatsapp): void
    {
        Cache::forget(self::OTP_CACHE_PREFIX.self::normalizeWhatsApp($whatsapp));
    }
}
