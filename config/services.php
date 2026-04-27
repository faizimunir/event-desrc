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
        /** Nama antrian database/redis untuk job kirim WA */
        'queue' => env('WHACENTER_QUEUE', 'default'),
        /** Jeda acak sebelum worker mengirim (detik), rentang inklusif */
        'delay_min_seconds' => (int) env('WHACENTER_DELAY_MIN_SECONDS', 5),
        'delay_max_seconds' => (int) env('WHACENTER_DELAY_MAX_SECONDS', 30),
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
    ],

];
