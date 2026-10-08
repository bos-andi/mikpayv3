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
            <p class="mt-4 text-sm text-slate-600 font-medium">Daftar trial {{ (int) config('mikpay.trial_days', 3) }} hari — bonus OpenVPN remote gratis</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-xl shadow-indigo-900/5 p-6 sm:p-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-medium space-y-1">
                    @foreach ($errors->all() as $err)
                        <p>{{ $err }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" id="registerForm" class="space-y-4">
                @csrf

                @php
                    $refValue = old('referral_code', $referralCode ?? '');
                    $refLocked = ($referralCode ?? null) && ! old('referral_code') && ! $errors->has('referral_code');
                    $discountPct = (float) ($referralDiscount ?? 0);
                    $discountLabel = rtrim(rtrim(number_format($discountPct, 2, ',', ''), '0'), ',').'%';
                    $rp = static fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
                @endphp
                <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3.5">
                    <div id="referralLocked" class="{{ $refLocked ? '' : 'hidden' }}">
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0"><i class="fa-solid fa-handshake text-sm"></i></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <span>Direkomendasikan oleh <span id="referralLockedName" class="text-emerald-700">{{ $referrerName ?: 'Affiliate MikPay' }}</span></span>
                                    <button type="button" class="js-referral-info shrink-0 w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] flex items-center justify-center hover:bg-indigo-500 transition" aria-label="Info kode referral">
                                        <i class="fa-solid fa-info"></i>
                                    </button>
                                </p>
                                <p class="text-[11px] text-slate-600 mt-0.5">
                                    Kode <span id="referralLockedCode" class="font-mono font-bold text-indigo-700">{{ $referralCode }}</span> otomatis terpakai.
                                    <button type="button" id="referralChangeBtn" class="font-semibold text-indigo-600 hover:underline">Ganti</button>
                                </p>
                                @if ($discountPct > 0)
                                    <p class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 bg-white border border-emerald-200 rounded-full px-2.5 py-0.5">
                                        <i class="fa-solid fa-gift"></i> Diskon {{ $discountLabel }} pembayaran paket pertama
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div id="referralEditor" class="{{ $refLocked ? 'hidden' : '' }}">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <label for="referral_code" class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
                                <i class="fa-solid fa-ticket text-indigo-500 text-[11px]"></i>
                                Kode referral affiliate <span class="font-normal text-slate-400">(opsional)</span>
                            </label>
                            <button type="button" class="js-referral-info inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 hover:text-indigo-700">
                                <span class="w-4 h-4 rounded-full bg-indigo-600 text-white text-[9px] flex items-center justify-center"><i class="fa-solid fa-info"></i></span>
                                Apa ini?
                            </button>
                        </div>
                        <input id="referral_code" type="text" name="referral_code" value="{{ $refValue }}" maxlength="20"
                               autocomplete="off" autocapitalize="characters" spellcheck="false"
                               class="w-full rounded-xl border {{ $errors->has('referral_code') ? 'border-rose-400' : 'border-slate-300' }} bg-white px-4 py-2.5 text-sm font-mono uppercase tracking-wider text-slate-900 placeholder:normal-case placeholder:tracking-normal placeholder:font-sans focus:outline-none focus:border-indigo-500 transition"
                               placeholder="Contoh: ANDI27">
                        <p id="referralStatus" class="text-[11px] mt-1.5 font-semibold {{ $errors->has('referral_code') ? 'text-rose-600' : 'hidden' }}" aria-live="polite">{{ $errors->first('referral_code') }}</p>
                    </div>

                    <p id="referralHint" class="text-[11px] text-slate-500 leading-relaxed mt-2 {{ $refLocked ? 'hidden' : '' }}">
                        @if ($discountPct > 0)
                            Punya kode dari affiliate MikPay? Dapatkan <b class="text-emerald-700">diskon {{ $discountLabel }}</b> di pembayaran paket pertama. Kosongkan jika tidak punya.
                        @else
                            Isi jika Anda diajak oleh affiliate MikPay. Kosongkan jika tidak punya.
                        @endif
                    </p>
                </div>

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
                    <div class="flex gap-2">
                        <div id="subdomainWrap" class="flex flex-1 rounded-xl border border-slate-300 bg-slate-50 overflow-hidden focus-within:border-indigo-500 focus-within:bg-white transition">
                            <input id="subdomain" type="text" name="subdomain" value="{{ old('subdomain') }}" required
                                   pattern="[a-z0-9]([a-z0-9-]{1,28}[a-z0-9])?" maxlength="30"
                                   class="flex-1 min-w-0 bg-transparent px-4 py-2.5 text-sm font-mono focus:outline-none lowercase"
                                   placeholder="annetwifi"
                                   autocomplete="off">
                            <span class="shrink-0 px-3 py-2.5 text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border-l border-slate-200 flex items-center">
                                .{{ $tenantBase }}
                            </span>
                        </div>
                        <button type="button" id="checkSubdomainBtn"
                                class="shrink-0 px-3 sm:px-4 rounded-xl border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition whitespace-nowrap">
                            Cek
                        </button>
                    </div>
                    <p id="subdomainStatus" class="text-[11px] mt-1.5 font-semibold min-h-[1.1rem]" aria-live="polite"></p>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Panel: <span class="font-mono font-bold text-indigo-700" id="panelPreview">….{{ $tenantBase }}</span><br>
                        Username VPN bonus: <span class="font-mono font-bold text-violet-700" id="vpnPreview">…&#64;{{ $accountSuffix }}</span><br>
                        <span class="text-amber-700 font-semibold">Permanen — tidak bisa diganti setelah daftar.</span>
                    </p>
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 mb-1.5">Nomor WhatsApp</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required
                           inputmode="tel" autocomplete="tel" pattern="[0-9+\-\s]{9,20}"
                           title="Nomor WhatsApp aktif, contoh 081234567890"
                           class="w-full rounded-xl border {{ $errors->has('phone') ? 'border-rose-400' : 'border-slate-300' }} bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition"
                           placeholder="08xxxxxxxxxx">
                    <p class="text-[11px] text-slate-500 mt-1">Untuk info akun, tagihan, dan bantuan setup.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <x-password-input
                        id="password"
                        name="password"
                        label="Password"
                        placeholder="Min. 8 karakter"
                        autocomplete="new-password"
                        :minlength="8"
                        required
                    />
                    <x-password-input
                        id="password_confirmation"
                        name="password_confirmation"
                        label="Ulangi password"
                        autocomplete="new-password"
                        :minlength="8"
                        required
                    />
                </div>

                <x-turnstile />

                <button type="submit" id="registerSubmitBtn" class="w-full gradient-bg text-white text-sm font-bold py-3 rounded-xl shadow-lg shadow-indigo-500/20 hover:opacity-95 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    Daftar &amp; mulai trial {{ (int) config('mikpay.trial_days', 3) }} hari
                </button>
                <p class="text-[11px] text-center text-slate-500 leading-relaxed">
                    Dengan mendaftar, Anda menyetujui
                    <a href="{{ route('legal.terms') }}" target="_blank" class="font-semibold text-indigo-600 hover:underline">Syarat &amp; Ketentuan</a>
                    dan
                    <a href="{{ route('legal.privacy') }}" target="_blank" class="font-semibold text-indigo-600 hover:underline">Kebijakan Privasi</a>
                    MikPay.
                </p>
            </form>
        </div>

        <x-register-progress-modal />

        <p class="text-center text-xs text-slate-500 mt-6 space-y-2">
            <span class="block">Sudah punya akun?
                <a href="{{ route('login') }}" class="text-indigo-600 font-bold hover:underline">Masuk</a>
            </span>
            <a href="{{ route('home') }}" class="hover:text-indigo-600 font-semibold transition">← Kembali ke beranda</a>
        </p>
    </div>
</section>

<div id="referralInfoModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="referralInfoTitle">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" data-referral-close></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl overflow-hidden">
        <div class="relative px-6 pt-6 pb-5 bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 text-white">
            <button type="button" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center transition" data-referral-close aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-widest bg-white/15 rounded-full px-2.5 py-1">
                <i class="fa-solid fa-bullhorn"></i> Pengumuman
            </span>
            @if ($discountPct > 0)
                <h3 id="referralInfoTitle" class="mt-3 text-lg font-extrabold leading-snug">Daftar pakai kode referral, hemat {{ $discountLabel }}!</h3>
                <p class="mt-1 text-xs text-white/80">Khusus pembayaran paket pertama setelah masa trial.</p>
            @else
                <h3 id="referralInfoTitle" class="mt-3 text-lg font-extrabold leading-snug">Tentang kode referral affiliate</h3>
            @endif
        </div>

        <div class="px-6 py-5 space-y-4">
            @if ($discountPct > 0 && ! empty($discountExample))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Contoh paket {{ $discountExample['name'] }}</p>
                    <p class="mt-1 flex items-baseline gap-2">
                        <span class="text-sm text-slate-400 line-through">{{ $rp($discountExample['price']) }}</span>
                        <span class="text-xl font-extrabold text-emerald-700">{{ $rp($discountExample['discounted']) }}</span>
                    </p>
                    <p class="text-[11px] text-emerald-700 mt-0.5">Hemat {{ $rp($discountExample['price'] - $discountExample['discounted']) }} — potongan otomatis, tanpa kupon.</p>
                </div>
            @endif

            <ul class="space-y-2.5 text-xs text-slate-600">
                @if ($discountPct > 0)
                    <li class="flex gap-2.5"><i class="fa-solid fa-gift text-emerald-600 mt-0.5"></i><span><b class="text-slate-800">Diskon {{ $discountLabel }}</b> berlaku untuk semua durasi paket (1, 3, atau 6 bulan) pada pembayaran pertama.</span></li>
                @endif
                <li class="flex gap-2.5"><i class="fa-solid fa-ticket text-indigo-600 mt-0.5"></i><span>Kode didapat dari <b class="text-slate-800">affiliate / tim marketing MikPay</b> yang mengajak Anda, contohnya <span class="font-mono font-bold">ANDI27</span>.</span></li>
                <li class="flex gap-2.5"><i class="fa-solid fa-headset text-indigo-600 mt-0.5"></i><span>Affiliate yang mengajak Anda bisa membantu proses setup panel & router.</span></li>
                <li class="flex gap-2.5"><i class="fa-solid fa-circle-check text-indigo-600 mt-0.5"></i><span>Tidak punya kode? Kosongkan saja — Anda tetap bisa daftar & trial gratis.</span></li>
            </ul>

            <p class="text-[10px] text-slate-400 leading-relaxed">Kode hanya bisa diisi saat pendaftaran dan tidak bisa ditambahkan setelah akun dibuat.</p>

            <button type="button" class="w-full gradient-bg text-white text-sm font-bold py-2.5 rounded-xl shadow-lg shadow-indigo-500/20 hover:opacity-95 transition" data-referral-close>
                Mengerti
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const STEP_LABELS = {
        validate: 'Validasi data formulir',
        tenant: 'Membuat subdomain & akun login',
        trial: 'Menyiapkan paket trial gratis',
        tunnel: 'Menyiapkan OpenVPN & port remote',
        firewall: 'Sinkronisasi firewall tunnel',
        dns: 'Mendaftarkan DNS subdomain',
        login: 'Mengirim email verifikasi',
        done: 'Menyelesaikan pendaftaran',
    };

    const input = document.getElementById('subdomain');
    const wrap = document.getElementById('subdomainWrap');
    const panel = document.getElementById('panelPreview');
    const vpn = document.getElementById('vpnPreview');
    const statusEl = document.getElementById('subdomainStatus');
    const btn = document.getElementById('checkSubdomainBtn');
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('registerSubmitBtn');
    const modal = document.getElementById('registerProgressModal');
    const stepsEl = document.getElementById('registerProgressSteps');
    const barEl = document.getElementById('registerProgressBar');
    const subtitleEl = document.getElementById('registerProgressSubtitle');
    const titleEl = document.getElementById('registerProgressTitle');
    const spinnerEl = document.getElementById('registerProgressSpinner');
    const footerEl = document.getElementById('registerProgressFooter');
    const closeBtn = document.getElementById('registerProgressClose');
    const base = @js('.'.$tenantBase);
    const suffix = @js('@'.$accountSuffix);
    const checkUrl = @js(route('register.check-subdomain'));
    const storeUrl = @js(route('register.store'));
    const csrf = @js(csrf_token());
    if (!input || !panel || !vpn || !statusEl || !btn || !form) return;

    let timer = null;
    let lastChecked = '';
    let available = null;
    let stepState = {};

    const sanitize = () => {
        input.value = (input.value || '').toLowerCase().replace(/[^a-z0-9-]/g, '').replace(/^-+/, '').replace(/-+$/, '');
        return input.value;
    };

    const syncPreview = () => {
        const v = sanitize();
        const vpnBase = v.replace(/[^a-z0-9]/g, '') || '…';
        panel.textContent = (v || '…') + base;
        vpn.textContent = vpnBase + suffix;
    };

    const setStatus = (state, message) => {
        statusEl.textContent = message || '';
        statusEl.className = 'text-[11px] mt-1.5 font-semibold min-h-[1.1rem]';
        wrap?.classList.remove('border-emerald-400', 'border-rose-400', 'border-amber-400', 'border-slate-300');
        if (state === 'ok') {
            statusEl.classList.add('text-emerald-600');
            wrap?.classList.add('border-emerald-400');
            available = true;
        } else if (state === 'bad') {
            statusEl.classList.add('text-rose-600');
            wrap?.classList.add('border-rose-400');
            available = false;
        } else if (state === 'loading') {
            statusEl.classList.add('text-slate-500');
            wrap?.classList.add('border-amber-400');
            available = null;
        } else {
            statusEl.classList.add('text-slate-500');
            wrap?.classList.add('border-slate-300');
            available = null;
        }
    };

    const check = async (force = false) => {
        const v = sanitize();
        syncPreview();
        if (!v) {
            setStatus('idle', '');
            lastChecked = '';
            return;
        }
        if (!force && v === lastChecked && available !== null) return;

        setStatus('loading', 'Mengecek ketersediaan…');
        btn.disabled = true;
        btn.classList.add('opacity-60');
        try {
            const res = await fetch(checkUrl + '?subdomain=' + encodeURIComponent(v), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            lastChecked = v;
            if (data.available) {
                setStatus('ok', '✓ ' + (data.message || 'Tersedia'));
            } else {
                setStatus('bad', '✕ ' + (data.message || 'Tidak tersedia'));
            }
        } catch (e) {
            setStatus('bad', 'Gagal cek subdomain. Coba lagi.');
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-60');
        }
    };

    const renderProgressSteps = () => {
        if (!stepsEl) return;
        const order = Object.keys(STEP_LABELS);
        stepsEl.innerHTML = order.map((key) => {
            const st = stepState[key] || { status: 'pending', message: '' };
            let icon = 'fa-circle text-slate-300';
            let textClass = 'text-slate-400';
            if (st.status === 'running') {
                icon = 'fa-spinner fa-spin text-indigo-600';
                textClass = 'text-indigo-700 font-semibold';
            } else if (st.status === 'done') {
                icon = 'fa-circle-check text-emerald-600';
                textClass = 'text-slate-700';
            } else if (st.status === 'error') {
                icon = 'fa-circle-xmark text-rose-600';
                textClass = 'text-rose-700 font-semibold';
            }
            const detail = st.message ? `<p class="text-[10px] text-slate-500 mt-0.5 pl-6">${st.message}</p>` : '';
            return `<li class="${textClass}">
                <div class="flex items-start gap-2">
                    <i class="fa-solid ${icon} mt-0.5 shrink-0"></i>
                    <span>${STEP_LABELS[key]}</span>
                </div>${detail}
            </li>`;
        }).join('');
    };

    const updateProgressBar = () => {
        const keys = Object.keys(STEP_LABELS);
        const done = keys.filter((k) => stepState[k]?.status === 'done').length;
        const pct = Math.round((done / keys.length) * 100);
        if (barEl) barEl.style.width = pct + '%';
    };

    const openProgressModal = () => {
        stepState = {};
        Object.keys(STEP_LABELS).forEach((k) => { stepState[k] = { status: 'pending', message: '' }; });
        renderProgressSteps();
        updateProgressBar();
        if (titleEl) titleEl.textContent = 'Memproses pendaftaran…';
        if (subtitleEl) subtitleEl.textContent = 'Mohon tunggu, jangan tutup halaman ini.';
        if (spinnerEl) {
            spinnerEl.className = 'w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0';
            spinnerEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        }
        footerEl?.classList.add('hidden');
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
    };

    const finishProgressModal = (ok, message) => {
        if (titleEl) titleEl.textContent = ok ? 'Pendaftaran berhasil!' : 'Pendaftaran gagal';
        if (subtitleEl) subtitleEl.textContent = message || '';
        if (spinnerEl) {
            spinnerEl.className = ok
                ? 'w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0'
                : 'w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0';
            spinnerEl.innerHTML = ok
                ? '<i class="fa-solid fa-check"></i>'
                : '<i class="fa-solid fa-xmark"></i>';
        }
        footerEl?.classList.remove('hidden');
        if (barEl) barEl.style.width = ok ? '100%' : barEl.style.width;
    };

    const applyProgressEvent = (data) => {
        if (!data?.step) return;
        if (data.step === 'complete') return;
        if (data.step === 'error') {
            finishProgressModal(false, data.message || 'Terjadi kesalahan.');
            return;
        }
        if (!STEP_LABELS[data.step]) return;
        stepState[data.step] = { status: data.status || 'running', message: data.message || '' };
        if (data.status === 'running' && subtitleEl && data.message) {
            subtitleEl.textContent = data.message;
        }
        renderProgressSteps();
        updateProgressBar();
        if (data.status === 'error') {
            finishProgressModal(false, data.message || 'Validasi gagal.');
        }
    };

    const submitWithProgress = async () => {
        const body = new FormData(form);
        if (window.turnstile && form.querySelector('.cf-turnstile') && !body.get('cf-turnstile-response')) {
            window.showToast?.('Selesaikan verifikasi keamanan (captcha) terlebih dahulu.');
            form.querySelector('.cf-turnstile').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        openProgressModal();
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-60');

        let succeeded = false;
        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/x-ndjson',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body,
            });

            if (!res.ok && !res.body) {
                throw new Error('Server error (' + res.status + ')');
            }

            const reader = res.body?.getReader();
            if (!reader) {
                throw new Error('Browser tidak mendukung streaming progress.');
            }

            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';
                for (const line of lines) {
                    if (!line.trim()) continue;
                    try {
                        const payload = JSON.parse(line);
                        if (payload.step === 'complete' && payload.status === 'done') {
                            succeeded = true;
                            finishProgressModal(true, payload.success || 'Mengalihkan ke panel…');
                            setTimeout(() => {
                                window.location.href = payload.redirect || '/dashboard';
                            }, 900);
                            return;
                        }
                        applyProgressEvent(payload);
                    } catch (e) {
                        // abaikan baris corrupt
                    }
                }
            }
        } catch (e) {
            finishProgressModal(false, e.message || 'Gagal mendaftar. Coba lagi.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-60');
            if (!succeeded && window.turnstile) {
                window.turnstile.reset();
            }
        }
    };

    input.addEventListener('input', () => {
        syncPreview();
        available = null;
        lastChecked = '';
        setStatus('idle', 'Ketik selesai, lalu klik Cek atau tunggu sebentar…');
        clearTimeout(timer);
        timer = setTimeout(() => check(false), 550);
    });

    btn.addEventListener('click', () => check(true));

    closeBtn?.addEventListener('click', () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    });

    form.addEventListener('submit', (e) => {
        const v = sanitize();
        if (!v) return;
        if (available === false) {
            e.preventDefault();
            setStatus('bad', 'Subdomain belum tersedia. Ganti dulu sebelum daftar.');
            input.focus();
            return;
        }
        e.preventDefault();
        submitWithProgress();
    });

    syncPreview();
    if (input.value) check(true);
})();

