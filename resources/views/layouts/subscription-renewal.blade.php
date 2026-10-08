<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Perpanjang Langganan — MikPay')</title>

    @include('partials.head')
    @stack('styles')
</head>
<body class="min-h-full bg-gradient-to-b from-indigo-50/80 via-slate-50 to-white text-slate-800 font-sans antialiased">
@php
    $tenant = current_tenant();
    $brand = config('mikpay.brand');
@endphp

<div class="min-h-screen flex flex-col">
    <header class="border-b border-slate-200/80 bg-white/80 backdrop-blur-sm">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <img src="{{ asset($brand['logo'] ?? 'logo-mikpay.png') }}?v=2" alt="MikPay" class="w-9 h-9 object-contain shrink-0">
                <div class="min-w-0">
                    <p class="text-sm font-extrabold text-slate-900 truncate">{{ $tenant?->name ?? 'Panel ISP' }}</p>
                    @if ($tenant?->subdomain)
                        <p class="text-[11px] font-mono text-indigo-600 truncate">{{ $tenant->subdomain }}.{{ config('mikpay.tenant_base_domain') }}</p>
                    @endif
                </div>
            </div>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-rose-600 transition">
                        <i class="fa-solid fa-right-from-bracket"></i> Keluar
                    </button>
                </form>
            @endauth
        </div>
    </header>

    <main class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10 space-y-6">
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <div>
                    <h1 class="text-base sm:text-lg font-extrabold text-rose-900">Aktifkan kembali panel Anda</h1>
                    <p class="text-xs sm:text-sm text-rose-800/90 mt-1 leading-relaxed">
                        Panel <strong>{{ $tenant?->name ?? 'tenant Anda' }}</strong> sedang nonaktif.
                        <strong>Isi saldo</strong>, lalu <strong>perpanjang paket</strong> dari saldo — panel aktif kembali otomatis.
                    </p>
                </div>
            </div>
        </div>

        @yield('content')
    </main>

    <footer class="border-t border-slate-200/80 py-4 text-center text-[11px] text-slate-500">
        MikPay · Billing & NMS per tenant · {{ $tenant?->subdomain ?? 'panel' }}.{{ config('mikpay.tenant_base_domain') }}
    </footer>
</div>

@include('components.toast')
<script src="{{ asset('js/mikpay.js') }}?v={{ @filemtime(public_path('js/mikpay.js')) ?: 2 }}" defer></script>
@stack('scripts')
</body>
</html>
