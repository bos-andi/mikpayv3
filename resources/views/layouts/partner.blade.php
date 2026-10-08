<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Affiliate') — MikPay</title>
    @include('partials.head')
    @stack('styles')
</head>
@php
    $partner = auth()->user()->partner;
    $nav = [
        ['partner.dashboard', 'fa-gauge-high', 'Dashboard'],
        ['partner.referrals', 'fa-users', 'Pelanggan Rujukan'],
        ['partner.commissions', 'fa-coins', 'Komisi'],
        ['partner.withdrawals', 'fa-building-columns', 'Pencairan'],
    ];
@endphp
<body class="min-h-full bg-slate-100 text-slate-800 font-sans antialiased">
    <div id="partnerSidebarBackdrop" class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm hidden lg:hidden" onclick="togglePartnerSidebar(false)"></div>

    <div class="min-h-screen lg:flex">
        <aside id="partnerSidebar" class="fixed lg:sticky top-0 z-50 h-screen w-72 shrink-0 -translate-x-full lg:translate-x-0 transition-transform duration-300 bg-slate-950 text-slate-300 flex flex-col overflow-hidden">
            <div class="absolute inset-0 bg-grid-dark opacity-40 pointer-events-none"></div>
            <div class="absolute -top-24 -left-24 w-64 h-64 rounded-full bg-indigo-600/20 blur-3xl pointer-events-none"></div>

            <div class="relative h-16 px-5 flex items-center justify-between border-b border-white/5">
                <a href="{{ route('partner.dashboard') }}" class="flex items-center gap-3">
                    <img src="{{ asset(config('mikpay.brand.logo', 'logo-mikpay.png')) }}?v=2" alt="MikPay" class="w-9 h-9 object-contain">
                    <div>
                        <p class="font-extrabold text-white text-sm tracking-wide">{{ config('mikpay.brand.name', 'MikPay') }}<span class="text-indigo-300">.link</span></p>
                        <p class="text-[10px] uppercase tracking-widest text-emerald-300 font-bold">Panel Affiliate</p>
                    </div>
                </a>
                <button type="button" class="lg:hidden w-9 h-9 rounded-lg hover:bg-white/10 text-slate-300" onclick="togglePartnerSidebar(false)" aria-label="Tutup menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            @if ($partner->isActive() && $partner->code)
                <div class="relative mx-4 mt-4 rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-800 p-4 text-white shadow-lg shadow-indigo-900/40">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-200">Kode referral</p>
                    <div class="mt-1 flex items-center justify-between gap-2">
                        <p class="font-mono text-xl font-extrabold tracking-wider">{{ $partner->code }}</p>
                        <button type="button" onclick="partnerCopy(@js($partner->referralUrl()), this)" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 transition">
                            <i class="fa-regular fa-copy"></i> Link
                        </button>
                    </div>
                </div>
            @endif

            <nav class="relative flex-1 p-4 space-y-1 text-sm overflow-y-auto">
                @foreach ($nav as [$route, $icon, $label])
                    @php $active = request()->routeIs($route.'*'); @endphp
                    <a href="{{ route($route) }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold transition {{ $active ? 'bg-white text-slate-900 shadow-lg shadow-black/20' : 'hover:bg-white/5 hover:text-white' }}">
                        <i class="fa-solid {{ $icon }} w-5 text-center {{ $active ? 'text-indigo-600' : 'text-slate-500' }}"></i> {{ $label }}
                    </a>
                @endforeach

                <div class="pt-4 mt-4 border-t border-white/5 space-y-1">
                    <a href="{{ route('partner.landing') }}" target="_blank" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-white/5 hover:text-white">
                        <i class="fa-solid fa-circle-info w-5 text-center text-slate-500"></i> Info Program Affiliate
                    </a>
                    @if (config('mikpay.whatsapp.number'))
                        <a href="https://wa.me/{{ config('mikpay.whatsapp.number') }}?text={{ rawurlencode('Halo admin MikPay, saya affiliate '.($partner->code ?: auth()->user()->name).'. ') }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-white/5 hover:text-white">
                            <i class="fa-brands fa-whatsapp w-5 text-center text-slate-500"></i> Hubungi Admin
                        </a>
                    @endif
                </div>
            </nav>

            <div class="relative p-4 border-t border-white/5">
                <div class="flex items-center gap-3 px-2 mb-3">
                    <span class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-cyan-400 text-slate-950 flex items-center justify-center font-extrabold">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-rose-500/10 text-rose-300 text-sm font-semibold">
                        <i class="fa-solid fa-right-from-bracket w-5 text-center"></i> Keluar
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 min-w-0">
            <header class="sticky top-0 z-30 h-16 bg-white/85 backdrop-blur-xl border-b border-slate-200 px-4 sm:px-6 flex items-center gap-3">
                <button type="button" class="lg:hidden w-10 h-10 rounded-xl hover:bg-slate-100 text-slate-700" onclick="togglePartnerSidebar(true)" aria-label="Buka menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-extrabold text-slate-900 truncate">@yield('heading', 'Panel Affiliate')</h1>
                    <p class="hidden sm:block text-[11px] text-slate-500 truncate">@yield('subheading', 'Program Affiliate MikPay')</p>
                </div>
                <span class="ml-auto inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full border {{ $partner->statusBadgeClass() }}">
                    <i class="fa-solid fa-circle text-[6px]"></i> {{ $partner->statusLabel() }}
                </span>
            </header>

            <div class="p-4 sm:p-6 max-w-6xl">
                @if (session('success'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3 flex gap-2">
                        <i class="fa-solid fa-circle-check mt-0.5"></i> {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs font-semibold px-4 py-3 flex gap-2">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i> {{ session('error') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs font-semibold px-4 py-3">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>

    <div id="partnerToast" class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[60] hidden items-center gap-2 rounded-xl bg-slate-900 text-white text-xs font-semibold px-4 py-3 shadow-xl">
        <i class="fa-solid fa-circle-check text-emerald-400"></i> <span id="partnerToastText">Disalin</span>
    </div>

    @include('components.mikpay-dialogs')
    <script>
    function togglePartnerSidebar(open) {
        document.getElementById('partnerSidebar').classList.toggle('-translate-x-full', !open);
        document.getElementById('partnerSidebarBackdrop').classList.toggle('hidden', !open);
    }
    function partnerToast(text) {
        const el = document.getElementById('partnerToast');
        document.getElementById('partnerToastText').textContent = text;
        el.classList.remove('hidden');
        el.classList.add('flex');
        clearTimeout(window.__partnerToastTimer);
        window.__partnerToastTimer = setTimeout(() => { el.classList.add('hidden'); el.classList.remove('flex'); }, 2200);
    }
    async function partnerCopy(text, btn) {
        try {
            await navigator.clipboard.writeText(text);
        } catch (e) {
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
        }
        partnerToast('Berhasil disalin');
    }
    </script>
    @stack('scripts')
</body>
</html>
