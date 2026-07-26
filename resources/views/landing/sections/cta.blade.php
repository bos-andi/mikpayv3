@php
    $wa = config('mikpay.whatsapp');
    $waUrl = 'https://wa.me/'.$wa['number'].'?text='.rawurlencode($wa['consult_message']);
@endphp

<section class="py-16 gradient-bg border-t border-indigo-700 relative overflow-hidden shadow-2xl">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6 relative z-10">
        <h2 class="text-2xl sm:text-4xl font-extrabold text-white">Siap Mengotomatiskan Bisnis Internet Anda?</h2>
        <p class="text-xs sm:text-sm text-indigo-100 max-w-xl mx-auto font-medium">
            Daftar sekarang dan nikmati seluruh fitur MikPay + Remote VPN Dedicated Gratis selama 14 Hari tanpa komitmen!
        </p>
        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('register') }}" class="w-full sm:w-auto bg-white hover:bg-slate-100 text-slate-900 font-extrabold text-sm px-8 py-3.5 rounded-xl transition shadow-xl hover:scale-105">
                <i class="fa-solid fa-rocket text-indigo-600 mr-2"></i> Daftar &amp; Coba Gratis
            </a>
            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="w-full sm:w-auto bg-white/10 hover:bg-white/20 text-white font-bold text-sm px-8 py-3.5 rounded-xl transition border border-white/30">
                <i class="fa-brands fa-whatsapp mr-2"></i> Konsultasi WA
            </a>
        </div>
    </div>
</section>
