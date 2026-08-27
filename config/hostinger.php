<?php

return [

    'api_token' => env('HOSTINGER_API_TOKEN'),

    'api_base' => env('HOSTINGER_API_BASE', 'https://developers.hostinger.com'),

    /*
    |--------------------------------------------------------------------------
    | DNS zone
    |--------------------------------------------------------------------------
    |
    | Saat tenant daftar, sistem membuat record A untuk subdomain panel
    | (contoh: sarpannet.mikpay.link → IP server).
    |
    */
    'dns_domain' => env('HOSTINGER_DNS_DOMAIN', env('MIKPAY_TENANT_BASE_DOMAIN', 'mikpay.link')),

    'dns_ttl' => (int) env('HOSTINGER_DNS_TTL', 300),

    // Target A record. Kosong = pakai IP publik server / HOSTINGER_DNS_A_IP.
    'dns_a_ip' => env('HOSTINGER_DNS_A_IP', env('SERVER_PUBLIC_IP')),

    // true = buat record A eksplisit per subdomain meski wildcard (*) sudah ada.
    'create_tenant_a_record' => (bool) env('HOSTINGER_CREATE_TENANT_A_RECORD', true),

    // jeda propagasi DNS untuk certbot DNS-01 (detik)
    'acme_wait_seconds' => (int) env('HOSTINGER_ACME_WAIT_SECONDS', 35),

];
