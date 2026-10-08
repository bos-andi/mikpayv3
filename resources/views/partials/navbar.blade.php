@php
    $home = route('home');
    $links = $navLinks ?? [
        [$home.'#fitur', 'Fitur'],
        [$home.'#cara-kerja', 'Cara Kerja'],
        [$home.'#remote-vpn', 'Remote VPN', 'GRATIS'],
        [$home.'#harga', 'Harga'],
        [$home.'#faq', 'FAQ'],
        [route('partner.landing'), 'Affiliate'],
    ];
    $ctaUrl = $navCtaUrl ?? route('register');
    $ctaLabel = $navCtaLabel ?? 'Coba Gratis';
    $solid = (bool) ($navSolid ?? false);
@endphp

<header id="siteHeader" class="fixed top-0 inset-x-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div id="siteHeaderBar" data-always-solid="{{ $solid ? '1' : '0' }}" class="mt-3 h-16 px-4 sm:px-5 flex items-center justify-between rounded-2xl border transition-all duration-300 {{ $solid ? 'bg-white/85 backdrop-blur-xl border-slate-200/80 shadow-lg shadow-slate-900/5' : 'border-transparent' }}">
            <a href="{{ $home }}" class="flex items-center gap-3">
                @include('components.logo')
            </a>

            <nav class="hidden lg:flex items-center gap-1 text-sm font-semibold text-slate-600">
                @foreach ($links as $link)
                    <a href="{{ $link[0] }}" class="px-3.5 py-2 rounded-xl hover:text-indigo-700 hover:bg-indigo-50/70 transition flex items-center gap-1.5 {{ url()->current() === rtrim($link[0], '/') ? 'text-indigo-700' : '' }}">
                        {{ $link[1] }}
                        @isset($link[2])
                            <span class="bg-gradient-to-r from-indigo-600 to-cyan-600 text-white text-[9px] px-1.5 py-0.5 rounded-md font-extrabold tracking-wide">{{ $link[2] }}</span>
                        @endisset
                    </a>
                @endforeach
            </nav>

            <div class="hidden sm:flex items-center gap-2">
                <a href="{{ route('login') }}" class="text-xs font-bold text-slate-700 hover:text-indigo-700 px-4 py-2.5 rounded-xl hover:bg-slate-100 transition">
                    Masuk
                </a>
                <a href="{{ $ctaUrl }}" class="group text-xs font-bold text-white gradient-bg pl-5 pr-4 py-2.5 rounded-xl transition shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 flex items-center gap-2">
                    {{ $ctaLabel }}
                    <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-0.5 transition"></i>
                </a>
            </div>

            <button type="button" onclick="toggleMobileMenu()" class="lg:hidden w-10 h-10 rounded-xl flex items-center justify-center text-slate-700 hover:bg-slate-100" aria-label="Menu">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>
        </div>

        <div id="mobileMenu" class="hidden lg:hidden mt-2 bg-white/95 backdrop-blur-xl border border-slate-200 rounded-2xl p-4 space-y-1 shadow-xl shadow-slate-900/5">
            @foreach ($links as $link)
                <a href="{{ $link[0] }}" onclick="toggleMobileMenu()" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
                    {{ $link[1] }}
                    @isset($link[2])
                        <span class="bg-indigo-100 text-indigo-700 text-[10px] px-2 py-0.5 rounded-full font-bold">{{ $link[2] }}</span>
                    @endisset
                </a>
            @endforeach
            <div class="pt-3 mt-2 border-t border-slate-100 grid grid-cols-2 gap-2">
                <a href="{{ route('login') }}" class="text-center text-xs font-bold text-slate-700 py-3 rounded-xl border border-slate-200 bg-slate-50">Masuk</a>
                <a href="{{ $ctaUrl }}" class="text-center text-xs font-bold text-white gradient-bg py-3 rounded-xl shadow-md">{{ $ctaLabel }}</a>
            </div>
        </div>
    </div>
</header>

@push('scripts')
<script>
(function () {
    const bar = document.getElementById('siteHeaderBar');
    if (!bar || bar.dataset.alwaysSolid === '1') return;
    const solid = ['bg-white/85', 'backdrop-blur-xl', 'border-slate-200/80', 'shadow-lg', 'shadow-slate-900/5'];
    const onScroll = () => {
        const scrolled = window.scrollY > 12;
        solid.forEach((c) => bar.classList.toggle(c, scrolled));
        bar.classList.toggle('border-transparent', !scrolled);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
})();
</script>
@endpush
