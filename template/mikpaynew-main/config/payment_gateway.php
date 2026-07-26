<?php

return [
    // Payment gateway internal/manual (tanpa pihak ketiga)
    // Bank & e-wallet: tanpa default — isi di .env agar muncul di halaman pembayaran.
    'bank' => [
        'bank_name' => env('PAYMENT_BANK_NAME'),
        'account_number' => env('PAYMENT_BANK_ACCOUNT'),
        'account_name' => env('PAYMENT_BANK_ACCOUNT_NAME'),
    ],
    'ewallet' => [
        'wallet_name' => env('PAYMENT_EWALLET_NAME'),
        'wallet_number' => env('PAYMENT_EWALLET_NUMBER'),
    ],
    'qris' => [
        'merchant_name' => env('PAYMENT_QRIS_MERCHANT_NAME', 'MIKPAY ANNETWIFI'),
        'nmid' => env('PAYMENT_QRIS_NMID', 'ID1025372982195'),
        'terminal_id' => env('PAYMENT_QRIS_TERMINAL_ID', 'A01'),
        // Path relatif dari public/ (dipakai asset())
        'image_path' => env('PAYMENT_QRIS_IMAGE', 'mikpay/img/qris_mikpay.png'),
    ],
];

