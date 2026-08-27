<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 transition-colors duration-200">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MikPay Control Center')</title>

    @include('partials.panel.head')
    @stack('styles')
</head>
<body class="h-full flex overflow-hidden font-sans antialiased text-slate-800 bg-slate-50 dark:text-slate-200 dark:bg-slate-950 transition-colors duration-200">
    @include('partials.panel.sidebar')

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50 dark:bg-slate-950 transition-colors duration-200">
        @include('partials.panel.topbar')

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 sm:space-y-6">
            @php
                $subTenant = current_tenant();
                $subEnd = $subTenant?->subscriptionEndsAt();
                $subActive = $subTenant?->subscriptionIsActive();
                $subSecs = $subEnd ? wib_now()->diffInSeconds($subEnd, false) : null;
                $subDays = $subSecs !== null
                    ? ($subSecs > 0 ? (int) ceil($subSecs / 86400) : 0)
                    : null;
                $showSubBanner = $subTenant
                    && ($subTenant->plan ?? '') !== 'tunnel'
                    && $subEnd
                    && (! $subActive || ($subDays !== null && $subDays <= 7));
            @endphp
            @if ($showSubBanner && ! request()->routeIs(['panel.subscription', 'panel.subscription.*', 'tenant.subscription', 'tenant.subscription.*']))
                <div class="rounded-xl border px-4 py-3 text-xs font-semibold flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 {{ $subActive ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-rose-200 bg-rose-50 text-rose-900' }}">
                    <span>
                        @if ($subActive)
                            Langganan/trial berakhir {{ format_wib($subEnd) }} ({{ max(0, (int) $subDays) }} hari lagi).
                        @else
                            Trial/langganan sudah berakhir. Panel terkunci sampai pembayaran disetujui.
                        @endif
                    </span>
                    <a href="{{ panel_route('subscription') }}" class="inline-flex items-center gap-1.5 font-bold underline underline-offset-2 shrink-0">
                        <i class="fa-solid fa-crown"></i> Buka Langganan MikPay
                    </a>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    @include('panel.modals.all')
    @include('components.toast')

    @php
        $tenantPayload = [
            'name' => $tenant->name ?? null,
            'subdomain' => $tenant->subdomain ?? 'cl45',
            'remote_host' => isset($tenant) ? $tenant->remoteHost() : 'cl45.remote.mikpay.link',
            'vpn_status' => $tenant->vpn_status ?? 'offline',
        ];
    @endphp
    <script>
        window.MikPay = window.MikPay || {};
        window.MikPay.tenant = @json($tenantPayload);
    </script>
    <script src="{{ asset('js/mikpay.js') }}" defer></script>
    <script src="{{ asset('js/panel.js') }}?v=5" defer></script>
    @stack('scripts')
</body>
</html>
