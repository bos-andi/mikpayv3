@php
    $trialDays = (int) config('mikpay.trial_days', 4);
    $steps = [
        ['fa-user-plus', 'Daftar & dapat subdomain', 'Isi nama usaha, langsung dapat panel sendiri di namaisp.mikpay.link. Gratis '.$trialDays.' hari, tanpa kartu kredit.'],
        ['fa-terminal', 'Tempel script ke MikroTik', 'Salin satu script OpenVPN dari panel ke terminal Winbox. Router langsung terhubung & bisa diremote.'],
        ['fa-chart-line', 'Kelola & tagih otomatis', 'Import pelanggan, atur paket & jatuh tempo. Tagihan WhatsApp dan auto-isolir berjalan sendiri.'],
    ];
@endphp

<section id="cara-kerja" class="py-20 lg:py-24 bg-white scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-indigo-600 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full">Cara Kerja</span>
            <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Siap dipakai dalam 5 menit.</h2>
            <p class="mt-4 text-slate-600">Tidak perlu server, tidak perlu IP Public, tidak perlu install aplikasi.</p>
        </div>

        <div class="relative grid md:grid-cols-3 gap-6 lg:gap-8">
            <div class="hidden md:block absolute top-10 left-[16%] right-[16%] h-0.5 bg-gradient-to-r from-indigo-200 via-cyan-200 to-indigo-200"></div>

            @foreach ($steps as $i => $step)
                <div class="reveal relative text-center px-4">
                    <div class="relative mx-auto w-20 h-20 rounded-3xl gradient-bg text-white flex items-center justify-center text-2xl shadow-xl shadow-indigo-500/30">
                        <i class="fa-solid {{ $step[0] }}"></i>
                        <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-white border-2 border-indigo-100 text-indigo-700 text-xs font-extrabold flex items-center justify-center">{{ $i + 1 }}</span>
                    </div>
                    <h3 class="mt-6 text-lg font-extrabold text-slate-900">{{ $step[1] }}</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed max-w-xs mx-auto">{{ $step[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
