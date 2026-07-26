<section id="remote-vpn" class="py-20 bg-slate-100/70 border-t border-slate-200 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-100 border border-indigo-200 text-indigo-700 text-xs font-bold">
                    <i class="fa-solid fa-bolt"></i> DEDICATED TUNNEL SERVER
                </div>
                <h3 class="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                    Akses Winbox & Web OLT Dari Mana Saja. <span class="gradient-text">Gratis Selamanya!</span>
                </h3>
                <p class="text-sm text-slate-600 leading-relaxed font-medium">
                    Tidak perlu beli IP Public Statis yang mahal atau sewa VPN pihak ketiga. MikPay memberikan layanan VPN Remote Dedicated berbasis <strong>WireGuard & SSTP</strong> secara gratis untuk setiap akun terdaftar.
                </p>

                <div class="space-y-3 text-xs sm:text-sm text-slate-700">
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm">
                        <i class="fa-solid fa-terminal text-indigo-600 text-base"></i>
                        <span><strong class="text-slate-900">1-Click Script Generator:</strong> Tinggal copy-paste script ke Terminal Winbox MikroTik.</span>
                    </div>
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm">
                        <i class="fa-solid fa-network-wired text-amber-600 text-base"></i>
                        <span><strong class="text-slate-900">Webview OLT Direct:</strong> Buka antarmuka Web OLT HSGQ/HIOSO/ZTE langsung dari browser HP/Laptop.</span>
                    </div>
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm">
                        <i class="fa-solid fa-gauge-high text-emerald-600 text-base"></i>
                        <span><strong class="text-slate-900">Low Latency Server:</strong> Node Jakarta Datacenter (CGK-DC1) dengan latensi ultra rendah (&lt; 5ms).</span>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-terminal text-indigo-400 text-sm"></i>
                        <span class="text-xs font-mono font-bold text-white">MikroTik RouterOS v7 Auto-Script</span>
                    </div>
                    <span class="bg-indigo-500/20 text-indigo-300 text-[10px] px-2 py-0.5 rounded font-mono font-bold">WIREGUARD</span>
                </div>
                <pre class="bg-slate-950 p-4 rounded-xl text-emerald-400 font-mono text-xs overflow-x-auto leading-relaxed border border-slate-800">/interface wireguard add listen-port=13231 name=mikpay-vpn private-key="uP83sX=..."
/interface wireguard peers add interface=mikpay-vpn public-key="K8a2d..." \
   endpoint-address="103.178.14.22" endpoint-port=51820 \
   allowed-address=10.100.0.0/16 persistent-keepalive=25s
/ip address add address=10.100.45.2/24 interface=mikpay-vpn</pre>
                <div class="p-3 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-xs text-indigo-300 flex items-center justify-between gap-3 flex-wrap">
                    <span>Domain Dedicated Anda: <strong class="font-mono text-white">cl45.remote.mikpay.link</strong></span>
                    <button type="button" onclick="showToast('Skrip VPN berhasil disalin!')" class="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1 rounded-lg font-bold text-[11px] transition shadow-md">
                        Copy Script
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
