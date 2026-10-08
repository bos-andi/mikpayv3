@php
    $smallFeatures = [
        ['fa-map-location-dot', 'amber', 'Peta ODP & GIS', 'Petakan ODP, port splitter, dan rumah pelanggan di peta interaktif.'],
        ['fa-tower-cell', 'emerald', 'Monitoring OLT', 'Pantau status OLT & buka Web OLT langsung lewat tunnel remote.'],
        ['fa-ticket', 'violet', 'Voucher Hotspot', 'Generate voucher massal siap cetak, langsung tersinkron ke MikroTik.'],
        ['fa-wallet', 'rose', 'Laporan Keuangan', 'Kas masuk/keluar, aset, piutang, ekspor PDF & CSV.'],
        ['fa-credit-card', 'sky', 'Bayar Online', 'Link pembayaran untuk pelanggan via QRIS, VA & e-wallet (Xendit).'],
        ['fa-headset', 'indigo', 'Tiket Gangguan', 'Catat laporan gangguan pelanggan & pantau penyelesaiannya.'],
    ];
@endphp

<section id="fitur" class="py-20 lg:py-24 bg-slate-50 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-indigo-600 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full">Fitur Lengkap</span>
            <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Semua alat tempur ISP dalam satu dashboard.</h2>
            <p class="mt-4 text-slate-600">Semua fitur tersedia di setiap paket. Tidak ada fitur yang dikunci.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            {{-- Big card: billing automation --}}
            <div class="reveal md:col-span-2 relative overflow-hidden rounded-3xl gradient-bg p-7 sm:p-9 text-white shadow-xl shadow-indigo-600/20">
                <div class="absolute -right-16 -bottom-16 w-72 h-72 rounded-full bg-white/10"></div>
                <div class="absolute right-24 -top-20 w-48 h-48 rounded-full bg-white/5"></div>
                <div class="relative grid sm:grid-cols-2 gap-8 items-center">
                    <div>
                        <span class="w-12 h-12 rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center text-xl"><i class="fa-solid fa-robot"></i></span>
                        <h3 class="mt-5 text-2xl font-extrabold">Billing otomatis + Auto Isolir</h3>
                        <p class="mt-3 text-sm text-indigo-50/90 leading-relaxed">Tagihan terbit tiap bulan, pengingat WhatsApp terkirim sendiri, pelanggan menunggak diisolir otomatis di MikroTik, dan dibuka kembali saat lunas.</p>
                        <ul class="mt-5 space-y-2 text-sm font-semibold">
                            <li class="flex items-center gap-2"><i class="fa-solid fa-check text-cyan-200"></i> PPPoE & Hotspot</li>
                            <li class="flex items-center gap-2"><i class="fa-solid fa-check text-cyan-200"></i> Janji bayar & biaya periode</li>
                            <li class="flex items-center gap-2"><i class="fa-solid fa-check text-cyan-200"></i> Import pelanggan dari Excel</li>
                        </ul>
                    </div>
                    <div class="hidden sm:block">
                        <div class="rounded-2xl bg-white text-slate-800 p-4 shadow-2xl space-y-3">
                            <div class="flex items-center gap-2 text-[11px] font-bold text-emerald-600">
                                <i class="fa-brands fa-whatsapp text-base"></i> WhatsApp • baru saja
                            </div>
                            <p class="text-xs leading-relaxed bg-emerald-50 border border-emerald-100 rounded-xl rounded-tl-none p-3">Halo Kak Budi, tagihan internet bulan ini <b>Rp150.000</b> sudah terbit. Bayar sebelum tgl 20 ya agar koneksi tetap lancar 🙏</p>
                            <div class="flex items-center justify-between text-[11px] border-t border-slate-100 pt-3">
                                <span class="font-bold text-slate-500">Status MikroTik</span>
                                <span class="font-bold text-rose-600 bg-rose-50 border border-rose-200 rounded-full px-2 py-0.5">Isolir otomatis 21/10</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Remote VPN card --}}
            <div class="reveal relative overflow-hidden rounded-3xl bg-slate-900 p-7 text-white shadow-xl">
                <div class="absolute inset-0 bg-grid-dark"></div>
                <div class="relative">
                    <span class="w-12 h-12 rounded-2xl bg-cyan-500/15 border border-cyan-400/30 text-cyan-300 flex items-center justify-center text-xl"><i class="fa-solid fa-shield-halved"></i></span>
                    <h3 class="mt-5 text-xl font-extrabold">VPN Remote Gratis</h3>
                    <p class="mt-3 text-sm text-slate-300 leading-relaxed">Tanpa IP Public. Remote Winbox, API, Web OLT & modem pelanggan dari HP atau laptop.</p>
                    <div class="mt-5 flex flex-wrap gap-2 text-[11px] font-bold">
                        @foreach (['Winbox', 'API', 'Web OLT', 'Web Modem'] as $tag)
                            <span class="px-2.5 py-1 rounded-lg bg-white/10 border border-white/10">{{ $tag }}</span>
                        @endforeach
                    </div>
                    <a href="#remote-vpn" class="mt-6 inline-flex items-center gap-2 text-xs font-bold text-cyan-300 hover:text-cyan-200">Lihat detail <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                </div>
            </div>

            @foreach ($smallFeatures as $f)
                <div class="reveal group rounded-3xl bg-white border border-slate-200 p-6 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-500/5 hover:-translate-y-1 transition duration-300">
                    <span class="w-12 h-12 rounded-2xl bg-{{ $f[1] }}-50 text-{{ $f[1] }}-600 border border-{{ $f[1] }}-100 flex items-center justify-center text-lg group-hover:scale-110 transition">
                        <i class="fa-solid {{ $f[0] }}"></i>
                    </span>
                    <h3 class="mt-5 text-lg font-extrabold text-slate-900">{{ $f[2] }}</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $f[3] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
