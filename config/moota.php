<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webhook HMAC (hash_hmac sha256)
    |--------------------------------------------------------------------------
    | Must match the secret configured in Moota webhook (signUsingSecret).
    | Signature header = hash_hmac('sha256', raw_json_body, secret)
    */
    'webhook_secret' => env('MOOTA_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Optional header checks (set in Moota dashboard if used)
    |--------------------------------------------------------------------------
    */
    'webhook_user' => env('MOOTA_WEBHOOK_USER'),
    'webhook_token' => env('MOOTA_WEBHOOK_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Rekening yang dipantau Moota (untuk tampilan instruksi transfer)
    |--------------------------------------------------------------------------
    | Default mengikuti payment.manual jika tidak di-set.
    */
    'bank_name' => env('MOOTA_BANK_NAME', env('PAYMENT_MANUAL_BANK_NAME', 'Bank')),
    'account_number' => env('MOOTA_ACCOUNT_NUMBER', env('PAYMENT_MANUAL_ACCOUNT_NUMBER', '')),
    'account_holder' => env('MOOTA_ACCOUNT_HOLDER', env('PAYMENT_MANUAL_ACCOUNT_HOLDER', '')),

    /*
    |--------------------------------------------------------------------------
    | Optional: batasi webhook ke rekening tujuan tertentu (nomor rekening)
    |--------------------------------------------------------------------------
    | Jika diisi, mutasi dengan account_number berbeda diabaikan.
    | Kosongkan untuk menerima semua mutasi yang cocok nominal.
    */
    'expected_account_number' => env('MOOTA_EXPECTED_ACCOUNT_NUMBER'),

    /*
    |--------------------------------------------------------------------------
    | Mode webhook
    |--------------------------------------------------------------------------
    | settle       — Moota yang menyelesaikan pembayaran (payment method moota,
    |                cocokkan order_code / nominal unik, lalu tandai order paid).
    | record_only  — Hanya simpan mutasi ke tabel moota_settlement_records (audit /
    |                rekonsiliasi), tanpa mengubah status pembayaran/order.
    */
    'webhook_mode' => env('MOOTA_WEBHOOK_MODE', 'settle'),

    /*
    |--------------------------------------------------------------------------
    | Proses webhook: sinkron (seperti deltae) vs queue
    |--------------------------------------------------------------------------
    | true  = proses MootaWebhookProcessor langsung di request (tidak butuh queue worker).
    | false = simpan event lalu disp ProcessMootaWebhookEvent (favor throughput besar).
    */
    'webhook_sync' => (bool) env('MOOTA_WEBHOOK_SYNC', true),

    /*
    |--------------------------------------------------------------------------
    | QRIS statis (opsional)
    |--------------------------------------------------------------------------
    | URL gambar QRIS yang ditampilkan untuk pembayaran metode qris/moota.
    | MOOTA_QRIS_IMAGE_URL alias deltae; jika kosong, pakai MOOTA_STATIC_QRIS_IMAGE_URL.
    */
    'static_qris_image_url' => env('MOOTA_QRIS_IMAGE_URL', env('MOOTA_STATIC_QRIS_IMAGE_URL', '')),

];
