<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Superadmin — MikPay')</title>
    @include('partials.head')
    @stack('styles')
</head>
<body class="min-h-full bg-slate-100 text-slate-800 font-sans antialiased">
    <div class="min-h-screen flex">
        <aside class="w-64 bg-slate-900 text-slate-200 flex flex-col shrink-0">
            <div class="h-16 px-5 flex items-center gap-3 border-b border-slate-800">
                <img src="{{ asset(config('mikpay.brand.logo', 'logo-mikpay.png')) }}?v=2" alt="MikPay" class="w-9 h-9 object-contain shrink-0">
                <div>
                    <p class="font-extrabold text-white text-sm tracking-wide">{{ config('mikpay.brand.name', 'MikPay') }}<span class="text-violet-300">.link</span></p>
                    <p class="text-[10px] uppercase tracking-widest text-violet-300 font-bold">Superadmin</p>
                </div>
            </div>

            <nav class="flex-1 p-3 space-y-1 text-sm">
                <a href="{{ route('superadmin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('superadmin.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 text-slate-300' }}">
                    <i class="fa-solid fa-gauge w-5 text-center"></i> Dashboard
                </a>
                <a href="{{ route('superadmin.users') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('superadmin.users*') || request()->routeIs('superadmin.tenants*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 text-slate-300' }}">
                    <i class="fa-solid fa-users w-5 text-center"></i> Pengguna & Tenant
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-500 cursor-not-allowed" title="Segera hadir">
                    <i class="fa-solid fa-newspaper w-5 text-center"></i> Konten Landing
                    <span class="ml-auto text-[9px] bg-slate-800 px-1.5 py-0.5 rounded font-bold">SOON</span>
                </a>
                <a href="{{ route('panel.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 text-slate-300">
                    <i class="fa-solid fa-display w-5 text-center"></i> Buka Panel ISP
                </a>
            </nav>

            <div class="p-3 border-t border-slate-800">
                <p class="text-[11px] text-slate-400 truncate px-2 mb-2">{{ auth()->user()->email }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-rose-500/10 text-rose-300 text-sm">
                        <i class="fa-solid fa-right-from-bracket w-5 text-center"></i> Keluar
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 min-w-0">
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between">
                <h1 class="text-sm font-bold text-slate-900">@yield('heading', 'Superadmin')</h1>
                <a href="{{ route('home') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600">Lihat Landing</a>
            </header>
            <div class="p-6">
                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