(function () {
    const input = document.getElementById('referral_code');
    const statusEl = document.getElementById('referralStatus');
    const locked = document.getElementById('referralLocked');
    const editor = document.getElementById('referralEditor');
    const changeBtn = document.getElementById('referralChangeBtn');
    const checkUrl = @js(route('register.check-referral'));
    if (!input || !statusEl) return;

    let timer = null;
    let seq = 0;

    const setStatus = (state, html) => {
        statusEl.innerHTML = html || '';
        statusEl.className = 'text-[11px] mt-1.5 font-semibold';
        input.classList.remove('border-emerald-400', 'border-rose-400', 'border-slate-300');
        if (state === 'ok') {
            statusEl.classList.add('text-emerald-600');
            input.classList.add('border-emerald-400');
        } else if (state === 'bad') {
            statusEl.classList.add('text-rose-600');
            input.classList.add('border-rose-400');
        } else if (state === 'loading') {
            statusEl.classList.add('text-slate-500');
            input.classList.add('border-slate-300');
        } else {
            statusEl.classList.add('hidden');
            input.classList.add('border-slate-300');
        }
    };

    const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const check = async () => {
        const code = (input.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (input.value !== code) input.value = code;
        if (!code) {
            setStatus('idle');
            return;
        }
        const mine = ++seq;
        setStatus('loading', '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Mengecek kode…');
        try {
            const res = await fetch(checkUrl + '?code=' + encodeURIComponent(code), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (mine !== seq) return;
            if (res.status === 429) {
                setStatus('bad', 'Terlalu sering mengecek. Tunggu sebentar lalu coba lagi.');
                return;
            }
            const data = await res.json();
            if (mine !== seq) return;
            if (data.valid) {
                const pct = Number(data.discount || 0);
                const bonus = pct > 0 ? ' · <span class="text-emerald-700">diskon ' + String(pct).replace('.', ',') + '% pembayaran pertama</span>' : '';
                setStatus('ok', '<i class="fa-solid fa-circle-check mr-1"></i>Kode valid — direkomendasikan oleh <b>' + escapeHtml(data.name) + '</b>' + bonus);
            } else {
                setStatus('bad', '<i class="fa-solid fa-circle-xmark mr-1"></i>' + escapeHtml(data.message || 'Kode tidak ditemukan.'));
            }
        } catch (e) {
            if (mine === seq) setStatus('bad', 'Gagal mengecek kode. Coba lagi.');
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(check, 500);
    });
    input.addEventListener('blur', () => {
        clearTimeout(timer);
        check();
    });

    const modal = document.getElementById('referralInfoModal');
    const openModal = () => {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };
    const closeModal = () => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };
    document.querySelectorAll('.js-referral-info').forEach((el) => el.addEventListener('click', openModal));
    modal?.querySelectorAll('[data-referral-close]').forEach((el) => el.addEventListener('click', closeModal));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) closeModal();
    });

    changeBtn?.addEventListener('click', () => {
        locked?.classList.add('hidden');
        editor?.classList.remove('hidden');
        document.getElementById('referralHint')?.classList.remove('hidden');
        input.focus();
        input.select();
        check();
    });

    if (input.value && !editor?.classList.contains('hidden') && statusEl.classList.contains('hidden')) {
        check();
    }
})();
</script>
@endsection
