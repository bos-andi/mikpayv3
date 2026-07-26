<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MikPay Tunnel — Remote OpenVPN MikroTik</title>
    @include('partials.head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased">
    <header class="bg-gradient-to-r from-violet-700 via-violet-600 to-fuchsia-600 text-white">
        <div class="max-w-5xl mx-auto px-4 py-5 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset(config('mikpay.brand.logo', 'logo-mikpay.png')) }}?v=2" alt="MikPay" class="h-10 w-10 object-contain">
                <div>
                    <p class="font-extrabold tracking-tight">MikPay Tunnel</p>
                    <p class="text-[10px] text-violet-100/80 uppercase tracking-wider">Remote OpenVPN untuk MikroTik</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs font-bold">
                <a href="{{ url('/login') }}" class="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition">Masuk</a>
                <a href="{{ route('tunnel.register') }}" class="px-3 py-2 rounded-xl bg-white text-violet-700 hover:bg-violet-50 transition">Daftar</a>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-12 space-y-10">
        <section class="space-y-4 max-w-2xl">
            <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-slate-900">
                Remote MikroTik dari mana saja via <span class="text-violet-600">OpenVPN</span>
            </h1>
            <p class="text-sm text-slate-600 leading-relaxed">
                Cocok untuk RouterOS v6 &amp; v7. Akun VPN:
                <span class="font-mono font-semibold text-violet-700">nama@tunnel.mikpay.link</span>,
                remote via node
                <span class="font-mono font-semibold text-violet-700">{{ $defaultNodeHost ?? 'an-1.mikpay.link' }}:PORT</span>.
                Pelanggan <strong>MikPay</strong> mendapat 1 slot gratis.
            </p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tunnel.register') }}" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold px-5 py-3 rounded-2xl shadow-lg shadow-violet-600/20">
                    Daftar Tunnel Baru
                </a>
                <a href="{{ url('/login') }}" class="inline-flex items-center gap-2 border border-slate-200 bg-white text-slate-700 text-sm font-bold px-5 py-3 rounded-2xl">
                    Sudah punya akun? Masuk
                </a>
            </div>
        </section>

        <section class="grid sm:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold text-violet-600 uppercase">Gratis</p>
                <h2 class="text-lg font-extrabold mt-1">Bundle MikPay</h2>
                <p class="text-[12px] text-slate-500 mt-2">1 akun OpenVPN otomatis saat langganan MikPay aktif.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold text-violet-600 uppercase">Berbayar</p>
                <h2 class="text-lg font-extrabold mt-1">Tunnel Standalone</h2>
                <p class="text-[12px] text-slate-500 mt-2">Langganan remote VPN saja tanpa panel billing penuh.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold text-violet-600 uppercase">Kompatibel</p>
                <h2 class="text-lg font-extrabold mt-1">ROS 6 &amp; 7</h2>
                <p class="text-[12px] text-slate-500 mt-2">Script ovpn-client mode=ip TCP — copy-paste ke Winbox.</p>
            </div>
        </section>
    </main>

    <footer class="text-center text-[10px] text-slate-400 py-8">
        Powered by {{ config('mikpay.brand.domain', 'mikpay.link') }}
    </footer>
</body>
</html>
