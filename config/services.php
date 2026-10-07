<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whacenter' => [
        'device_id' => env('WHACENTER_DEVICE_ID'),
        'base_url' => env('WHACENTER_BASE_URL', 'https://app.whacenter.com'),
        /** Paksa ke Redis biar worker `queue:work redis --queue=whatsapp` yang memproses */
        'queue_connection' => env('WHACENTER_QUEUE_CONNECTION', 'redis'),
        /** Nama antrian untuk job kirim WA */
        'queue' => env('WHACENTER_QUEUE', 'whatsapp'),
        /** Jeda acak serial antar pesan (detik), rentang inklusif */
        'delay_min_seconds' => (int) env('WHACENTER_DELAY_MIN_SECONDS', 15),
        'delay_max_seconds' => (int) env('WHACENTER_DELAY_MAX_SECONDS', 45),
        /** Pada lonjakan beruntun: setiap N pesan ditambah jeda istirahat (0 = nonaktif) */
        'batch_size' => (int) env('WHACENTER_BATCH_SIZE', 20),
        'batch_rest_min_seconds' => (int) env('WHACENTER_BATCH_REST_MIN_SECONDS', 60),
        'batch_rest_max_seconds' => (int) env('WHACENTER_BATCH_REST_MAX_SECONDS', 180),
        /** OTP aktivasi: cooldown kirim ulang (detik), batas per jam per nomor / per IP, batas salah input */
        'otp_cooldown_seconds' => (int) env('WHACENTER_OTP_COOLDOWN_SECONDS', 60),
        'otp_max_per_hour' => (int) env('WHACENTER_OTP_MAX_PER_HOUR', 5),
        'otp_max_per_ip_per_hour' => (int) env('WHACENTER_OTP_MAX_PER_IP_PER_HOUR', 10),
        'otp_max_verify_attempts' => (int) env('WHACENTER_OTP_MAX_VERIFY_ATTEMPTS', 5),
        /** Watchdog: toleransi setelah expires_at, dan batas umur log tanpa expires_at (menit) */
        'watchdog_grace_minutes' => (int) env('WHACENTER_WATCHDOG_GRACE_MINUTES', 5),
        'stale_after_minutes' => (int) env('WHACENTER_STALE_AFTER_MINUTES', 360),
        /** Verifikasi status pesan via /api/messageStatus sebelum log ditandai "sent" */
        'verify_delivery' => (bool) env('WHACENTER_VERIFY_DELIVERY', true),
        /** Lama cache status device (detik) agar /api/statusDevice tidak dipanggil tiap pesan */
        'device_status_cache_seconds' => (int) env('WHACENTER_DEVICE_STATUS_CACHE_SECONDS', 30),
        /** Batas tunggu (menit, setelah jadwal kirim) sebelum pesan gagal bila device tidak terhubung */
        'job_ttl_minutes' => (int) env('WHACENTER_JOB_TTL_MINUTES', 180),
    ],

    'google_sheets' => [
        'api_key' => env('GOOGLE_SHEETS_API_KEY'),
    ],

    /*
     * Moota (Herd/deltae: secret + qris image; tampilan rekening tetap lewat key tambahan)
     * Endpoint: {APP_URL}/api/webhooks/moota
     */
    'moota' => [
        'webhook_secret' => env('MOOTA_WEBHOOK_SECRET'),
        'qris_image_url' => env('MOOTA_QRIS_IMAGE_URL', env('MOOTA_STATIC_QRIS_IMAGE_URL', '')),
        'bank_name' => env('MOOTA_BANK_NAME', env('PAYMENT_MANUAL_BANK_NAME', 'Bank')),
        'account_number' => env('MOOTA_ACCOUNT_NUMBER', env('PAYMENT_MANUAL_ACCOUNT_NUMBER', '')),
        'account_holder' => env('MOOTA_ACCOUNT_HOLDER', env('PAYMENT_MANUAL_ACCOUNT_HOLDER', '')),
        'queue_connection' => env('MOOTA_QUEUE_CONNECTION', 'redis'),
        'queue' => env('MOOTA_QUEUE', 'moota'),
    ],

];
