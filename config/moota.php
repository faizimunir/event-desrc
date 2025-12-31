<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Moota API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi dengan Moota API
    | Dapatkan API Key dari https://app.moota.co/settings/api
    |
    */

    'api_key' => env('MOOTA_API_KEY', ''),

    'api_url' => env('MOOTA_API_URL', 'https://app.moota.co/api/v2'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | URL webhook yang akan digunakan oleh Moota untuk mengirim notifikasi
    | transaksi masuk. Pastikan URL ini dapat diakses dari internet.
    |
    */

    'webhook_url' => env('MOOTA_WEBHOOK_URL', '/webhook/moota'),

    /*
    |--------------------------------------------------------------------------
    | Transaction Matching
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk mencocokkan transaksi dengan pembayaran
    |
    */

    'amount_tolerance' => env('MOOTA_AMOUNT_TOLERANCE', 0), // Toleransi perbedaan nominal (dalam rupiah)

    'match_by_unique_code' => env('MOOTA_MATCH_BY_UNIQUE_CODE', true), // Cocokkan berdasarkan kode unik

    'match_by_registration_number' => env('MOOTA_MATCH_BY_REGISTRATION_NUMBER', true), // Cocokkan berdasarkan nomor registrasi

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Konfigurasi logging untuk transaksi Moota
    |
    */

    'log_unmatched_transactions' => env('MOOTA_LOG_UNMATCHED', true), // Log transaksi yang tidak cocok

    'log_channel' => env('MOOTA_LOG_CHANNEL', 'daily'), // Channel log untuk transaksi Moota
];

