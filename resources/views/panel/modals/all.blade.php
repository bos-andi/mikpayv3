<div id="thermalModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden">
        <div class="bg-white text-slate-900 w-80 p-5 rounded-2xl shadow-2xl font-mono text-xs space-y-3">
            <div class="text-center border-b border-dashed border-slate-400 pb-3">
                <h3 class="font-extrabold text-base uppercase">MIKPAY NET</h3>
                <p class="text-[10px] text-slate-600">Layanan Internet Cepat & Unlimited</p>
                <p class="text-[9px] text-slate-500">WA: 0812-9999-8888</p>
            </div>
            <div class="space-y-1 text-[11px]">
                <div class="flex justify-between"><span>No. Struk:</span><span id="stNo">INV-2026-001</span></div>
                <div class="flex justify-between"><span>Pelanggan:</span><span id="stName">Budi Santoso</span></div>
                <div class="flex justify-between"><span>Paket:</span><span id="stPaket">Home 20 Mbps</span></div>
                <div class="flex justify-between"><span>Tgl Lunas:</span><span>22 Jul 2026</span></div>
            </div>
            <div class="border-t border-b border-dashed border-slate-400 py-2 flex justify-between font-bold text-sm">
                <span>TOTAL BAYAR</span>
                <span id="stTotal">Rp 200.000</span>
            </div>
            <p class="text-center text-[10px] text-slate-600">Terima kasih atas pembayaran Anda!</p>
            <div class="flex gap-2 pt-2">
                <button onclick="closeThermalModal()" class="flex-1 bg-slate-200 hover:bg-slate-300 py-2 rounded-xl text-slate-800 font-bold transition">Tutup</button>
                <button onclick="showToast('Mengirim perintah ke Printer Thermal Bluetooth...')" class="flex-1 bg-indigo-600 hover:bg-indigo-500 py-2 rounded-xl text-white font-bold transition">Cetak</button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: REGISTRASI ONU BARU -->

<div id="onuModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden">
        <div class="bg-slate-900 border border-slate-800 text-slate-200 w-full max-w-md p-6 rounded-2xl shadow-2xl space-y-4">
            <h3 class="font-bold text-base text-white">Registrasi ONU/Modem Baru</h3>
            <div class="space-y-3 text-xs">
                <div>
                    <label class="text-slate-400 block mb-1">Serial Number ONU</label>
                    <input type="text" value="ZTEG-C9812A4" readonly class="w-full bg-slate-950 p-2 rounded-xl border border-slate-800 font-mono text-amber-400">
                </div>
                <div>
                    <label class="text-slate-400 block mb-1">Hubungkan ke Pelanggan</label>
                    <select class="w-full bg-slate-950 p-2 rounded-xl border border-slate-800 text-slate-200">
                        <option>Pilih Pelanggan...</option>
                        <option>Budi Santoso (ODP-BLOK-A1)</option>
                        <option>Asep Saepudin (ODP-BLOK-A1)</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <button onclick="closeOnuModal()" class="flex-1 bg-slate-800 py-2 rounded-xl font-semibold">Batal</button>
                <button onclick="closeOnuModal(); showToast('ONU Berhasil Didaftarkan dan Disinkronkan!')" class="flex-1 bg-amber-500 text-slate-950 font-bold py-2 rounded-xl">Simpan & Bind</button>
            </div>
        </div>
    </div>

    <!-- MODAL 3: WEBVIEW REMOTE OLT DIRECT -->

<div id="oltWebviewModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-900 border border-slate-800 w-full max-w-4xl h-[85vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden">
            <!-- Modal Header -->
            <div class="p-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                    <h3 class="font-bold text-sm text-white">Web Management OLT HSGQ (Tunnel: {{ $tenant?->remoteHost() ?? '—' }}:28080)</h3>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-mono bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-bold">HTTP PROXY ACTIVE</span>
                    <button onclick="closeOltWebview()" class="text-slate-400 hover:text-white p-1 text-base ml-2"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <!-- Modal Webview Body Simulation -->
            <div class="flex-1 bg-slate-950 p-6 flex flex-col items-center justify-center text-center space-y-4">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-2xl border border-amber-500/30">
                    <i class="fa-solid fa-server"></i>
                </div>
                <div>
                    <h4 class="font-extrabold text-lg text-white">Simulasi Antarmuka Web OLT (EPON / GPON)</h4>
                    <p class="text-xs text-slate-400 max-w-md mx-auto mt-1">Sistem berhasil membuat terowongan HTTP Proxy aman langsung dari Cloud VPN Server MikPay ke IP Lokal OLT (192.168.100.1).</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 font-mono text-xs text-left w-full max-w-md text-slate-300 space-y-1">
                    <p><span class="text-indigo-400">Target IP:</span> 192.168.100.1:80</p>
                    <p><span class="text-indigo-400">Status Gateway:</span> 200 OK (Response: 12ms)</p>
                    <p><span class="text-indigo-400">Auth Token:</span> MikPay-Secure-Tunnel-Pass</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="showToast('Membuka Web OLT di tab baru...'); closeOltWebview()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs px-5 py-2.5 rounded-xl transition">
                        <i class="fa-solid fa-external-link mr-1"></i> Buka Di Tab Browser Baru
                    </button>
                    <button onclick="closeOltWebview()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs px-5 py-2.5 rounded-xl transition">
                        Tutup Webview
                    </button>
                </div>
            </div>
        </div>
    </div>

{{-- Modal konfirmasi custom (ganti window.confirm) --}}
<div id="mikpayConfirmModal" class="fixed inset-0 z-[6000] hidden items-center justify-center p-4">
    <div id="mikpayConfirmBackdrop" class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]"></div>
    <div class="relative w-full max-w-sm bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 pt-5 pb-3 flex items-start gap-3">
            <div id="mikpayConfirmIconWrap" class="h-10 w-10 rounded-2xl bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
                <i id="mikpayConfirmIcon" class="fa-solid fa-circle-question text-lg"></i>
            </div>
            <div class="min-w-0 pt-0.5">
                <p id="mikpayConfirmTitle" class="text-sm font-extrabold text-slate-900">Konfirmasi</p>
                <p id="mikpayConfirmMessage" class="text-[12px] text-slate-500 mt-1 leading-relaxed"></p>
            </div>
        </div>
        <div class="px-5 pb-5 pt-2 flex gap-2">
            <button type="button" id="mikpayConfirmCancel" class="flex-1 px-3 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                Batal
            </button>
            <button type="button" id="mikpayConfirmOk" class="flex-1 px-3 py-2.5 rounded-2xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold shadow-md shadow-violet-600/20 transition">
                Ya, lanjut
            </button>
        </div>
    </div>
</div>
