<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar MikPay Tunnel</title>
    @include('partials.head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased">
    <header class="bg-gradient-to-r from-violet-700 via-violet-600 to-fuchsia-600 text-white">
        <div class="max-w-lg mx-auto px-4 py-5 flex items-center justify-between gap-3">
            <a href="{{ route('tunnel.home') }}" class="flex items-center gap-3">
                <img src="{{ asset(config('mikpay.brand.logo', 'logo-mikpay.png')) }}?v=2" alt="MikPay" class="h-9 w-9 object-contain">
                <div>
                    <p class="text-sm font-extrabold">MikPay Tunnel</p>
                    <p class="text-[10px] text-violet-100/80">Pendaftaran pelanggan baru</p>
                </div>
            </a>
            <a href="{{ url('/login') }}" class="text-xs font-bold px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20">Masuk</a>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-slate-900">Buat akun Tunnel</h1>
            <p class="text-xs text-slate-500 mt-1">
                Setelah daftar, username VPN dan script OpenVPN langsung tersedia di dashboard.
                Node: <span class="font-mono font-semibold text-violet-700">{{ $defaultNodeHost }}</span>
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-medium space-y-1">
                @foreach ($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('tunnel.register.store') }}" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 sm:p-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">Nama / Nama usaha</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="120"
                       class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500 focus:bg-white"
                       placeholder="Contoh: Annet Wifi">
            </div>

            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email login</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500 focus:bg-white"
                       placeholder="nama@email.com" autocomplete="email">
            </div>

            <div>
                <label for="username" class="block text-xs font-bold text-slate-700 mb-1.5">Username VPN</label>
                <div class="flex rounded-xl border border-slate-300 bg-slate-50 overflow-hidden focus-within:border-violet-500 focus-within:bg-white">
                    <input id="username" type="text" name="username" value="{{ old('username') }}" required
                           pattern="[a-z0-9]{3,30}" maxlength="30"
                           class="flex-1 min-w-0 bg-transparent px-4 py-2.5 text-sm font-mono focus:outline-none lowercase"
                           placeholder="annetwifi"
                           autocomplete="off"
                           oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9]/g,'')">
                    <span class="shrink-0 px-3 py-2.5 text-xs font-mono font-bold text-violet-700 bg-violet-50 border-l border-slate-200 flex items-center">
                        &#64;{{ $accountSuffix }}
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1.5">
                    Akan menjadi
                    <span class="font-mono font-bold text-violet-700" id="vpnPreview">{{ old('username') ? old('username').'@'.$accountSuffix : '…@'.$accountSuffix }}</span>
                    — dipakai login OpenVPN di MikroTik.
                </p>
            </div>

            <div>
                <label for="phone" class="block text-xs font-bold text-slate-700 mb-1.5">WhatsApp <span class="font-normal text-slate-400">(opsional)</span></label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                       class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500 focus:bg-white"
                       placeholder="08xxxxxxxxxx">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                    <input id="password" type="password" name="password" required minlength="8"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500 focus:bg-white"
                           autocomplete="new-password" placeholder="Min. 8 karakter">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">Ulangi password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500 focus:bg-white"
                           autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="w-full bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold py-3 rounded-xl shadow-lg shadow-violet-600/20 transition">
                Daftar &amp; buat akun OpenVPN
            </button>

            <p class="text-[11px] text-center text-slate-500">
                Sudah punya akun?
                <a href="{{ url('/login') }}" class="font-bold text-violet-600 hover:underline">Masuk</a>
            </p>
        </form>
    </main>

    <script>
    (function () {
        const input = document.getElementById('username');
        const preview = document.getElementById('vpnPreview');
        const suffix = @js('@'.$accountSuffix);
        if (!input || !preview) return;
        const sync = () => {
            const v = (input.value || '').toLowerCase().replace(/[^a-z0-9]/g, '');
            preview.textContent = (v || '…') + suffix;
        };
        input.addEventListener('input', sync);
        sync();
    })();
    </script>
</body>
</html>
