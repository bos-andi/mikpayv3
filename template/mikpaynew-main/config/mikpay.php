<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MIKPAY - MikroTik Management
    |--------------------------------------------------------------------------
    | Default admin credentials can be overridden via env.
    | Router list is stored in database (routers table).
    */

    'admin' => [
        'username' => env('MIKPAY_ADMIN_USERNAME', 'mikpay'),
        'password' => env('MIKPAY_ADMIN_PASSWORD', '1234'), // plain; stored hashed in DB or use env for initial
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin (hanya .env — tidak ada user super admin di database)
    |--------------------------------------------------------------------------
    | Login: SUPERADMIN_USERNAME atau SUPERADMIN_EMAIL (opsional).
    | Password: SUPERADMIN_PASSWORD (plain di .env — cara utama).
    | Opsional: SUPERADMIN_PASSWORD_HASH (bcrypt) hanya jika SUPERADMIN_PASSWORD kosong.
    */
    'superadmin' => [
        'username' => env('SUPERADMIN_USERNAME', 'superadmin'),
        'email' => env('SUPERADMIN_EMAIL'),
        'password' => env('SUPERADMIN_PASSWORD'),
        'password_hash' => env('SUPERADMIN_PASSWORD_HASH'),
    ],

    'router' => [
        'api_port' => env('MIKROTIK_API_PORT', 8728),
        'timeout' => (int) env('MIKROTIK_TIMEOUT', 3),
        /** Batas waktu satu pemanggilan RouterosAPI::read() (detik); cegah hang memenuhi max_execution_time. */
        'read_timeout' => (int) env('MIKROTIK_READ_TIMEOUT', 45),
        'ssl' => env('MIKROTIK_SSL', false),
    ],

    /* URL dasar cetak voucher (template lama). Jika kosong, tombol cetak tidak membuka URL. */
    'voucher_print_base_url' => env('MIKPAY_VOUCHER_PRINT_URL', ''),

    'subscription_packages' => [
        'trial' => [
            'name' => 'Trial',
            'price' => 0,
            'duration' => 7,
            'max_customers' => -1,
            'max_routers' => 1,
            'max_users' => -1,
        ],
        'monthly' => [
            'name' => 'Paket 1 Bulan',
            'price' => 50000,
            'duration' => 30,
            'max_customers' => -1,
            'max_routers' => -1,
            'max_users' => -1,
        ],
        'monthly5' => [
            'name' => 'Paket 5 Bulan',
            'price' => 200000,
            'duration' => 150,
            'max_customers' => -1,
            'max_routers' => -1,
            'max_users' => -1,
        ],
    ],

    /*
    | Nomor WhatsApp (08… / 62…) untuk tombol "hubungi admin" saat user menunggu verifikasi
    | pembayaran langganan. Bisa di-override di Super Admin → Paket langganan.
    */
    'subscription_verification_whatsapp' => env('MIKPAY_SUBSCRIPTION_VERIFY_WHATSAPP'),

];
