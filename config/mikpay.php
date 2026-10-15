<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    */
    'brand' => [
        'name' => env('MIKPAY_BRAND_NAME', 'MikPay'),
        'domain' => env('MIKPAY_BRAND_DOMAIN', 'mikpay.link'),
        'tagline' => env('MIKPAY_BRAND_TAGLINE', 'NMS & Billing System'),
        'initial' => env('MIKPAY_BRAND_INITIAL', 'M'),
        'logo' => env('MIKPAY_BRAND_LOGO', 'logo-mikpay.png'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Badan usaha (halaman legal: Tentang Kami, S&K, Privasi, Refund)
    |--------------------------------------------------------------------------
    */
    'company' => [
        'name' => env('MIKPAY_COMPANY_NAME', 'PT Andidev Kreatif Digital'),
        'nib' => env('MIKPAY_COMPANY_NIB', '2104260217439'),
        'address' => env('MIKPAY_COMPANY_ADDRESS', 'Dsn. Kemambang RT 08 RW 01, Kec. Balongbendo, Kab. Sidoarjo, Jawa Timur, Indonesia'),
        'email' => env('MIKPAY_COMPANY_EMAIL', 'admin@mikpay.link'),
        'phone' => env('MIKPAY_COMPANY_PHONE', '+62 857-0700-4054'),
        'legal_updated_at' => env('MIKPAY_LEGAL_UPDATED_AT', '14 Oktober 2026'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'group_url' => env('MIKPAY_TELEGRAM_GROUP_URL', 'https://t.me/+kmKY1X6VXnI0OTNl'),
    ],

    'whatsapp' => [
        'number' => env('MIKPAY_WA_NUMBER', '6281234567890'),
        'trial_message' => env(
            'MIKPAY_WA_TRIAL_MESSAGE',
            'Halo Admin MikPay, saya ingin coba gratis 3 hari'
        ),
        'consult_message' => env(
            'MIKPAY_WA_CONSULT_MESSAGE',
            'Halo Admin MikPay, saya ingin mencoba gratis'
        ),
        // WaAPI — https://wa-api.id/docs
        'waapi_base_url' => env('WAAPI_BASE_URL', 'https://wa-api.id/api/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial platform (hari) — dihitung pakai tanggal & jam WIB
    |--------------------------------------------------------------------------
    */
    'trial_days' => max(1, (int) env('MIKPAY_TRIAL_DAYS', 3)),

    /*
    |--------------------------------------------------------------------------
    | Email pengingat langganan (mikpay:subscription-emails, tiap jam)
    |--------------------------------------------------------------------------
    */
    'subscription_emails' => [
        // Pengingat H-N (sekali) dan hari H jatuh tempo, dikirim mulai jam send_hour WIB.
        'remind_days_before' => (int) env('MIKPAY_REMIND_DAYS_BEFORE', 3),
        'send_hour' => (int) env('MIKPAY_REMIND_SEND_HOUR', 7),
        'expired_lookback_days' => (int) env('MIKPAY_EXPIRED_EMAIL_LOOKBACK_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Saldo langganan MikPay (deposit via Xendit → bayar/perpanjang dari saldo)
    |--------------------------------------------------------------------------
    */
    'subscription' => [
        'deposit_min' => max(1000, (int) env('MIKPAY_DEPOSIT_MIN', 10000)),
        'deposit_presets' => [35000, 80000, 155000],
        'auto_renew_hours' => max(1, (int) env('MIKPAY_AUTO_RENEW_HOURS', 24)),
    ],

    /*
    | Toko voucher hotspot publik ({tenant}.mikpay.link/voucher) — QRIS via gateway platform.
    | Biaya QRIS gateway (MDR + PPN atas MDR) dan biaya layanan MikPay ditanggung pembeli.
    */
    'voucher_shop' => [
        'qris_mdr_percent' => (float) env('MIKPAY_VOUCHER_QRIS_MDR_PERCENT', 0.7),
        'qris_vat_percent' => (float) env('MIKPAY_VOUCHER_QRIS_VAT_PERCENT', 11),
        'qris_fee_flat' => max(0, (int) env('MIKPAY_VOUCHER_QRIS_FEE_FLAT', 0)),
        'min_price' => max(1000, (int) env('MIKPAY_VOUCHER_MIN_PRICE', 1500)),
        'payment_minutes' => max(5, (int) env('MIKPAY_VOUCHER_PAYMENT_MINUTES', 15)),
        'service_fee' => max(0, (int) env('MIKPAY_VOUCHER_SERVICE_FEE', 0)),
    ],

    /*
    |--------------------------------------------------------------------------
    | Superadmin (email yang diizinkan saja)
    |--------------------------------------------------------------------------
    | Customer daftar → tenant_admin di subdomain-nya.
    | Superadmin panel hanya untuk email di daftar ini.
    */
    'superadmin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('MIKPAY_SUPERADMIN_EMAILS', 'me@andidev.id'))
    ))),

    /*
    | Tenant panel default untuk superadmin di mikpay.link (bukan pelanggan baru).
    | Contoh: annetwifi — akun demo/operasional yang sudah dikonfigurasi.
    */
    'superadmin_panel_tenant' => env('MIKPAY_SUPERADMIN_PANEL_TENANT', 'annetwifi'),

    /*
    |--------------------------------------------------------------------------
    | Zona waktu bisnis (WIB)
    |--------------------------------------------------------------------------
    */
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Demo
    |--------------------------------------------------------------------------
    */
    'demo_url' => env('MIKPAY_DEMO_URL', '/daftar'),

    /*
    |--------------------------------------------------------------------------
    | Multi-tenant domains
    |--------------------------------------------------------------------------
    |
    | Central : mikpay.link              → landing + /dashboard (owner trial)
    | Tenant  : {subdomain}.mikpay.link  → /dashboard (client ISP)
    | Tunnel product : tunnel.mikpay.link
    | VPN nodes      : an-1.mikpay.link, cgk-2.mikpay.link, ...
    |
    */
    'central_domain' => env('MIKPAY_CENTRAL_DOMAIN', 'mikpay.link'),
    'tenant_base_domain' => env('MIKPAY_TENANT_BASE_DOMAIN', 'mikpay.link'),
    'remote_base_domain' => env('MIKPAY_REMOTE_BASE_DOMAIN', 'an-1.mikpay.link'),
    'tunnel_domain' => env('MIKPAY_TUNNEL_DOMAIN', 'tunnel.mikpay.link'),

    /*
    |--------------------------------------------------------------------------
    | OpenVPN Remote Tunnel
    |--------------------------------------------------------------------------
    |
    | Engine bersama untuk:
    | - Bonus gratis (mikpay_bundle) saat langganan MikPay aktif
    | - Produk berbayar di tunnel.mikpay.link (tunnel_paid)
    |
    */
    'tunnel' => [
        // Fallback node host (DB tunnel_nodes is source of truth).
        'default_node_host' => env('MIKPAY_TUNNEL_DEFAULT_NODE', 'an-1.mikpay.link'),
        'server_host' => env('MIKPAY_TUNNEL_SERVER_HOST', env('MIKPAY_TUNNEL_DEFAULT_NODE', 'an-1.mikpay.link')),
        // Connect-to untuk script OVPN (boleh IP mentah jika DNS di router bermasalah)
        'connect_host' => env('MIKPAY_TUNNEL_CONNECT_HOST', env('MIKPAY_TUNNEL_SERVER_HOST', 'tunnel.mikpay.link')),
        'server_port' => (int) env('MIKPAY_TUNNEL_SERVER_PORT', 1194),
        'account_suffix' => env('MIKPAY_TUNNEL_ACCOUNT_SUFFIX', 'tunnel.mikpay.link'),
        'port_range_start' => (int) env('MIKPAY_TUNNEL_PORT_START', 1000),
        'port_range_end' => (int) env('MIKPAY_TUNNEL_PORT_END', 3000),
        'protocol' => 'openvpn',
        // Subdomain prefixes reserved for VPN nodes: an-1, cgk-2, ...
        'node_sub_pattern' => env('MIKPAY_TUNNEL_NODE_SUB_PATTERN', '^(an|cgk)-[0-9]+$'),
        // OpenVPN server (TCP) subnet — terpisah dari instance UDP 10.8.0.0/24
        'vpn_network' => env('MIKPAY_TUNNEL_VPN_NETWORK', '10.9.0'),
        'vpn_cidr' => env('MIKPAY_TUNNEL_VPN_CIDR', '10.9.0.0/24'),
        'vpn_netmask' => env('MIKPAY_TUNNEL_VPN_NETMASK', '255.255.255.0'),
        'vpn_client_start' => (int) env('MIKPAY_TUNNEL_VPN_CLIENT_START', 2),
        'vpn_client_end' => (int) env('MIKPAY_TUNNEL_VPN_CLIENT_END', 254),
        'public_iface' => env('MIKPAY_TUNNEL_PUBLIC_IFACE', 'eth0'),
        // Management interface OpenVPN server (untuk memutus sesi akun yang dihapus/nonaktif).
        'management' => [
            'host' => env('MIKPAY_OVPN_MGMT_HOST', '127.0.0.1'),
            'port' => (int) env('MIKPAY_OVPN_MGMT_PORT', 7505),
            'password' => env('MIKPAY_OVPN_MGMT_PASSWORD', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MikroTik RouterOS API
    |--------------------------------------------------------------------------
    */
    'router' => [
        'api_port' => (int) env('MIKPAY_ROUTER_API_PORT', 8728),
        'ssl' => (bool) env('MIKPAY_ROUTER_SSL', false),
        'timeout' => (int) env('MIKPAY_ROUTER_TIMEOUT', 3),
        'read_timeout' => (int) env('MIKPAY_ROUTER_READ_TIMEOUT', 45),
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing / tunggakan
    |--------------------------------------------------------------------------
    |
    | tracking_start_date: lantai global go-live (Y-m-d). Tagihan per pelanggan
    | tetap mulai dari started_at (bulan daftar). Tanpa started_at = bulan berjalan.
    | grace_days: default hari setelah due_day sebelum auto isolir (bisa di-override
    | per tenant lewat Settings → Billing: meta billing.grace_days).
    |
    */
    'billing' => [
        'tracking_start_date' => env('MIKPAY_BILLING_TRACKING_START'),
        'grace_days' => (int) env('MIKPAY_BILLING_GRACE_DAYS', 2),
        'auto_isolir' => (bool) env('MIKPAY_BILLING_AUTO_ISOLIR', true),
        'auto_open' => (bool) env('MIKPAY_BILLING_AUTO_OPEN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment gateway
    |--------------------------------------------------------------------------
    |
    | Default platform = Andidev Pay bila ANDIDEV_PAY_KEY terisi, selain itu Xendit
    | (kunci di .env, tidak bisa diubah dari panel ISP).
    | Tenant tanpa gateway sendiri otomatis memakai default ini.
    | Tenant boleh override ke gateway sendiri via meta payment.mode=own.
    | Penarikan dana (disbursement) tetap lewat Xendit platform.
    |
    */
    'payment' => [
        'default_gateway' => env('MIKPAY_DEFAULT_PAYMENT_GATEWAY', 'xendit'),
        // false = https://mikpay.link/bayar/{token} (disarankan jika wildcard DNS belum siap)
        'pay_link_use_subdomain' => (bool) env('MIKPAY_PAY_LINK_USE_SUBDOMAIN', false),
        'xendit' => [
            'enabled' => (bool) env('XENDIT_ENABLED', true),
            'secret_key' => env('XENDIT_SECRET_KEY'),
            'public_key' => env('XENDIT_PUBLIC_KEY'),
            'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
        ],
        // https://payment.andidev.id/docs — webhook: {APP_URL}/webhooks/andidev-pay
        'andidev_pay' => [
            'enabled' => (bool) env('ANDIDEV_PAY_ENABLED', true),
            'url' => rtrim((string) env('ANDIDEV_PAY_URL', 'https://payment.andidev.id'), '/'),
            'api_key' => env('ANDIDEV_PAY_KEY'),
            'webhook_secret' => env('ANDIDEV_PAY_WEBHOOK_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO defaults (override per-page via @section)
    |--------------------------------------------------------------------------
    */
    'seo' => [
        'title' => 'MikPay - Software Manajemen ISP, RTRW Net, OLT & Remote VPN Tunnel',
        'description' => 'Aplikasi manajemen terpadu untuk pengusaha RTRW Net & ISP. Auto Isolir WhatsApp, Peta ODP, Cetak Voucher, dan Bonus VPN Remote Tunneling Gratis.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batas paket MikPay (harga & durasi di SubscriptionPackageService / Superadmin)
    |--------------------------------------------------------------------------
    */
    // Semua paket: 1 router MikroTik per tenant (berlaku juga saat superadmin mengelola panel tenant).
    'max_routers_per_tenant' => 1,

    'subscription_verification_whatsapp' => env('MIKPAY_SUBSCRIPTION_VERIFY_WHATSAPP'),

];

