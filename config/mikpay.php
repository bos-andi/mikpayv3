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
    | Contact
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'number' => env('MIKPAY_WA_NUMBER', '6281234567890'),
        'trial_message' => env(
            'MIKPAY_WA_TRIAL_MESSAGE',
            'Halo Admin MikPay, saya ingin coba gratis 14 hari'
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
    | Default platform = Xendit (kunci di .env, tidak bisa diubah dari panel ISP).
    | Tenant tanpa gateway sendiri otomatis memakai default ini.
    | Tenant boleh override ke gateway sendiri via meta payment.mode=own.
    |
    */
    'payment' => [
        'default_gateway' => env('MIKPAY_DEFAULT_PAYMENT_GATEWAY', 'xendit'),
        // false = https://mikpay.link/bayar/{token} (disarankan jika wildcard DNS belum siap)
        'pay_link_use_subdomain' => (bool) env('MIKPAY_PAY_LINK_USE_SUBDOMAIN', false),
        'xendit' => [
            'enabled' => (bool) env('XENDIT_ENABLED', true),
            'secret_key' => env('XENDIT_SECRET_KEY'),
            'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
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

];

