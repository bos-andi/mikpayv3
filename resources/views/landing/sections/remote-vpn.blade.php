@php
    $remotes = [
        ['fa-window-maximize', 'Remote Winbox', 'Login Winbox ke MikroTik dari mana saja.', 'an-1.mikpay.link:1041'],
        ['fa-code', 'Remote API MikroTik', 'Panel MikPay & aplikasi lain tetap terhubung ke router.', 'an-1.mikpay.link:1042'],
        ['fa-tower-cell', 'Remote Web OLT', 'Buka web management OLT (HSGQ, HIOSO, ZTE, dll).', 'an-1.mikpay.link:1043'],
        ['fa-house-signal', 'Remote Web Modem', 'Akses halaman web router / ONU pelanggan.', 'an-1.mikpay.link:1044'],
    ];
@endphp

<section id="remote-vpn" class="relative py-20 lg:py-28 bg-slate-950 text-white overflow-hidden scroll-mt-20">
    <div class="absolute inset-0 bg-grid-dark"></div>
    <div class="glow-blob w-[500px] h-[500px] bg-indigo-600/30 -top-40 -left-32"></div>
    <div class="glow-blob w-[420px] h-[420px] bg-cyan-500/20 bottom-0 -right-24"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-14 items-center">
            <div class="reveal">
                <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-cyan-300 bg-cyan-400/10 border border-cyan-400/20 px-3 py-1 rounded-full">
                    <i class="fa-solid fa-bolt"></i> Gratis di setiap paket
                </span>
                <h2 class="mt-5 text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight">
                    Remote MikroTik & OLT <span class="bg-gradient-to-r from-indigo-300 to-cyan-300 bg-clip-text text-transparent">tanpa IP Public.</span>
                </h2>
                <p class="mt-5 text-slate-300 leading-relaxed">
                    Setiap akun MikPay mendapat 1 akun VPN remote berbasis <strong class="text-white">OpenVPN</strong>. Router Anda tersambung ke server MikPay, lalu Winbox, API, Web OLT, dan modem pelanggan bisa dibuka lewat alamat khusus milik Anda.
                </p>

                <div class="mt-8 grid sm:grid-cols-2 gap-3">
                    @foreach ($remotes as $r)
                        <div class="rounded-2xl bg-white/[0.04] border border-white/10 p-4 hover:bg-white/[0.07] hover:border-cyan-400/30 transition">
                            <div class="flex items-center gap-3">
                                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center text-sm shadow-lg shadow-indigo-500/30"><i class="fa-solid {{ $r[0] }}"></i></span>
                                <p class="font-extrabold text-sm">{{ $r[1] }}</p>
                                <i class="fa-solid fa-circle-check text-emerald-400 ml-auto"></i>
                            </div>
                            <p class="mt-2.5 text-xs text-slate-400 leading-relaxed">{{ $r[2] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-xs text-slate-400 font-semibold">
                    <span><i class="fa-solid fa-check text-cyan-300 mr-1.5"></i>RouterOS v6 & v7</span>
                    <span><i class="fa-solid fa-check text-cyan-300 mr-1.5"></i>Cukup copy-paste 1 script</span>
                    <span><i class="fa-solid fa-check text-cyan-300 mr-1.5"></i>Tanpa biaya tambahan</span>
                </div>
            </div>

            <div class="reveal relative">
                <div class="absolute -inset-3 rounded-3xl bg-gradient-to-tr from-indigo-500/30 to-cyan-400/20 blur-2xl"></div>
                <div class="relative rounded-2xl border border-white/10 bg-slate-900/90 backdrop-blur shadow-2xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-white/10 bg-white/[0.03]">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <span class="ml-2 text-[11px] font-mono text-slate-400">Winbox › New Terminal</span>
                        </div>
                        <span class="text-[10px] font-mono font-bold text-cyan-300 bg-cyan-400/10 border border-cyan-400/20 px-2 py-0.5 rounded">OPENVPN</span>
                    </div>
<pre class="p-5 text-[11px] sm:text-xs font-mono leading-relaxed text-slate-300 overflow-x-auto"><span class="text-slate-500"># Script dibuat otomatis dari panel MikPay</span>
<span class="text-cyan-300">/interface ovpn-client</span> add name=<span class="text-amber-300">"ovpn-mikpay"</span> \
    connect-to=<span class="text-emerald-300">an-1.mikpay.link</span> port=1195 \
    mode=ip protocol=tcp user=<span class="text-emerald-300">"namaisp@mikpay"</span> \
    password=<span class="text-emerald-300">"••••••••"</span> cipher=blowfish128
<span class="text-cyan-300">/ip service</span> set winbox disabled=no
<span class="text-cyan-300">/ip service</span> set api disabled=no

<span class="text-emerald-400">✓ ovpn-mikpay: connected</span></pre>
                    <div class="border-t border-white/10 divide-y divide-white/5">
                        @foreach ($remotes as $r)
                            <div class="flex items-center justify-between px-5 py-2.5 text-xs">
                                <span class="text-slate-400 font-semibold">{{ $r[1] }}</span>
                                <span class="font-mono text-white">{{ $r[3] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
