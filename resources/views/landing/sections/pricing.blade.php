@php
    $wa = config('mikpay.whatsapp');
    $waBase = 'https://wa.me/'.$wa['number'].'?text=';
@endphp

<section id="harga" class="py-20 bg-slate-50 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12 space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-600">Investasi Terjangkau</h2>
            <h3 class="text-2xl sm:text-4xl font-extrabold text-slate-900">Paket Berlangganan Tanpa Biaya Tersembunyi.</h3>
            <p class="text-sm text-slate-600">Pilih paket yang sesuai dengan jumlah pelanggan bisnis jaringan Anda saat ini.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
            <div class="p-8 rounded-2xl bg-white border border-slate-200 flex flex-col justify-between space-y-6 shadow-sm hover:shadow-md transition">
                <div class="space-y-4">
                    <span class="bg-slate-100 text-slate-700 text-xs px-3 py-1 rounded-full font-bold inline-block border border-slate-200">STARTER</span>
                    <div>
                        <h4 class="text-3xl font-extrabold text-slate-900">Rp 99.000 <span class="text-xs text-slate-500 font-normal">/ bulan</span></h4>
                        <p class="text-xs text-slate-500 mt-1">Cocok untuk pemula RTRW Net koin / voucheran.</p>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-700 pt-4 border-t border-slate-100 font-medium">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Hingga 100 Pelanggan</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> 1 Router MikroTik</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Auto Isolir & WA Gateway</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Hotspot Voucher Generator</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> 1 Dedicated Remote VPN Tunnel</li>
                    </ul>
                </div>
                <a href="{{ route('register') }}" class="w-full py-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-800 text-center transition shadow-sm">
                    Mulai Trial — Starter
                </a>
            </div>

            <div class="p-8 rounded-2xl bg-white border-2 border-indigo-600 flex flex-col justify-between space-y-6 shadow-xl shadow-indigo-500/10 relative">
                <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-indigo-600 to-cyan-600 text-white text-[10px] font-extrabold px-4 py-1 rounded-full uppercase tracking-wider shadow-md">
                    PALING POPULER
                </span>
                <div class="space-y-4">
                    <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs px-3 py-1 rounded-full font-bold inline-block">PRO ISP</span>
                    <div>
                        <h4 class="text-3xl font-extrabold text-slate-900">Rp 199.000 <span class="text-xs text-slate-500 font-normal">/ bulan</span></h4>
                        <p class="text-xs text-slate-500 mt-1">Solusi lengkap bisnis jaringan menengah & OLT.</p>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-700 pt-4 border-t border-slate-100 font-medium">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Hingga 500 Pelanggan</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> 3 Router MikroTik</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Pemetaan ODP & Peta GIS</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Remote OLT & TR-069 Modem</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Auto Isolir & WA Gateway</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> 3 Dedicated Remote VPN Tunnel</li>
                    </ul>
                </div>
                <a href="{{ route('register') }}" class="w-full py-3 rounded-xl gradient-bg text-xs font-bold text-white text-center shadow-lg shadow-indigo-600/20 transition hover:opacity-95">
                    Mulai Coba Gratis 14 Hari
                </a>
            </div>

            <div class="p-8 rounded-2xl bg-white border border-slate-200 flex flex-col justify-between space-y-6 shadow-sm hover:shadow-md transition">
                <div class="space-y-4">
                    <span class="bg-slate-100 text-slate-700 text-xs px-3 py-1 rounded-full font-bold inline-block border border-slate-200">ULTIMATE ISP</span>
                    <div>
                        <h4 class="text-3xl font-extrabold text-slate-900">Rp 399.000 <span class="text-xs text-slate-500 font-normal">/ bulan</span></h4>
                        <p class="text-xs text-slate-500 mt-1">Kapasitas unlimited untuk ISP skala besar.</p>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-700 pt-4 border-t border-slate-100 font-medium">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Unlimited Pelanggan</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Unlimited Router & OLT</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Peta ODP & Network Infrastructure</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Tiket Gangguan & Management Teknisi</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600"></i> Priority VIP Support 24/7</li>
                    </ul>
                </div>
                <a href="{{ $waBase.rawurlencode('Halo Admin, saya tertarik paket Enterprise') }}" target="_blank" rel="noopener" class="w-full py-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-800 text-center transition shadow-sm">
                    Hubungi Tim Sales
                </a>
            </div>
        </div>
    </div>
</section>
