@php
    $trialDays = (int) config('mikpay.trial_days', 4);
    $startPrice = (int) (($pricingPackages ?? [])['bulan1']['price'] ?? 35000);
    $highlights = [
        ['fa-infinity', 'Unlimited', 'Pelanggan tanpa batas'],
        ['fa-tag', 'Rp'.number_format($startPrice / 1000, 0, ',', '.').'rb', 'Mulai per bulan'],
        ['fa-gift', $trialDays.' Hari', 'Coba gratis semua fitur'],
        ['fa-shield-halved', 'Gratis', '1 akun VPN remote'],
    ];
@endphp

<section class="relative -mt-6 pb-16 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal grid grid-cols-2 lg:grid-cols-4 gap-px rounded-3xl border border-slate-200 bg-slate-100 shadow-xl shadow-slate-900/5 overflow-hidden">
            @foreach ($highlights as $item)
                <div class="flex items-center gap-3 sm:gap-4 p-4 sm:p-6 bg-white">
                    <span class="w-11 h-11 shrink-0 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i class="fa-solid {{ $item[0] }}"></i>
                    </span>
                    <div>
                        <p class="text-xl sm:text-2xl font-extrabold gradient-text leading-none">{{ $item[1] }}</p>
                        <p class="text-[11px] sm:text-xs text-slate-500 font-semibold mt-1">{{ $item[2] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="reveal mt-10 flex flex-wrap items-center justify-center gap-x-10 gap-y-4 text-slate-400">
            <span class="text-[11px] font-bold uppercase tracking-widest">Kompatibel dengan</span>
            <span class="flex items-center gap-2 text-sm font-extrabold text-slate-500"><i class="fa-solid fa-network-wired"></i> MikroTik RouterOS v6 & v7</span>
            <span class="flex items-center gap-2 text-sm font-extrabold text-slate-500"><i class="fa-solid fa-wifi"></i> PPPoE & Hotspot</span>
            <span class="flex items-center gap-2 text-sm font-extrabold text-slate-500"><i class="fa-solid fa-tower-cell"></i> OLT berbasis Web</span>
            <span class="flex items-center gap-2 text-sm font-extrabold text-slate-500"><i class="fa-brands fa-whatsapp"></i> WhatsApp Gateway</span>
        </div>
    </div>
</section>
