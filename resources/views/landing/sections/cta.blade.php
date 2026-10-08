@php
    $wa = config('mikpay.whatsapp');
    $waUrl = 'https://wa.me/'.$wa['number'].'?text='.rawurlencode($wa['consult_message']);
    $landing = $landing ?? [];
    $trialDays = (int) config('mikpay.trial_days', 4);
@endphp

<section class="py-16 lg:py-20 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal relative overflow-hidden rounded-[2rem] gradient-bg px-6 py-14 sm:px-14 sm:py-16 text-center shadow-2xl shadow-indigo-600/25">
            <div class="absolute inset-0 bg-grid-dark opacity-60"></div>
            <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-28 -right-20 w-80 h-80 rounded-full bg-white/10"></div>

            <div class="relative max-w-2xl mx-auto">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight">{{ $landing['cta_title'] ?? 'Siap Mengotomatiskan Bisnis Internet Anda?' }}</h2>
                <p class="mt-5 text-indigo-50/90 sm:text-lg">
                    {{ $landing['cta_subtitle'] ?? ('Daftar sekarang dan nikmati seluruh fitur MikPay + VPN remote gratis selama '.$trialDays.' hari tanpa komitmen.') }}
                </p>
                <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('register') }}" class="group w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white text-slate-900 font-extrabold text-sm px-8 py-4 rounded-2xl shadow-xl hover:-translate-y-0.5 transition">
                        <i class="fa-solid fa-rocket text-indigo-600"></i> {{ $landing['cta_button'] ?? 'Daftar & Coba Gratis' }}
                        <i class="fa-solid fa-arrow-right text-xs text-slate-400 group-hover:translate-x-0.5 transition"></i>
                    </a>
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 text-white font-bold text-sm px-8 py-4 rounded-2xl border border-white/30 transition">
                        <i class="fa-brands fa-whatsapp"></i> Konsultasi WA
                    </a>
                </div>
                <p class="mt-6 text-xs text-indigo-100/80 font-semibold">Tanpa kartu kredit · Aktif dalam hitungan menit · Batalkan kapan saja</p>
            </div>
        </div>
    </div>
</section>
