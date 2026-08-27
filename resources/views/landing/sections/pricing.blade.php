@php
    $wa = config('mikpay.whatsapp');
    $waBase = 'https://wa.me/'.$wa['number'].'?text=';
    $headlines = $pricingHeadlines ?? [];
    $packages = $pricingPackages ?? [];
@endphp

<section id="harga" class="py-20 bg-slate-50 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12 space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-600">{{ $headlines['pricing_eyebrow'] ?? 'Investasi Terjangkau' }}</h2>
            <h3 class="text-2xl sm:text-4xl font-extrabold text-slate-900">{{ $headlines['pricing_title'] ?? 'Paket Berlangganan Tanpa Biaya Tersembunyi.' }}</h3>
            <p class="text-sm text-slate-600">{{ $headlines['pricing_subtitle'] ?? 'Pilih paket yang sesuai dengan jumlah pelanggan bisnis jaringan Anda saat ini.' }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
            @foreach ($packages as $key => $pkg)
                @php
                    $featured = ! empty($pkg['featured']);
                    $ctaType = $pkg['cta_type'] ?? 'register';
                    if ($ctaType === 'whatsapp') {
                        $href = $waBase.rawurlencode((string) ($pkg['wa_message'] ?? 'Halo Admin, saya tertarik paket '.$pkg['name']));
                        $target = ' target="_blank" rel="noopener"';
                    } elseif ($ctaType === 'checkout') {
                        $href = route('register');
                        $target = '';
                    } else {
                        $href = route('register');
                        $target = '';
                    }
                    $price = (int) ($pkg['price'] ?? 0);
                    $orig = isset($pkg['original_price']) ? (int) $pkg['original_price'] : null;
                @endphp
                <div class="p-8 rounded-2xl bg-white {{ $featured ? 'border-2 border-indigo-600 shadow-xl shadow-indigo-500/10 relative' : 'border border-slate-200 shadow-sm hover:shadow-md' }} flex flex-col justify-between space-y-6 transition">
                    @if ($featured && ! empty($pkg['featured_ribbon']))
                        <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-indigo-600 to-cyan-600 text-white text-[10px] font-extrabold px-4 py-1 rounded-full uppercase tracking-wider shadow-md">
                            {{ $pkg['featured_ribbon'] }}
                        </span>
                    @endif
                    <div class="space-y-4">
                        <span class="{{ $featured ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-700 border-slate-200' }} text-xs px-3 py-1 rounded-full font-bold inline-block border">
                            {{ $pkg['badge'] ?? strtoupper($key) }}
                        </span>
                        <div>
                            <h4 class="text-3xl font-extrabold text-slate-900">
                                Rp {{ number_format($price, 0, ',', '.') }}
                                <span class="text-xs text-slate-500 font-normal">{{ $pkg['duration_label'] ?? '/ bulan' }}</span>
                            </h4>
                            @if ($orig && $orig > $price)
                                <p class="text-xs text-slate-400 line-through mt-0.5">Rp {{ number_format($orig, 0, ',', '.') }}</p>
                            @endif
                            @if (! empty($pkg['discount_badge']))
                                <span class="inline-block mt-1 text-[10px] font-extrabold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">{{ $pkg['discount_badge'] }}</span>
                            @endif
                            <p class="text-xs text-slate-500 mt-1">{{ $pkg['subtitle'] ?? '' }}</p>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-700 pt-4 border-t border-slate-100 font-medium">
                            @foreach (($pkg['features'] ?? []) as $feature)
                                <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> {{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <a href="{{ $href }}"{!! $target !!} class="w-full py-3 rounded-xl text-xs font-bold text-center transition {{ $featured ? 'gradient-bg text-white shadow-lg shadow-indigo-600/20 hover:opacity-95' : 'border border-slate-300 bg-white hover:bg-slate-50 text-slate-800 shadow-sm' }}">
                        {{ $pkg['button_label'] ?? 'Pilih Paket' }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
