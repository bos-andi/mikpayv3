@php
    $brand = config('mikpay.brand');
    $wa = config('mikpay.whatsapp');
    $waUrl = 'https://wa.me/'.$wa['number'].'?text='.rawurlencode($wa['trial_message']);
    $home = route('home');
@endphp

<header class="fixed top-0 left-0 right-0 z-50 bg-white/85 backdrop-blur-md border-b border-slate-200/80 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <a href="{{ $home }}" class="flex items-center gap-3">
            @include('components.logo')
        </a>

        <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
            <a href="{{ $home }}#fitur" class="hover:text-indigo-600 transition">Fitur Utama</a>
            <a href="{{ $home }}#remote-vpn" class="hover:text-indigo-600 transition flex items-center gap-1.5">
                <span>Remote VPN</span>
                <span class="bg-indigo-100 text-indigo-700 text-[10px] px-2 py-0.5 rounded-full border border-indigo-200 font-bold">GRATIS</span>
            </a>
            <a href="{{ $home }}#peta-odp" class="hover:text-indigo-600 transition">Peta ODP</a>
            <a href="{{ $home }}#harga" class="hover:text-indigo-600 transition">Paket Harga</a>
            <a href="{{ $home }}#faq" class="hover:text-indigo-600 transition">FAQ</a>
        </nav>

        <div class="hidden sm:flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-xs font-bold text-slate-700 hover:text-indigo-600 px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 transition shadow-sm">
                    Masuk
                </a>
                <a
                    href="{{ route('register') }}"
                    class="text-xs font-bold text-white gradient-bg px-5 py-2.5 rounded-xl transition shadow-lg shadow-indigo-500/20 hover:opacity-95 hover:shadow-indigo-500/30"
                >
                    Daftar Gratis
                </a>
        </div>

        <button type="button" onclick="toggleMobileMenu()" class="md:hidden text-slate-600 hover:text-slate-900 p-2" aria-label="Menu">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
    </div>

    <div id="mobileMenu" class="hidden md:hidden bg-white border-b border-slate-200 px-6 py-4 space-y-3 shadow-lg">
        <a href="{{ $home }}#fitur" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-slate-700 hover:text-indigo-600">Fitur Utama</a>
        <a href="{{ $home }}#remote-vpn" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-slate-700 hover:text-indigo-600">Free Remote VPN</a>
        <a href="{{ $home }}#peta-odp" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-slate-700 hover:text-indigo-600">Peta ODP & GIS</a>
        <a href="{{ $home }}#harga" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-slate-700 hover:text-indigo-600">Paket Harga</a>
        <a href="{{ $home }}#faq" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-slate-700 hover:text-indigo-600">FAQ</a>
        <div class="pt-3 border-t border-slate-200 flex flex-col gap-2">
            <a href="{{ route('login') }}" onclick="toggleMobileMenu()" class="text-center text-xs font-bold text-slate-700 py-2.5 rounded-xl border border-slate-300 bg-slate-50">Masuk</a>
            <a href="{{ route('register') }}" onclick="toggleMobileMenu()" class="text-center text-xs font-bold text-white gradient-bg py-2.5 rounded-xl shadow-md">Daftar Gratis</a>
        </div>
    </div>
</header>
