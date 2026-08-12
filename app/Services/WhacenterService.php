<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Jobs\SendWhacenterMessageJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhacenterService
{
    private const OTP_CACHE_PREFIX = 'activation_otp:';

    private const OTP_TTL_MINUTES = 10;

    private const OTP_LENGTH = 6;

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
     * Antrekan pesan WA ke worker (jeda acak 5–30 dtk default), tidak kirim sync.
     */
    /**
     * @param  int|null  $whatsappNotificationLogId  Optional log row to update when send completes or fails.
     */
    public function queueMessage(
        string $number,
        string $message,
        ?int $whatsappNotificationLogId = null
    ): void {
        $min = max(
            30,
            (int) config(
                'services.whacenter.delay_min_seconds',
                30
            )
        );
    
        $max = max(
            $min,
            (int) config(
                'services.whacenter.delay_max_seconds',
                300
            )
        );
    
        $delay = random_int($min, $max);
    
        /*
         * Redis Lua script:
         *
         * Membaca slot terakhir dan menggesernya
         * secara ATOMIC.
         *
         * Ini mencegah dua request bersamaan
         * mendapatkan slot yang sama.
         */
        $redis = app('redis')->connection();
    
        $key = 'whacenter:next_available_at';
    
        $now = now()->timestamp;
    
        $script = <<<'LUA'
    local current = redis.call('GET', KEYS[1])
    local now = tonumber(ARGV[1])
    local delay = tonumber(ARGV[2])
    local ttl = tonumber(ARGV[3])
    
    local run_at = now
    
    if current then
        local current_value = tonumber(current)
    
        if current_value > now then
            run_at = current_value
        end
    end
    
    local next_available = run_at + delay
    
    redis.call(
        'SET',
        KEYS[1],
        tostring(next_available),
        'EX',
        ttl
    )
    
    return run_at
    LUA;
    
        $runAt = (int) $redis->eval(
            $script,
            1,
            $key,
            $now,
            $delay,
            86400
        );
    
        Log::info('Whacenter: job scheduled', [
            'number' => $number,
            'run_at' => $runAt,
            'delay_after' => $delay,
        ]);
    
        SendWhacenterMessageJob::dispatch(
            $number,
            $message,
            $whatsappNotificationLogId
        )->delay(
            \Illuminate\Support\Carbon::createFromTimestamp($runAt)
        );
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
