@php
    $tabs = [
        ['peta', 'fa-map-location-dot', 'Peta ODP & GIS'],
        ['billing', 'fa-file-invoice-dollar', 'Billing & WhatsApp'],
        ['olt', 'fa-tower-cell', 'Monitoring OLT'],
        ['voucher', 'fa-ticket', 'Voucher Hotspot'],
    ];
@endphp

<section id="peta-odp" class="py-20 lg:py-24 bg-white scroll-mt-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-10">
            <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-indigo-600 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full">Lihat Antarmuka</span>
            <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Satu sistem untuk owner, admin & teknisi.</h2>
        </div>

        <div class="reveal flex justify-center mb-8">
            <div class="inline-flex gap-1 p-1.5 rounded-2xl bg-slate-100 border border-slate-200 overflow-x-auto max-w-full">
                @foreach ($tabs as $i => $tab)
                    <button type="button" onclick="switchPreviewTab('{{ $tab[0] }}')" id="btn-tab-{{ $tab[0] }}"
                        class="tab-preview-btn shrink-0 px-4 sm:px-5 py-2.5 rounded-xl text-xs font-bold transition {{ $i === 0 ? 'bg-white text-indigo-700 shadow-md' : 'text-slate-600' }}">
                        <i class="fa-solid {{ $tab[1] }} mr-1.5"></i>{{ $tab[2] }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="reveal relative">
            <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-tr from-indigo-500/10 to-cyan-400/10 blur-2xl"></div>
            <div class="relative rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 bg-slate-50/80">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                    <span class="mx-auto text-[10px] font-mono text-slate-400">namaisp.mikpay.link</span>
                </div>

                <div class="p-5 sm:p-7 min-h-[340px]">
                    {{-- Peta --}}
                    <div id="preview-peta" class="tab-preview-content space-y-4">
                        <div class="flex justify-between items-center gap-3 flex-wrap">
                            <h3 class="font-extrabold text-slate-900">Pemetaan ODP & jalur fiber</h3>
                            <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2.5 py-1 rounded-full">12 ODP aktif</span>
                        </div>
                        <div class="h-72 rounded-2xl bg-slate-900 relative overflow-hidden">
                            <div class="absolute inset-0 opacity-30 bg-[radial-gradient(#6366f1_1px,transparent_1px)] [background-size:18px_18px]"></div>
                            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 600 280" preserveAspectRatio="none">
                                <path d="M80 140 C 180 60, 240 60, 300 120 S 440 220, 520 150" stroke="#22d3ee" stroke-width="2" stroke-dasharray="6 6" fill="none"/>
                                <path d="M300 120 C 330 60, 380 40, 450 60" stroke="#818cf8" stroke-width="2" stroke-dasharray="6 6" fill="none"/>
                            </svg>
                            <div class="absolute left-[10%] top-[42%] text-center">
                                <span class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-400/40 text-amber-300 flex items-center justify-center text-lg mx-auto"><i class="fa-solid fa-server"></i></span>
                                <p class="text-[10px] font-bold text-white mt-1">OLT Pusat</p>
                            </div>
                            <div class="absolute left-[46%] top-[32%] text-center">
                                <span class="w-10 h-10 rounded-xl bg-cyan-500/20 border border-cyan-400/40 text-cyan-300 flex items-center justify-center mx-auto"><i class="fa-solid fa-box-archive"></i></span>
                                <p class="text-[10px] font-bold text-white mt-1">ODP-A1</p>
                                <p class="text-[9px] text-emerald-400 font-mono">4/8 port</p>
                            </div>
                            <div class="absolute left-[72%] top-[12%] text-center">
                                <span class="w-9 h-9 rounded-xl bg-indigo-500/20 border border-indigo-400/40 text-indigo-300 flex items-center justify-center mx-auto text-sm"><i class="fa-solid fa-box-archive"></i></span>
                                <p class="text-[10px] font-bold text-white mt-1">ODP-B2</p>
                            </div>
                            <div class="absolute left-[82%] top-[50%] text-center">
                                <span class="w-9 h-9 rounded-full bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 flex items-center justify-center mx-auto text-sm"><i class="fa-solid fa-house-signal"></i></span>
                                <p class="text-[10px] font-bold text-white mt-1">Budi S.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Billing --}}
                    <div id="preview-billing" class="tab-preview-content space-y-4 hidden">
                        <div class="flex justify-between items-center gap-3 flex-wrap">
                            <h3 class="font-extrabold text-slate-900">Tagihan otomatis & struk</h3>
                            <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full"><i class="fa-brands fa-whatsapp mr-1"></i>WhatsApp terhubung</span>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="rounded-2xl bg-[#e7f6ee] border border-emerald-100 p-5 space-y-3">
                                <p class="text-xs font-bold text-emerald-700"><i class="fa-brands fa-whatsapp"></i> Pesan terkirim otomatis</p>
                                <p class="text-sm text-slate-700 bg-white rounded-2xl rounded-tl-none p-4 shadow-sm leading-relaxed">Halo Kak Budi, tagihan internet bulan ini <b>Rp150.000</b> sudah terbit. Bayar online di link berikut sebelum tgl 20 ya 🙏<br><span class="text-indigo-600 font-semibold">namaisp.mikpay.link/bayar/…</span></p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-5 font-mono text-xs space-y-1.5">
                                <p class="text-center font-bold text-slate-900 border-b border-dashed border-slate-300 pb-2">STRUK PEMBAYARAN</p>
                                <div class="flex justify-between text-slate-600"><span>Pelanggan</span><span class="text-slate-900 font-bold">Budi Santoso</span></div>
                                <div class="flex justify-between text-slate-600"><span>Paket</span><span class="text-slate-900 font-bold">Home 20 Mbps</span></div>
                                <div class="flex justify-between text-slate-600"><span>Periode</span><span class="text-slate-900 font-bold">Oktober 2026</span></div>
                                <div class="flex justify-between text-emerald-600 font-bold pt-2 border-t border-dashed border-slate-300"><span>TOTAL</span><span>Rp150.000 LUNAS</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- OLT --}}
                    <div id="preview-olt" class="tab-preview-content space-y-4 hidden">
                        <div class="flex justify-between items-center gap-3 flex-wrap">
                            <h3 class="font-extrabold text-slate-900">Monitoring OLT & akses Web OLT</h3>
                            <span class="text-[11px] font-bold text-cyan-700 bg-cyan-50 border border-cyan-100 px-2.5 py-1 rounded-full"><i class="fa-solid fa-shield-halved mr-1"></i>via tunnel remote</span>
                        </div>
                        <div class="grid sm:grid-cols-3 gap-3">
                            @foreach ([['ONU Online', '186', 'emerald'], ['ONU Offline', '7', 'rose'], ['Rata-rata Rx', '-19.4 dBm', 'indigo']] as $s)
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-[11px] font-semibold text-slate-500">{{ $s[0] }}</p>
                                    <p class="text-2xl font-extrabold text-{{ $s[2] }}-600">{{ $s[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100">
                            @foreach ([['OLT HSGQ - Blok A', 'Online', 'emerald'], ['OLT HIOSO - Blok B', 'Online', 'emerald'], ['OLT ZTE - Kantor', 'Offline', 'rose']] as $o)
                                <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs"><i class="fa-solid fa-server"></i></span>
                                        <span class="font-bold text-slate-800">{{ $o[0] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-{{ $o[2] }}-50 text-{{ $o[2] }}-700 border border-{{ $o[2] }}-200">{{ $o[1] }}</span>
                                        <button type="button" onclick="showToast('Web OLT dibuka lewat tunnel remote')" class="hidden sm:inline-flex text-[11px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg">Buka Web OLT</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Voucher --}}
                    <div id="preview-voucher" class="tab-preview-content space-y-4 hidden">
                        <div class="flex justify-between items-center gap-3 flex-wrap">
                            <h3 class="font-extrabold text-slate-900">Voucher hotspot siap cetak</h3>
                            <span class="text-[11px] font-bold text-violet-700 bg-violet-50 border border-violet-100 px-2.5 py-1 rounded-full">Sinkron ke MikroTik</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach ([['MP-88A2K', 'Rp5.000', '24 Jam'], ['MP-99B3L', 'Rp10.000', '3 Hari'], ['MP-12X9Z', 'Rp50.000', '30 Hari'], ['MP-77K1W', 'Rp5.000', '24 Jam']] as $v)
                                <div class="rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/40 p-4 text-center">
                                    <p class="text-[9px] font-extrabold tracking-widest text-indigo-600">VOUCHER WIFI</p>
                                    <p class="mt-2 font-mono font-extrabold text-slate-900 bg-white rounded-lg py-1.5 border border-slate-200">{{ $v[0] }}</p>
                                    <p class="mt-2 text-sm font-extrabold text-slate-800">{{ $v[1] }}</p>
                                    <p class="text-[10px] text-slate-500 font-semibold">{{ $v[2] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
