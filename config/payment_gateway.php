<?php

return [
    // Manual gateway for platform SaaS subscription (bank / e-wallet / QRIS)
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
        'merchant_name' => env('PAYMENT_QRIS_MERCHANT_NAME', 'MIKPAY'),
        'nmid' => env('PAYMENT_QRIS_NMID', ''),
        'terminal_id' => env('PAYMENT_QRIS_TERMINAL_ID', ''),
        'image_path' => env('PAYMENT_QRIS_IMAGE', 'mikpay/img/qris_mikpay.png'),
    ],
];
