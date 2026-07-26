@extends('layouts.app')

@section('title', 'Daftar — MikPay')

@section('content')
@php
    $tenantBase = $tenantBase ?? 'mikpay.link';
    $accountSuffix = $accountSuffix ?? 'tunnel.mikpay.link';
@endphp
<section class="min-h-screen flex items-center justify-center px-4 py-16 bg-gradient-to-b from-indigo-50/70 via-slate-50 to-white">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-3">
                @include('components.logo')
            </a>
            <p class="mt-4 text-sm text-slate-600 font-medium">Daftar trial 14 hari — bonus OpenVPN remote gratis</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-xl shadow-indigo-900/5 p-6 sm:p-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-medium space-y-1">
                    @foreach ($errors->all() as $err)
                        <p>{{ $err }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">Nama usaha / ISP</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="120"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition"
                           placeholder="Contoh: Annet Wifi" autofocus>
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email login</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition"
                           placeholder="nama@bisnisanda.com" autocomplete="email">
                </div>

                <div>
                    <label for="subdomain" class="block text-xs font-bold text-slate-700 mb-1.5">Subdomain panel</label>
                    <div class="flex rounded-xl border border-slate-300 bg-slate-50 overflow-hidden focus-within:border-indigo-500 focus-within:bg-white">
                        <input id="subdomain" type="text" name="subdomain" value="{{ old('subdomain') }}" required
                               pattern="[a-z0-9]([a-z0-9-]{1,28}[a-z0-9])?" maxlength="30"
                               class="flex-1 min-w-0 bg-transparent px-4 py-2.5 text-sm font-mono focus:outline-none lowercase"
                               placeholder="annetwifi"
                               autocomplete="off"
                               oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9-]/g,'').replace(/^-+/,'').replace(/-+$/,'')">
                        <span class="shrink-0 px-3 py-2.5 text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border-l border-slate-200 flex items-center">
                            .{{ $tenantBase }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Panel: <span class="font-mono font-bold text-indigo-700" id="panelPreview">….{{ $tenantBase }}</span><br>
                        Username VPN bonus: <span class="font-mono font-bold text-violet-700" id="vpnPreview">…&#64;{{ $accountSuffix }}</span><br>
                        <span class="text-amber-700 font-semibold">Permanen — tidak bisa diganti setelah daftar.</span>
                    </p>
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 mb-1.5">WhatsApp <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition"
                           placeholder="08xxxxxxxxxx">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                        <input id="password" type="password" name="password" required minlength="8"
                               class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:bg-white"
                               autocomplete="new-password" placeholder="Min. 8 karakter">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">Ulangi password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8"
                               class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:bg-white"
                               autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="w-full gradient-bg text-white text-sm font-bold py-3 rounded-xl shadow-lg shadow-indigo-500/20 hover:opacity-95 transition">
                    Daftar &amp; mulai trial 14 hari
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6 space-y-2">
            <span class="block">Sudah punya akun?
                <a href="{{ route('login') }}" class="text-indigo-600 font-bold hover:underline">Masuk</a>
            </span>
            <a href="{{ route('home') }}" class="hover:text-indigo-600 font-semibold transition">← Kembali ke beranda</a>
        </p>
    </div>
</section>

<script>
(function () {
    const input = document.getElementById('subdomain');
    const panel = document.getElementById('panelPreview');
    const vpn = document.getElementById('vpnPreview');
    const base = @js('.'.$tenantBase);
    const suffix = @js('@'.$accountSuffix);
    if (!input || !panel || !vpn) return;
    const sync = () => {
        let v = (input.value || '').toLowerCase().replace(/[^a-z0-9-]/g, '');
        const vpnBase = v.replace(/[^a-z0-9]/g, '') || '…';
        panel.textContent = (v || '…') + base;
        vpn.textContent = vpnBase + suffix;
    };
    input.addEventListener('input', sync);
    sync();
})();
</script>
@endsection
