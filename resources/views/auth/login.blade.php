@extends('layouts.app')

@section('title', 'Login — MikPay')

@php
    $isTunnelHost = request()->getHost() === config('mikpay.tunnel_domain', 'tunnel.mikpay.link');
    $tenant = $loginTenant ?? current_tenant();
    $homeUrl = $isTunnelHost ? route('tunnel.home') : route('home');
    $highlights = [
        ['fa-robot', 'Tagihan & auto-isolir otomatis', 'WhatsApp terkirim sendiri, isolir tepat waktu.'],
        ['fa-shield-halved', 'VPN remote tanpa IP Public', 'Winbox, API, Web OLT dari mana saja.'],
        ['fa-map-location-dot', 'Peta ODP & pelanggan', 'Semua titik jaringan dalam satu peta.'],
    ];
@endphp

@section('content')
<section class="min-h-screen flex bg-white">
    {{-- Brand panel --}}
    <div class="hidden lg:flex lg:w-[46%] xl:w-1/2 relative overflow-hidden gradient-bg text-white">
        <div class="absolute inset-0 bg-grid-dark opacity-70"></div>
        <div class="absolute -top-32 -left-32 w-[420px] h-[420px] rounded-full bg-white/10"></div>
        <div class="absolute -bottom-40 -right-24 w-[480px] h-[480px] rounded-full bg-cyan-300/20 blur-2xl"></div>

        <div class="relative z-10 flex flex-col justify-between w-full p-10 xl:p-14">
            <a href="{{ $homeUrl }}" class="inline-flex items-center gap-3 w-fit">
                <span class="w-11 h-11 rounded-2xl bg-white p-1.5 shadow-lg">
                    <img src="{{ asset(config('mikpay.brand.logo') ?: 'logo-mikpay.png') }}?v=2" alt="MikPay" class="w-full h-full object-contain">
                </span>
                <span>
                    <span class="block text-xl font-extrabold leading-none">MikPay<span class="text-cyan-200">.link</span></span>
                    <span class="block text-[10px] font-bold tracking-[0.2em] uppercase text-indigo-100/80 mt-1">{{ config('mikpay.brand.tagline') }}</span>
                </span>
            </a>

            <div class="max-w-md">
                <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest bg-white/10 border border-white/20 px-3 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span> Control Center
                </span>
                <h1 class="mt-5 text-4xl xl:text-5xl font-extrabold leading-[1.1] tracking-tight">
                    Kelola jaringan & tagihan dalam satu panel.
                </h1>
                <p class="mt-4 text-indigo-50/85 leading-relaxed">Masuk untuk memantau pelanggan, tagihan, dan router MikroTik Anda secara real-time.</p>

                <div class="mt-9 space-y-3">
                    @foreach ($highlights as $item)
                        <div class="flex items-center gap-4 rounded-2xl bg-white/10 border border-white/15 backdrop-blur-sm px-4 py-3.5">
                            <span class="w-10 h-10 shrink-0 rounded-xl bg-white text-indigo-600 flex items-center justify-center shadow-md"><i class="fa-solid {{ $item[0] }}"></i></span>
                            <div>
                                <p class="text-sm font-extrabold">{{ $item[1] }}</p>
                                <p class="text-xs text-indigo-50/75">{{ $item[2] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between text-xs text-indigo-100/70 font-semibold">
                <span>© {{ date('Y') }} mikpay.link</span>
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-lock"></i> Koneksi terenkripsi SSL</span>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="flex-1 relative flex items-center justify-center px-5 py-12 sm:px-8 overflow-hidden bg-gradient-to-b from-indigo-50/70 via-white to-white lg:bg-none">
        <div class="absolute inset-0 bg-grid lg:hidden"></div>
        <div class="glow-blob w-80 h-80 bg-indigo-300/30 -top-20 -right-20 lg:hidden"></div>

        <div class="relative w-full max-w-[420px]">
            <div class="lg:hidden text-center mb-8">
                <a href="{{ $homeUrl }}" class="inline-flex items-center justify-center gap-3">
                    @include('components.logo')
                </a>
            </div>

            <div class="bg-white lg:bg-transparent rounded-3xl border border-slate-200 lg:border-0 shadow-2xl shadow-indigo-900/10 lg:shadow-none p-6 sm:p-8 lg:p-0">
                <div class="mb-7">
                    <span class="w-12 h-12 rounded-2xl gradient-bg text-white flex items-center justify-center text-lg shadow-lg shadow-indigo-500/30 mb-5">
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Selamat datang kembali 👋</h2>
                    <p class="mt-2 text-sm text-slate-500">
                        @if ($tenant)
                            Masuk ke panel <span class="font-bold text-slate-800">{{ $tenant->name }}</span>
                        @else
                            Masuk ke Control Center MikPay Anda.
                        @endif
                    </p>
                </div>

                @if (session('status') || session('success'))
                    <div class="mb-5 flex items-start gap-2.5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700 font-semibold">
                        <i class="fa-solid fa-circle-check mt-0.5"></i>
                        <span>{{ session('status') ?: session('success') }}</span>
                    </div>
                @endif

                @if (session('error') && ! $errors->any())
                    <div class="mb-5 flex items-start gap-2.5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-semibold">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 flex items-start gap-2.5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-semibold">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form id="loginForm" method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-2">Email</label>
                        <div class="relative group">
                            <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-600 transition"></i>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                class="w-full rounded-2xl border {{ $errors->has('email') ? 'border-rose-300' : 'border-slate-200' }} bg-slate-50 pl-11 pr-4 py-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 transition"
                                placeholder="nama@bisnisanda.com"
                            >
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-xs font-bold text-slate-700">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs text-indigo-600 font-bold hover:text-indigo-700">Lupa password?</a>
                        </div>
                        <div class="relative group">
                            <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-600 transition"></i>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan password"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-11 pr-12 py-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 transition"
                            >
                            <button type="button" data-password-toggle="password" tabindex="-1" aria-label="Tampilkan password"
                                class="absolute inset-y-0 right-0 z-10 flex items-center justify-center w-12 text-slate-400 hover:text-indigo-600 transition">
                                <i class="fa-solid fa-eye-slash text-sm pointer-events-none" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <label class="flex items-center gap-2.5 text-sm text-slate-600 font-medium cursor-pointer select-none w-fit">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Ingat saya di perangkat ini
                    </label>

                    <x-turnstile />

                    <button type="submit" class="group w-full inline-flex items-center justify-center gap-2 gradient-bg text-white text-sm font-bold py-4 rounded-2xl shadow-xl shadow-indigo-600/25 hover:shadow-indigo-600/40 hover:-translate-y-0.5 transition disabled:opacity-70 disabled:translate-y-0">
                        <span data-label>Masuk ke Panel</span>
                        <i class="fa-solid fa-arrow-right text-xs group-hover:translate-x-0.5 transition"></i>
                    </button>
                </form>

                <div class="mt-7 pt-6 border-t border-slate-100 text-center text-sm text-slate-500">
                    @if ($isTunnelHost)
                        Belum punya akun Tunnel?
                        <a href="{{ route('tunnel.register') }}" class="text-violet-600 font-bold hover:underline">Daftar di sini</a>
                    @else
                        Belum punya akun?
                        <a href="{{ route('register') }}" class="font-bold gradient-text hover:opacity-80">Coba gratis {{ (int) config('mikpay.trial_days', 4) }} hari</a>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex items-center justify-center gap-5 text-xs font-semibold text-slate-500">
                <a href="{{ $homeUrl }}" class="hover:text-indigo-600 transition"><i class="fa-solid fa-arrow-left mr-1.5"></i>Kembali ke beranda</a>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <a href="https://wa.me/{{ config('mikpay.whatsapp.number') }}" target="_blank" rel="noopener" class="hover:text-emerald-600 transition"><i class="fa-brands fa-whatsapp mr-1.5"></i>Bantuan</a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('loginForm');
    if (!form) return;
    const btn = form.querySelector('[type=submit]');
    const label = btn.querySelector('[data-label]');
    const icon = btn.querySelector('i');
    const idle = { text: label.textContent, icon: icon.className };

    form.addEventListener('submit', () => {
        btn.disabled = true;
        label.textContent = 'Memproses...';
        icon.className = 'fa-solid fa-circle-notch fa-spin';
    });

    window.addEventListener('pageshow', () => {
        btn.disabled = false;
        label.textContent = idle.text;
        icon.className = idle.icon;
    });
})();
</script>
@endpush
