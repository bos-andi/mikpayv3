@php
    $headlines = $pricingHeadlines ?? [];
    $packages = $pricingPackages ?? [];
    $features = $pricingFeatures ?? [];
    $trialDays = (int) config('mikpay.trial_days', 4);
@endphp

<section id="harga" class="relative py-20 lg:py-28 bg-slate-50 overflow-hidden scroll-mt-20">
    <div class="glow-blob w-[600px] h-[600px] bg-indigo-300/20 top-20 left-1/2 -translate-x-1/2"></div>

    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-indigo-600 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full">{{ $headlines['pricing_eyebrow'] ?? 'Harga Simpel' }}</span>
            <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">{{ $headlines['pricing_title'] ?? 'Satu Paket, Semua Fitur, Unlimited Pelanggan.' }}</h2>
            <p class="mt-4 text-slate-600">{{ $headlines['pricing_subtitle'] ?? '' }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 items-stretch">
            @foreach ($packages as $key => $pkg)
                @php
                    $featured = ! empty($pkg['featured']);
                    $price = (int) $pkg['price'];
                @endphp
                <div class="reveal relative flex flex-col rounded-3xl p-7 lg:p-8 transition duration-300 {{ $featured ? 'bg-white gradient-border shadow-2xl shadow-indigo-500/15 md:-translate-y-3' : 'bg-white border border-slate-200 shadow-sm hover:shadow-xl hover:-translate-y-1' }}">
                    @if (! empty($pkg['featured_ribbon']))
                        <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 whitespace-nowrap {{ $featured ? 'gradient-bg text-white shadow-lg shadow-indigo-500/30' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }} text-[10px] font-extrabold px-4 py-1.5 rounded-full uppercase tracking-wider">
                            {{ $pkg['featured_ribbon'] }}
                        </span>
                    @endif

                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-base font-extrabold text-slate-900">{{ $pkg['name'] }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $pkg['subtitle'] ?? '' }}</p>
                        </div>
                        @if (($pkg['savings_percent'] ?? 0) > 0)
                            <span class="shrink-0 text-[11px] font-extrabold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full">-{{ $pkg['savings_percent'] }}%</span>
                        @endif
                    </div>

                    <div class="mt-6">
                        <div class="flex items-end gap-1.5">
                            <span class="text-sm font-bold text-slate-500 mb-1.5">Rp</span>
                            <span class="text-5xl font-extrabold tracking-tight {{ $featured ? 'gradient-text' : 'text-slate-900' }}">{{ number_format($price, 0, ',', '.') }}</span>
                            <span class="text-xs text-slate-500 font-semibold mb-2">{{ $pkg['duration_label'] }}</span>
                        </div>
                        <div class="mt-2 min-h-[40px] text-xs">
                            @if ($pkg['normal_price'])
                                <p class="text-slate-400">
                                    <span class="line-through">Rp{{ number_format((int) $pkg['normal_price'], 0, ',', '.') }}</span>
                                    <span class="ml-1 font-bold text-emerald-600">Hemat Rp{{ number_format((int) $pkg['savings'], 0, ',', '.') }}</span>
                                </p>
                                <p class="mt-0.5 text-slate-500 font-semibold">Setara Rp{{ number_format((int) $pkg['monthly_equivalent'], 0, ',', '.') }}/bulan</p>
                            @else
                                <p class="text-slate-500 font-semibold">Tanpa kontrak, perpanjang kapan saja.</p>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('register') }}" class="mt-6 w-full py-3.5 rounded-2xl text-sm font-bold text-center transition {{ $featured ? 'gradient-bg text-white shadow-lg shadow-indigo-600/25 hover:shadow-indigo-600/40' : 'border border-slate-300 bg-white hover:border-indigo-300 hover:text-indigo-700 text-slate-800' }}">
                        {{ $pkg['button_label'] ?? 'Mulai Coba Gratis' }}
                    </a>

                    <ul class="mt-7 pt-6 border-t border-slate-100 space-y-3 text-sm text-slate-700 flex-1">
                        @foreach ($features as $feature)
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 w-4 h-4 shrink-0 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-check text-[9px]"></i></span>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                        @if ($pkg['savings'] > 0)
                            <li class="flex items-start gap-2.5 text-amber-700 font-bold">
                                <span class="mt-0.5 w-4 h-4 shrink-0 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center"><i class="fa-solid fa-tag text-[8px]"></i></span>
                                <span>Hemat Rp{{ number_format((int) $pkg['savings'], 0, ',', '.') }} dibanding bulanan</span>
                            </li>
                        @endif
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="reveal mt-12 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-xs text-slate-600 font-semibold">
            <span><i class="fa-solid fa-gift text-indigo-600 mr-1.5"></i>Coba gratis {{ $trialDays }} hari, tanpa kartu kredit</span>
            <span><i class="fa-solid fa-rotate text-indigo-600 mr-1.5"></i>Perpanjang lebih awal, sisa masa aktif tidak hangus</span>
            <span><i class="fa-solid fa-wallet text-indigo-600 mr-1.5"></i>Bayar via QRIS, VA & e-wallet</span>
        </div>
    </div>
</section>
