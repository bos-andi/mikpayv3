@php
    $wa = config('mikpay.whatsapp');
    $waUrl = 'https://wa.me/'.$wa['number'].'?text='.rawurlencode($wa['trial_message']);
    $landing = $landing ?? [];
@endphp

<section class="pt-32 pb-20 relative overflow-hidden hero-glow-light bg-gradient-to-b from-indigo-50/60 via-slate-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto space-y-6">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-100/80 border border-indigo-200 text-indigo-700 text-xs font-bold shadow-sm">
                <span class="flex h-2 w-2 rounded-full bg-indigo-600 animate-ping"></span>
                <span>{{ $landing['hero_badge'] ?? 'Software All-in-One ISP & RTRW Net Terlengkap' }}</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </div>

            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-tight">
                {{ $landing['hero_title'] ?? 'Kelola Bisnis WiFi, Remote OLT & Auto Isolir' }}
                <span class="gradient-text">{{ $landing['hero_title_highlight'] ?? 'Tanpa Pusing.' }}</span>
            </h1>

            <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-2xl mx-auto font-medium">
                {{ $landing['hero_subtitle'] ?? 'Aplikasi manajemen terpadu untuk pengusaha RTRW Net & ISP.' }}
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                <a href="{{ route('register') }}" class="w-full sm:w-auto flex items-center justify-center gap-2 text-sm font-bold text-white gradient-bg px-8 py-4 rounded-xl transition shadow-xl shadow-indigo-600/25 hover:scale-105">
                    <i class="fa-solid fa-rocket text-lg"></i>
                    <span>{{ $landing['hero_cta_primary'] ?? ('Mulai Coba Gratis '.(int) config('mikpay.trial_days', 3).' Hari') }}</span>
                </a>
                <a href="#remote-vpn" class="w-full sm:w-auto flex items-center justify-center gap-2 text-sm font-bold text-slate-700 hover:text-slate-900 px-8 py-4 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 transition shadow-sm">
                    <i class="fa-solid fa-shield-halved text-indigo-600"></i>
                    <span>{{ $landing['hero_cta_secondary'] ?? 'Pelajari Remote VPN Gratis' }}</span>
                </a>
            </div>

            <div class="pt-6 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-500 font-semibold">
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-600"></i> Tanpa Kartu Kredit</span>
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-600"></i> Support RouterOS v6 & v7</span>
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-600"></i> Support OLT EPON / GPON</span>
            </div>
        </div>

        <div class="mt-12 relative mx-auto max-w-5xl rounded-2xl border border-slate-200 bg-white p-3 sm:p-5 shadow-2xl shadow-indigo-900/10">
            <div class="flex items-center gap-2 px-3 py-2 border-b border-slate-100 mb-3 bg-slate-50/80 rounded-t-xl">
                <div class="flex gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-rose-400 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-400 inline-block"></span>
                </div>
                <div class="mx-auto bg-white px-4 py-1 rounded-lg text-[11px] font-mono text-slate-600 flex items-center gap-2 border border-slate-200 shadow-sm">
                    <i class="fa-solid fa-lock text-emerald-600 text-[10px]"></i>
                    <span>https://mikpay.link/dashboard</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-left">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 shadow-sm">
                    <div class="flex justify-between text-xs text-slate-500 font-medium mb-1">
                        <span>Omset Bulan Ini</span>
                        <span class="text-emerald-600 font-bold">+14.2%</span>
                    </div>
                    <p class="text-xl font-extrabold text-slate-900">Rp 34.850.000</p>
                    <p class="text-[10px] text-slate-500 mt-1 font-semibold">198 Pelanggan Lunas</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 shadow-sm">
                    <div class="flex justify-between text-xs text-slate-500 font-medium mb-1">
                        <span>VPN Remote Status</span>
                        <span class="text-indigo-600 font-bold">CONNECTED</span>
                    </div>
                    <p class="text-xl font-extrabold text-slate-900">cl45.remote.mikpay.link</p>
                    <p class="text-[10px] text-slate-500 mt-1 font-semibold">WireGuard Latency: 4ms</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 shadow-sm">
                    <div class="flex justify-between text-xs text-slate-500 font-medium mb-1">
                        <span>OLT Optical Signal</span>
                        <span class="text-emerald-600 font-bold">NORMAL</span>
                    </div>
                    <p class="text-xl font-extrabold text-slate-900">4 PON <span class="text-xs font-normal text-slate-500">(144 ONU)</span></p>
                    <p class="text-[10px] text-slate-500 mt-1 font-semibold">Avg Redaman: -19.2 dBm</p>
                </div>
            </div>

            <div class="mt-3 p-4 rounded-xl bg-gradient-to-r from-indigo-50 via-white to-slate-50 border border-indigo-200 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-600 shrink-0">
                        <i class="fa-brands fa-whatsapp text-xl"></i>
                    </div>
                    <div class="text-left">
                        <p class="text-xs font-bold text-slate-900">Auto-Isolir & Broadcast WA Tagihan Berjalan</p>
                        <p class="text-[11px] text-slate-500 font-medium">12 Akun menunggak otomatis di-isolir & terkirim pesan invoice WhatsApp.</p>
                    </div>
                </div>
                <span class="bg-emerald-100 text-emerald-800 text-xs px-3 py-1 rounded-full font-mono font-bold border border-emerald-200 shrink-0">
                    WA GATEWAY READY
                </span>
            </div>
        </div>
    </div>
</section>
