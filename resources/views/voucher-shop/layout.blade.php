<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.favicon')
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Beli Voucher WiFi — '.$settings['title'])</title>
    <link rel="stylesheet" href="{{ asset('css/voucher-shop.css') }}?v={{ @filemtime(public_path('css/voucher-shop.css')) }}">
</head>
<body>
@php
    $initials = collect(preg_split('/\s+/', trim($settings['title'])))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'W';
    $waLink = $settings['contact_wa'] !== '' ? 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/\D/', '', $settings['contact_wa'])) : null;
@endphp
<header class="vs-hero">
    <div class="vs-hero-inner">
        <div class="vs-logo" aria-hidden="true">{{ $initials }}</div>
        <div>
            <h1>{{ $settings['title'] }}</h1>
            <p>{{ $settings['tagline'] ?: 'Beli voucher WiFi, bayar QRIS, langsung online.' }}</p>
        </div>
    </div>
</header>

<main class="vs-wrap">
    @yield('content')

    <div class="vs-foot">
        @if ($waLink)
            <p><a class="vs-wa" href="{{ $waLink }}" target="_blank" rel="noopener">
                <svg class="vs-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.3 0 .5l-.4.6-.3.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.6-.1 1.1Z"/></svg>
                Butuh bantuan? Chat pengelola WiFi
            </a></p>
        @endif
        <p>Pembayaran diproses aman oleh <strong>MikPay</strong> &amp; {{ \App\Support\PaymentGateway::platformProviderLabel() }} (QRIS).<br>
            <a href="{{ central_url('/syarat-ketentuan') }}" target="_blank" rel="noopener">Syarat</a> ·
            <a href="{{ central_url('/kebijakan-pengembalian-dana') }}" target="_blank" rel="noopener">Refund</a> ·
            <a href="{{ central_url('/kebijakan-privasi') }}" target="_blank" rel="noopener">Privasi</a>
        </p>
    </div>
</main>
@stack('scripts')
</body>
</html>
