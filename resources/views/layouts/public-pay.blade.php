<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bayar Tagihan') — {{ $tenant->name ?? config('mikpay.brand.name') }}</title>
    @include('partials.head')
    <style>
        .pay-bg {
            background:
                radial-gradient(1200px 500px at 10% -10%, rgba(124, 58, 237, 0.18), transparent 55%),
                radial-gradient(900px 420px at 100% 0%, rgba(249, 115, 22, 0.14), transparent 50%),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 45%, #f8fafc 100%);
        }
    </style>
</head>
<body class="min-h-screen pay-bg text-slate-800 font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="bg-gradient-to-r from-violet-700 via-violet-600 to-fuchsia-600 text-white shadow-lg shadow-violet-600/25">
            <div class="max-w-lg mx-auto px-4 py-4 flex items-center gap-3">
                <img src="{{ asset(config('mikpay.brand.logo', 'logo-mikpay.png')) }}?v=2" alt="MikPay" class="h-11 w-11 object-contain shrink-0 drop-shadow">
                <div class="min-w-0">
                    <p class="text-sm font-extrabold tracking-tight truncate">{{ $tenant->name ?? config('mikpay.brand.name') }}</p>
                    <p class="text-[10px] text-violet-100/80">@yield('subtitle', 'Pembayaran tagihan online')</p>
                </div>
            </div>
        </header>

        <main class="flex-1 max-w-lg w-full mx-auto px-4 py-6">
            @if (session('success'))
                <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700">
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>

        <footer class="text-center text-[10px] text-slate-400 py-5">
            Powered by {{ config('mikpay.brand.domain', 'mikpay.link') }}
            <span class="block mt-1.5 space-x-2">
                <a href="{{ central_url('/syarat-ketentuan') }}" target="_blank" class="hover:text-slate-600 underline-offset-2 hover:underline">Syarat &amp; Ketentuan</a>
                <span>·</span>
                <a href="{{ central_url('/kebijakan-privasi') }}" target="_blank" class="hover:text-slate-600 underline-offset-2 hover:underline">Privasi</a>
                <span>·</span>
                <a href="{{ central_url('/kebijakan-pengembalian-dana') }}" target="_blank" class="hover:text-slate-600 underline-offset-2 hover:underline">Pengembalian Dana</a>
            </span>
        </footer>
    </div>

    {{-- Modal konfirmasi custom MikPay --}}
    <div id="mikpayConfirmModal" class="fixed inset-0 z-[80] hidden items-center justify-center p-4">
        <div id="mikpayConfirmBackdrop" class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]"></div>
        <div class="relative w-full max-w-sm bg-white rounded-3xl shadow-2xl shadow-violet-900/20 border border-slate-200/80 overflow-hidden animate-[fadeIn_.15s_ease-out]">
            <div class="px-5 pt-5 pb-3 flex items-start gap-3">
                <div class="h-10 w-10 rounded-2xl bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-circle-question text-lg"></i>
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

    <div id="mikpayToast" class="fixed left-1/2 -translate-x-1/2 bottom-6 z-[90] hidden max-w-sm w-[calc(100%-2rem)]">
        <div class="rounded-2xl bg-slate-900 text-white text-[11px] font-semibold px-4 py-3 shadow-xl text-center"></div>
    </div>

    <script>
    window.mikpayConfirm = function (opts) {
        const options = typeof opts === 'string' ? { message: opts } : (opts || {});
        const modal = document.getElementById('mikpayConfirmModal');
        const titleEl = document.getElementById('mikpayConfirmTitle');
        const msgEl = document.getElementById('mikpayConfirmMessage');
        const okBtn = document.getElementById('mikpayConfirmOk');
        const cancelBtn = document.getElementById('mikpayConfirmCancel');
        const backdrop = document.getElementById('mikpayConfirmBackdrop');
        const alertMode = !!options.alert;

        if (!modal) return Promise.resolve(false);

        titleEl.textContent = options.title || (alertMode ? 'Pemberitahuan' : 'Konfirmasi');
        msgEl.textContent = options.message || 'Lanjutkan?';
        okBtn.textContent = options.confirmText || (alertMode ? 'Mengerti' : 'Ya, lanjut');
        cancelBtn.textContent = options.cancelText || 'Batal';
        cancelBtn.classList.toggle('hidden', alertMode);

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        return new Promise((resolve) => {
            const close = (value) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                okBtn.onclick = null;
                cancelBtn.onclick = null;
                backdrop.onclick = null;
                document.removeEventListener('keydown', onKey);
                resolve(value);
            };
            const onKey = (e) => {
                if (e.key === 'Escape') close(alertMode ? true : false);
                if (e.key === 'Enter') close(true);
            };
            okBtn.onclick = () => close(true);
            cancelBtn.onclick = () => close(false);
            backdrop.onclick = () => close(alertMode ? true : false);
            document.addEventListener('keydown', onKey);
        });
    };

    window.mikpayAlert = function (opts) {
        const options = typeof opts === 'string' ? { message: opts } : (opts || {});
        options.alert = true;
        return window.mikpayConfirm(options);
    };

    window.mikpayToast = function (message, ms) {
        const wrap = document.getElementById('mikpayToast');
        if (!wrap) return;
        const box = wrap.firstElementChild;
        if (box) box.textContent = message || '';
        wrap.classList.remove('hidden');
        clearTimeout(window.__mikpayToastTimer);
        window.__mikpayToastTimer = setTimeout(() => wrap.classList.add('hidden'), ms || 2200);
    };
    </script>
    @stack('scripts')
</body>
</html>
