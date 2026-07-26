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

        <main class="flex-1 overflow-y-auto p-6 space-y-6">
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
    <script src="{{ asset('js/panel.js') }}?v=3" defer></script>
    @stack('scripts')
</body>
</html>
