@extends('layouts.app')

@section('title', 'Verifikasi Email — MikPay')

@php
    $isPartner = $user->isPartner();
    $hours = \App\Notifications\VerifyEmailNotification::EXPIRE_HOURS;
    $domain = strtolower(substr(strrchr($user->email, '@') ?: '', 1));
    $inbox = match (true) {
        in_array($domain, ['gmail.com', 'googlemail.com'], true) => ['https://mail.google.com/mail/u/0/#search/from%3Amikpay', 'Buka Gmail'],
        in_array($domain, ['yahoo.com', 'yahoo.co.id', 'ymail.com'], true) => ['https://mail.yahoo.com', 'Buka Yahoo Mail'],
        in_array($domain, ['outlook.com', 'hotmail.com', 'live.com', 'msn.com'], true) => ['https://outlook.live.com/mail', 'Buka Outlook'],
        default => null,
    };
    $showChange = $errors->has('email') || $errors->has('current_password');
@endphp

@section('content')
<section class="min-h-screen flex items-center justify-center px-4 py-16 bg-gradient-to-b from-indigo-50/70 via-slate-50 to-white">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-3">
                @include('components.logo')
            </a>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-xl shadow-indigo-900/5 p-6 sm:p-8">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl mb-4">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <h1 class="text-center text-lg font-extrabold text-slate-900">Cek email Anda</h1>
            <p class="mt-2 text-center text-xs text-slate-600 leading-relaxed">
                Kami mengirim tautan verifikasi ke<br>
                <span class="font-bold text-slate-900 text-sm break-all">{{ $user->email }}</span>
            </p>

            @if (session('success'))
                <div class="mt-5 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700 font-semibold">
                    <i class="fa-solid fa-circle-check mt-0.5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mt-5 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 font-semibold">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div class="mt-5 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-semibold">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <ol class="mt-5 space-y-2 text-xs text-slate-600">
                <li class="flex gap-2"><span class="w-5 h-5 shrink-0 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center">1</span>Buka inbox email di atas (cek juga folder <b>Spam / Promosi</b>).</li>
                <li class="flex gap-2"><span class="w-5 h-5 shrink-0 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center">2</span>Klik tombol <b>“Verifikasi Email Saya”</b> (berlaku {{ $hours }} jam).</li>
                <li class="flex gap-2"><span class="w-5 h-5 shrink-0 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center">3</span>
                    @if ($isPartner)
                        Akun affiliate Anda masuk antrean peninjauan tim MikPay.
                    @else
                        Panel, trial {{ (int) config('mikpay.trial_days', 3) }} hari &amp; OpenVPN langsung aktif.
                    @endif
                </li>
            </ol>

            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-[11px] text-amber-800 leading-relaxed">
                <i class="fa-solid fa-lock mr-1"></i>
                Selama email belum diverifikasi, {{ $isPartner ? 'panel affiliate' : 'panel, trial & koneksi OpenVPN' }} belum bisa dipakai.
            </div>

            @if ($inbox)
                <a href="{{ $inbox[0] }}" target="_blank" rel="noopener" class="mt-5 w-full inline-flex items-center justify-center gap-2 gradient-bg text-white text-sm font-bold py-3 rounded-xl shadow-lg shadow-indigo-500/20 hover:opacity-95 transition">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> {{ $inbox[1] }}
                </a>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="mt-3" id="resendForm">
                @csrf
                <button type="submit" id="resendBtn" class="w-full inline-flex items-center justify-center gap-2 text-sm font-bold py-3 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-paper-plane"></i> <span id="resendLabel">Kirim ulang email verifikasi</span>
                </button>
            </form>

            <div class="mt-5 border-t border-slate-100 pt-4">
                <button type="button" id="toggleChangeEmail" class="text-xs font-semibold text-indigo-600 hover:underline">
                    Salah ketik email? Ubah email
                </button>
                <form method="POST" action="{{ route('verification.change-email') }}" id="changeEmailForm" class="mt-3 space-y-3 {{ $showChange ? '' : 'hidden' }}">
                    @csrf
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email baru</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition"
                            placeholder="nama@bisnisanda.com">
                    </div>
                    <x-password-input id="current_password" name="current_password" label="Password akun" :required="true" autocomplete="current-password" placeholder="Password yang dipakai saat daftar" />
                    <button type="submit" class="w-full text-sm font-bold py-2.5 rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition">
                        Simpan &amp; kirim tautan baru
                    </button>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="text-center mt-6">
            @csrf
            <button type="submit" class="text-xs text-slate-500 hover:text-indigo-600 font-semibold transition">
                <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Keluar
            </button>
        </form>
    </div>
</section>

<script>
(function () {
    const toggle = document.getElementById('toggleChangeEmail');
    const changeForm = document.getElementById('changeEmailForm');
    toggle?.addEventListener('click', () => changeForm?.classList.toggle('hidden'));

    const btn = document.getElementById('resendBtn');
    const label = document.getElementById('resendLabel');
    const form = document.getElementById('resendForm');
    const KEY = 'mikpay_verify_resend_at';
    const COOLDOWN = 60;
    @if (session('success'))
        try { sessionStorage.setItem(KEY, String(Date.now())); } catch (e) {}
    @endif

    const tick = () => {
        let last = 0;
        try { last = parseInt(sessionStorage.getItem(KEY) || '0', 10); } catch (e) {}
        const left = COOLDOWN - Math.floor((Date.now() - last) / 1000);
        if (left > 0) {
            btn.disabled = true;
            label.textContent = 'Kirim ulang dalam ' + left + ' detik';
            setTimeout(tick, 1000);
        } else {
            btn.disabled = false;
            label.textContent = 'Kirim ulang email verifikasi';
        }
    };
    form?.addEventListener('submit', () => { btn.disabled = true; label.textContent = 'Mengirim…'; });
    tick();
})();
</script>
@endsection
