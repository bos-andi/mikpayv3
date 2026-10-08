@php
    $brand = config('mikpay.brand');
    $wa = config('mikpay.whatsapp');
    $home = route('home');
    $waUrl = 'https://wa.me/'.$wa['number'].'?text='.rawurlencode($wa['consult_message'] ?? '');
    $telegramUrl = config('mikpay.telegram.group_url');
    $columns = [
        'Produk' => [
            [$home.'#fitur', 'Fitur'],
            [$home.'#remote-vpn', 'VPN Remote Gratis'],
            [$home.'#peta-odp', 'Tampilan Panel'],
            [$home.'#harga', 'Harga'],
        ],
        'Bantuan' => [
            [$home.'#cara-kerja', 'Cara Kerja'],
            [$home.'#faq', 'FAQ'],
            [$waUrl, 'Hubungi Kami'],
            ...($telegramUrl ? [[$telegramUrl, 'Grup Telegram']] : []),
        ],
        'Akun' => [
            [route('register'), 'Daftar Gratis'],
            [route('login'), 'Masuk'],
            [route('password.request'), 'Lupa Password'],
            [route('partner.landing'), 'Program Affiliate'],
        ],
        'Perusahaan' => [
            [route('legal.about'), 'Tentang Kami'],
            [route('legal.terms'), 'Syarat & Ketentuan'],
            [route('legal.privacy'), 'Kebijakan Privasi'],
            [route('legal.refund'), 'Kebijakan Pengembalian Dana'],
        ],
    ];
    $company = config('mikpay.company');
@endphp

<footer class="bg-slate-950 text-slate-400 relative overflow-hidden">
    <div class="absolute inset-0 bg-grid-dark opacity-50"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-8">
        <div class="grid grid-cols-2 md:grid-cols-12 gap-10">
            <div class="col-span-2 md:col-span-4">
                <a href="{{ $home }}" class="inline-flex items-center gap-3">
                    <img src="{{ asset($brand['logo'] ?? 'logo-mikpay.png') }}?v=2" alt="{{ $brand['name'] }}" class="w-10 h-10 object-contain rounded-xl bg-white p-1">
                    <div>
                        <p class="text-lg font-extrabold text-white leading-none">{{ $brand['name'] ?? 'MikPay' }}<span class="text-cyan-400">.link</span></p>
                        <p class="text-[11px] font-semibold text-slate-500 mt-1">{{ $brand['tagline'] ?? '' }}</p>
                    </div>
                </a>
                <p class="mt-5 text-sm leading-relaxed max-w-sm">Software billing, auto-isolir, VPN remote & pemetaan ODP untuk ISP dan RT RW Net. Dibuat untuk pengguna MikroTik di Indonesia.</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-bold text-white bg-white/5 hover:bg-white/10 border border-white/10 px-4 py-2.5 rounded-xl transition">
                        <i class="fa-brands fa-whatsapp text-emerald-400 text-base"></i> +{{ $wa['number'] }}
                    </a>
                    @if ($telegramUrl)
                        <a href="{{ $telegramUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-bold text-white bg-[#229ED9]/90 hover:bg-[#229ED9] px-4 py-2.5 rounded-xl transition">
                            <i class="fa-brands fa-telegram text-base"></i> Gabung Grup
                        </a>
                    @endif
                </div>
                <ul class="mt-5 space-y-2 text-xs leading-relaxed">
                    <li class="flex gap-2"><i class="fa-regular fa-envelope text-cyan-400 mt-0.5"></i><a href="mailto:{{ $company['email'] }}" class="hover:text-white transition">{{ $company['email'] }}</a></li>
                    <li class="flex gap-2"><i class="fa-solid fa-location-dot text-cyan-400 mt-0.5"></i><span>{{ $company['address'] }}</span></li>
                </ul>
            </div>

            @foreach ($columns as $title => $links)
                <div class="md:col-span-2">
                    <p class="text-xs font-bold uppercase tracking-widest text-white">{{ $title }}</p>
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach ($links as $link)
                            <li><a href="{{ $link[0] }}" @if (str_starts_with($link[0], 'https://wa.me') || str_starts_with($link[0], 'https://t.me')) target="_blank" rel="noopener" @endif class="hover:text-white transition">{{ $link[1] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-14 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <p>© {{ date('Y') }} {{ $brand['domain'] ?? 'mikpay.link' }} · {{ $company['name'] }} · NIB {{ $company['nib'] }}</p>
            <p class="flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-cyan-400"></i> Data tersimpan aman & terpisah per akun</p>
        </div>
    </div>
</footer>

<a href="{{ $waUrl }}" target="_blank" rel="noopener" aria-label="Chat WhatsApp"
   class="fixed bottom-5 right-5 z-40 group flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full shadow-xl shadow-emerald-600/30 pl-4 pr-4 sm:pr-5 py-3.5 transition hover:-translate-y-0.5">
    <i class="fa-brands fa-whatsapp text-2xl"></i>
    <span class="hidden sm:inline text-sm font-bold">Tanya Admin</span>
</a>
