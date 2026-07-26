<section id="peta-odp" class="py-20 bg-white border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-600 mb-2">Lihat Antarmuka MikPay</h2>
            <h3 class="text-2xl sm:text-4xl font-extrabold text-slate-900">Satu Sistem Untuk Semua Tim Anda.</h3>
        </div>

        <div class="flex items-center justify-center gap-2 overflow-x-auto pb-4 mb-8">
            <button type="button" onclick="switchPreviewTab('peta')" id="btn-tab-peta" class="tab-preview-btn px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-md transition shrink-0">
                <i class="fa-solid fa-map-location-dot mr-1.5"></i> Pemetaan ODP & GIS
            </button>
            <button type="button" onclick="switchPreviewTab('billing')" id="btn-tab-billing" class="tab-preview-btn px-5 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:text-slate-900 transition shrink-0">
                <i class="fa-solid fa-file-invoice-dollar mr-1.5"></i> Billing & Struk Thermal
            </button>
            <button type="button" onclick="switchPreviewTab('olt')" id="btn-tab-olt" class="tab-preview-btn px-5 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:text-slate-900 transition shrink-0">
                <i class="fa-solid fa-tower-cell mr-1.5"></i> Control Remote OLT
            </button>
            <button type="button" onclick="switchPreviewTab('voucher')" id="btn-tab-voucher" class="tab-preview-btn px-5 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:text-slate-900 transition shrink-0">
                <i class="fa-solid fa-ticket mr-1.5"></i> Hotspot Voucher
            </button>
        </div>

        <div class="p-6 rounded-2xl bg-white border border-slate-200/90 min-h-[320px] shadow-xl">
            <div id="preview-peta" class="tab-preview-content space-y-4">
                <div class="flex justify-between items-center">
                    <h4 class="font-bold text-sm text-slate-900">Visualisasi Pemetaan ODP & Kabel Fiber Optik</h4>
                    <span class="text-xs text-indigo-600 font-mono font-bold">12 Box ODP Active</span>
                </div>
                <div class="h-64 rounded-xl bg-slate-900 border border-slate-800 p-4 flex flex-col items-center justify-center relative overflow-hidden">
                    <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#6366f1_1px,transparent_1px)] [background-size:16px_16px]"></div>
                    <div class="z-10 flex items-center gap-8 flex-wrap justify-center">
                        <div class="text-center">
                            <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl border border-amber-500/40 mx-auto">
                                <i class="fa-solid fa-server"></i>
                            </div>
                            <p class="text-[10px] font-bold text-white mt-1">OLT Center</p>
                        </div>
                        <div class="w-20 h-0.5 border-t-2 border-dashed border-cyan-400 hidden sm:block"></div>
                        <div class="text-center">
                            <div class="w-10 h-10 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-lg border border-cyan-500/40 mx-auto">
                                <i class="fa-solid fa-box-archive"></i>
                            </div>
                            <p class="text-[10px] font-bold text-white mt-1">ODP-BLOK-A1</p>
                            <p class="text-[9px] text-emerald-400 font-mono">4/8 Port Active</p>
                        </div>
                        <div class="w-20 h-0.5 border-t-2 border-dashed border-emerald-400 hidden sm:block"></div>
                        <div class="text-center">
                            <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-sm border border-emerald-500/40 mx-auto">
                                <i class="fa-solid fa-house-signal"></i>
                            </div>
                            <p class="text-[10px] font-bold text-white mt-1">Budi Santoso</p>
                            <p class="text-[9px] text-emerald-400 font-mono">-17.8 dBm</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="preview-billing" class="tab-preview-content space-y-4 hidden">
                <div class="flex justify-between items-center">
                    <h4 class="font-bold text-sm text-slate-900">Invoice Auto-Blast WhatsApp & Struk Thermal Bluetooth</h4>
                    <span class="text-xs text-emerald-600 font-mono font-bold">WA Gateway Connected</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                        <p class="text-emerald-600 font-bold"><i class="fa-brands fa-whatsapp"></i> WhatsApp Notifikasi Terkirim:</p>
                        <p class="text-slate-700 italic bg-white p-3 rounded border border-slate-200 shadow-sm">"Halo Kak Budi, Tagihan Internet MikPay bulan ini sebesar Rp 200.000 sudah terbit. Silakan bayar sebelum tgl 20 agar koneksi tetap lancar."</p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs font-mono space-y-1">
                        <p class="text-center font-bold text-slate-900 border-b border-dashed border-slate-300 pb-1">STRUK MIKPAY NET</p>
                        <div class="flex justify-between text-slate-600"><span>Pelanggan:</span><span class="text-slate-900 font-bold">Budi Santoso</span></div>
                        <div class="flex justify-between text-slate-600"><span>Paket:</span><span class="text-slate-900 font-bold">Home 20 Mbps</span></div>
                        <div class="flex justify-between text-emerald-600 font-bold pt-1 border-t border-dashed border-slate-300"><span>TOTAL:</span><span>Rp 200.000 (LUNAS)</span></div>
                    </div>
                </div>
            </div>

            <div id="preview-olt" class="tab-preview-content space-y-4 hidden">
                <h4 class="font-bold text-sm text-slate-900">Remote Management OLT & TR-069 Modem Pelanggan</h4>
                <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 text-xs space-y-3">
                    <div class="flex justify-between items-center border-b border-slate-800 pb-2 gap-2 flex-wrap">
                        <span class="font-bold text-white">ZTE F660 ONU (Pelanggan: Budi Santoso)</span>
                        <span class="text-emerald-400 font-mono font-bold">-17.8 dBm (EXCELLENT)</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="showToast('Reboot Modem dikirim via TR-069')" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg text-[11px] font-semibold"><i class="fa-solid fa-power-off text-rose-400 mr-1"></i> Reboot Modem</button>
                        <button type="button" onclick="showToast('Sinyal Optik dipindai ulang')" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg text-[11px] font-semibold"><i class="fa-solid fa-wave-square text-cyan-400 mr-1"></i> Check Optical dBm</button>
                        <button type="button" onclick="showToast('Form Ganti Pass Wifi dibuka')" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg text-[11px] font-semibold"><i class="fa-solid fa-key text-amber-400 mr-1"></i> Ganti Password WiFi</button>
                    </div>
                </div>
            </div>

            <div id="preview-voucher" class="tab-preview-content space-y-4 hidden">
                <h4 class="font-bold text-sm text-slate-900">Hotspot Voucher Massal Ready-to-Print</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    @foreach ([
                        ['code' => 'MP-88A2K', 'price' => 'Rp 5.000 / 24 Jam'],
                        ['code' => 'MP-99B3L', 'price' => 'Rp 10.000 / 3 Hari'],
                        ['code' => 'MP-12X9Z', 'price' => 'Rp 50.000 / 30 Hari'],
                        ['code' => 'MP-77K1W', 'price' => 'Rp 5.000 / 24 Jam'],
                    ] as $voucher)
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-center space-y-1 shadow-sm">
                            <p class="text-[9px] font-bold text-indigo-600">MIKPAY VOUCHER</p>
                            <p class="font-mono font-extrabold text-slate-900 bg-white py-1 rounded border border-slate-200">{{ $voucher['code'] }}</p>
                            <p class="text-[10px] text-slate-500 font-semibold">{{ $voucher['price'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
