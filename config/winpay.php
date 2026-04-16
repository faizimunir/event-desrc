<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Winpay SNAP API (QRIS MPM dynamic)
    |--------------------------------------------------------------------------
    | Dokumentasi: https://docs.winpay.id/docs/payments/snap-api/qris
    | Base URL: sandbox = https://sandbox-api.bmstaging.id/snap (path wajib /snap).
    | Produksi = https://snap.winpay.id saja — JANGAN tambahkan /snap (akan HTTP 404).
    */
    'enabled' => (bool) env('WINPAY_ENABLED', false),

    'base_url' => rtrim((string) env('WINPAY_BASE_URL', 'https://sandbox-api.bmstaging.id/snap'), '/'),

    /** Merchant client key dari Winpay (header X-PARTNER-ID) */
    'partner_id' => env('WINPAY_PARTNER_ID'),

    /** Header CHANNEL-ID (mis. WEB) */
    'channel_id' => env('WINPAY_CHANNEL_ID', 'WEB'),

    /**
     * RSA private key PEM (merchant) untuk menandatangani request.
     * Prefer path file; alternatif: WINPAY_PRIVATE_KEY (satu baris dengan \n).
     */
    'private_key_path' => env('WINPAY_PRIVATE_KEY_PATH'),

    'private_key' => env('WINPAY_PRIVATE_KEY'),

    /** Terminal ID terdaftar di Winpay (opsional) */
    'terminal_id' => env('WINPAY_TERMINAL_ID'),

];
