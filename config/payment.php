<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Manual transfer (rekening pribadi)
    |--------------------------------------------------------------------------
    | Dipakai saat pembayaran manual: user transfer ke rekening ini lalu upload bukti.
    | Nanti diganti / dilengkapi dengan Moota atau gateway lain.
    */
    'manual' => [
        'bank_name' => env('PAYMENT_MANUAL_BANK_NAME', 'Bank Contoh'),
        'account_number' => env('PAYMENT_MANUAL_ACCOUNT_NUMBER', '1234567890'),
        'account_holder' => env('PAYMENT_MANUAL_ACCOUNT_HOLDER', 'Nama Pemilik Rekening'),
    ],

];
