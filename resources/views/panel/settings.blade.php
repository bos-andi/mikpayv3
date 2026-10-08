@extends('layouts.panel')

@section('title', 'Pengaturan Sistem — MikPay')
@section('page', 'settings')

@section('content')
@php
    $tab = $settingsTab ?? 'mikrotik';
    $t = current_tenant();
    $input = 'w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs text-slate-800 dark:text-slate-100';
    $tabMeta = [
        'mikrotik' => ['Router MikroTik', 'fa-solid fa-network-wired', 'Kelola router MikroTik yang terhubung ke MikPay.'],
        'payment' => ['Payment Gateway', 'fa-solid fa-credit-card', 'Atur penerimaan pembayaran pelanggan lewat Xendit.'],
        'billing' => ['Billing & Isolir', 'fa-solid fa-calendar-days', 'Atur masa tenggang sebelum pelanggan diisolir.'],
        'whatsapp' => ['WhatsApp Gateway', 'fa-brands fa-whatsapp', 'Hubungkan WhatsApp untuk notifikasi tagihan otomatis.'],
    ][$tab] ?? ['Router MikroTik', 'fa-solid fa-network-wired', ''];
@endphp

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Pengaturan Sistem</p>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="{{ $tabMeta[1] }} text-indigo-500 text-base"></i>
            {{ $tabMeta[0] }}
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $tabMeta[2] }}</p>
    </div>
</div>

@if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-500/10 dark:border-emerald-500/30 px-4 py-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 dark:border-rose-500/30 px-4 py-3 text-xs font-semibold text-rose-700 dark:text-rose-300">
        {{ session('error') }}
    </div>
@endif

@if ($tab === 'billing')
    @php
        $graceDays = old('grace_days', $t?->isolirGraceDays() ?? 2);
    @endphp
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm max-w-xl space-y-4">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Masa tenggang isolir</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Jumlah hari setelah tanggal jatuh tempo sebelum pelanggan dianggap siap isolir.
                Contoh: jatuh tempo tgl 10 + grace {{ $graceDays }} hari → isolir mulai setelah masa tenggang habis.
            </p>
        </div>
        <form method="POST" action="{{ panel_route('settings.billing.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Grace isolir (hari)</label>
                <input type="number" name="grace_days" value="{{ $graceDays }}" min="0" max="60" required
                       class="{{ $input }} max-w-[140px]">
                @error('grace_days')
                    <p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>
                @enderror
                <p class="text-[10px] text-slate-400 mt-1.5">0 = isolir keesokan hari setelah jatuh tempo. Maksimal 60 hari.</p>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-indigo-600/20 transition">
                <i class="fa-solid fa-floppy-disk"></i> Simpan
            </button>
        </form>
    </div>

@elseif ($tab === 'payment')
    @php
        $platformReady = \App\Support\PaymentGateway::platformXenditEnabled();
        $active = old('active', $t?->paymentActive() ?? 'platform');
        $radio = 'mt-1 accent-indigo-600';
        $card = 'flex items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-700 p-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 has-[:checked]:border-indigo-400 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500/30';
    @endphp
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 max-w-2xl">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Payment Gateway</h3>
            <p class="text-xs text-slate-500 mt-1">Hanya <strong>satu</strong> gateway yang aktif. Default platform = Xendit MikPay (terkunci). Pilih alternatif di bawah jika perlu.</p>
        </div>

        <div class="rounded-xl border px-3 py-3 text-[11px] space-y-1 {{ $platformReady ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-200' : 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30 text-amber-800 dark:text-amber-200' }}">
            <p class="font-bold flex items-center gap-2">
                <i class="fa-solid {{ $platformReady ? 'fa-lock' : 'fa-triangle-exclamation' }}"></i>
                Default MikPay — Xendit (tidak bisa diubah dari panel)
            </p>
            <p>{{ $platformReady ? 'Platform Xendit aktif. Kredensial hanya di server (.env), bukan di form ini.' : 'Platform Xendit belum diisi di .env (XENDIT_SECRET_KEY). Hubungi superadmin.' }}</p>
            <p class="opacity-80">Webhook pusat: <code class="font-mono">{{ url('/webhooks/xendit') }}</code></p>
        </div>

        <form method="POST" action="{{ panel_route('settings.xendit.update') }}" class="space-y-4" id="paymentGatewayForm">
            @csrf

            <div class="space-y-2">
                <p class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Pilih gateway aktif (satu saja)</p>

                <label class="{{ $card }}">
                    <input type="radio" name="active" value="platform" class="{{ $radio }}" @checked($active === 'platform') onchange="showGw('platform')">
                    <span>
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Default MikPay (Xendit)</span>
                        <span class="block text-[10px] text-slate-500 mt-0.5">Disarankan. Tanpa setup API key sendiri.</span>
                    </span>
                </label>

                <label class="{{ $card }}">
                    <input type="radio" name="active" value="xendit" class="{{ $radio }}" @checked($active === 'xendit') onchange="showGw('xendit')">
                    <span>
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Xendit (akun sendiri)</span>
                        <span class="block text-[10px] text-slate-500 mt-0.5">Uang masuk ke akun Xendit ISP Anda.</span>
                    </span>
                </label>

                <label class="{{ $card }}">
                    <input type="radio" name="active" value="midtrans" class="{{ $radio }}" @checked($active === 'midtrans') onchange="showGw('midtrans')">
                    <span>
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Midtrans</span>
                        <span class="block text-[10px] text-slate-500 mt-0.5">Snap / Core API — Server Key & Client Key.</span>
                    </span>
                </label>

                <label class="{{ $card }}">
                    <input type="radio" name="active" value="doku" class="{{ $radio }}" @checked($active === 'doku') onchange="showGw('doku')">
                    <span>
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">DOKU</span>
                        <span class="block text-[10px] text-slate-500 mt-0.5">Client ID & Secret Key.</span>
                    </span>
                </label>

                <label class="{{ $card }}">
                    <input type="radio" name="active" value="tripay" class="{{ $radio }}" @checked($active === 'tripay') onchange="showGw('tripay')">
                    <span>
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Tripay</span>
                        <span class="block text-[10px] text-slate-500 mt-0.5">API Key, Private Key & Merchant Code.</span>
                    </span>
                </label>
            </div>

            {{-- Xendit own --}}
            <div id="gw-xendit" class="gw-panel space-y-3 border-t border-slate-100 dark:border-slate-800 pt-4 {{ $active === 'xendit' ? '' : 'hidden' }}">
                <p class="text-[11px] font-bold text-slate-600">Konfigurasi Xendit (akun sendiri)</p>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Secret Key</label>
                    <input type="password" name="xendit_secret_key" class="{{ $input }}" placeholder="{{ $t?->hasOwnXenditSecretKey() ? '•••• tersimpan — isi untuk ganti' : 'xnd_production_… / xnd_development_…' }}" autocomplete="off">
                    @if ($t?->hasOwnXenditSecretKey())
                        <label class="mt-1.5 flex items-center gap-2 text-[10px] text-rose-600 font-semibold">
                            <input type="checkbox" name="clear_xendit_secret_key" value="1" class="rounded border-slate-300"> Hapus secret key
                        </label>
                    @endif
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Callback Token</label>
                    <input type="password" name="xendit_callback_token" class="{{ $input }}" placeholder="{{ $t?->hasOwnXenditCallbackToken() ? '•••• tersimpan — isi untuk ganti' : 'Opsional' }}" autocomplete="off">
                    @if ($t?->hasOwnXenditCallbackToken())
                        <label class="mt-1.5 flex items-center gap-2 text-[10px] text-rose-600 font-semibold">
                            <input type="checkbox" name="clear_xendit_callback_token" value="1" class="rounded border-slate-300"> Hapus callback token
                        </label>
                    @endif
                </div>
                <p class="text-[10px] text-emerald-600 font-semibold">Checkout online sudah didukung.</p>
            </div>

            {{-- Midtrans --}}
            <div id="gw-midtrans" class="gw-panel space-y-3 border-t border-slate-100 dark:border-slate-800 pt-4 {{ $active === 'midtrans' ? '' : 'hidden' }}">
                <p class="text-[11px] font-bold text-slate-600">Konfigurasi Midtrans</p>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Server Key</label>
                    <input type="password" name="midtrans_server_key" class="{{ $input }}" placeholder="{{ $t?->hasMetaSecret('midtrans.server_key') ? '•••• tersimpan — isi untuk ganti' : 'SB-Mid-server-… / Mid-server-…' }}" autocomplete="off">
                    @if ($t?->hasMetaSecret('midtrans.server_key'))
                        <label class="mt-1.5 flex items-center gap-2 text-[10px] text-rose-600 font-semibold">
                            <input type="checkbox" name="clear_midtrans_server_key" value="1" class="rounded border-slate-300"> Hapus server key
                        </label>
                    @endif
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Client Key</label>
                    <input type="text" name="midtrans_client_key" value="{{ old('midtrans_client_key', $t?->metaPlain('midtrans.client_key')) }}" class="{{ $input }}" placeholder="SB-Mid-client-… / Mid-client-…">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Merchant ID (opsional)</label>
                    <input type="text" name="midtrans_merchant_id" value="{{ old('midtrans_merchant_id', $t?->metaPlain('midtrans.merchant_id')) }}" class="{{ $input }}">
                </div>
                <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-200">
                    <input type="checkbox" name="midtrans_is_production" value="1" class="rounded border-slate-300" @checked(old('midtrans_is_production', $t?->metaPlain('midtrans.is_production')))>
                    Mode production
                </label>
                <p class="text-[10px] text-amber-600 font-semibold">Kredensial tersimpan. Checkout Midtrans akan diaktifkan bertahap (saat ini bayar online via Xendit).</p>
            </div>

            {{-- Doku --}}
            <div id="gw-doku" class="gw-panel space-y-3 border-t border-slate-100 dark:border-slate-800 pt-4 {{ $active === 'doku' ? '' : 'hidden' }}">
                <p class="text-[11px] font-bold text-slate-600">Konfigurasi DOKU</p>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Client ID</label>
                    <input type="text" name="doku_client_id" value="{{ old('doku_client_id', $t?->metaPlain('doku.client_id')) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Secret Key</label>
                    <input type="password" name="doku_secret_key" class="{{ $input }}" placeholder="{{ $t?->hasMetaSecret('doku.secret_key') ? '•••• tersimpan — isi untuk ganti' : 'Secret Key DOKU' }}" autocomplete="off">
                    @if ($t?->hasMetaSecret('doku.secret_key'))
                        <label class="mt-1.5 flex items-center gap-2 text-[10px] text-rose-600 font-semibold">
                            <input type="checkbox" name="clear_doku_secret_key" value="1" class="rounded border-slate-300"> Hapus secret key
                        </label>
                    @endif
                </div>
                <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-200">
                    <input type="checkbox" name="doku_is_production" value="1" class="rounded border-slate-300" @checked(old('doku_is_production', $t?->metaPlain('doku.is_production')))>
                    Mode production
                </label>
                <p class="text-[10px] text-amber-600 font-semibold">Kredensial tersimpan. Checkout DOKU akan diaktifkan bertahap.</p>
            </div>

            {{-- Tripay --}}
            <div id="gw-tripay" class="gw-panel space-y-3 border-t border-slate-100 dark:border-slate-800 pt-4 {{ $active === 'tripay' ? '' : 'hidden' }}">
                <p class="text-[11px] font-bold text-slate-600">Konfigurasi Tripay</p>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">API Key</label>
                    <input type="password" name="tripay_api_key" class="{{ $input }}" placeholder="{{ $t?->hasMetaSecret('tripay.api_key') ? '•••• tersimpan — isi untuk ganti' : 'API Key Tripay' }}" autocomplete="off">
                    @if ($t?->hasMetaSecret('tripay.api_key'))
                        <label class="mt-1.5 flex items-center gap-2 text-[10px] text-rose-600 font-semibold">
                            <input type="checkbox" name="clear_tripay_api_key" value="1" class="rounded border-slate-300"> Hapus API key
                        </label>
                    @endif
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Private Key</label>
                    <input type="password" name="tripay_private_key" class="{{ $input }}" placeholder="{{ $t?->hasMetaSecret('tripay.private_key') ? '•••• tersimpan — isi untuk ganti' : 'Private Key Tripay' }}" autocomplete="off">
                    @if ($t?->hasMetaSecret('tripay.private_key'))
                        <label class="mt-1.5 flex items-center gap-2 text-[10px] text-rose-600 font-semibold">
                            <input type="checkbox" name="clear_tripay_private_key" value="1" class="rounded border-slate-300"> Hapus private key
                        </label>
                    @endif
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Merchant Code</label>
                    <input type="text" name="tripay_merchant_code" value="{{ old('tripay_merchant_code', $t?->metaPlain('tripay.merchant_code')) }}" class="{{ $input }}">
                </div>
                <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-200">
                    <input type="checkbox" name="tripay_is_production" value="1" class="rounded border-slate-300" @checked(old('tripay_is_production', $t?->metaPlain('tripay.is_production')))>
                    Mode production
                </label>
                <p class="text-[10px] text-amber-600 font-semibold">Kredensial tersimpan. Checkout Tripay akan diaktifkan bertahap.</p>
            </div>

            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl">
                <i class="fa-solid fa-save"></i> Simpan pilihan gateway
            </button>
        </form>
    </div>
    <script>
    function showGw(name) {
        document.querySelectorAll('.gw-panel').forEach(el => el.classList.add('hidden'));
        const panel = document.getElementById('gw-' + name);
        if (panel) panel.classList.remove('hidden');
    }
    </script>

@elseif ($tab === 'whatsapp')
    @php
        $waDefs = \App\Support\WaapiTemplates::definitions();
        $waGate = $t?->waapiGatewayStatus(true) ?? ['state' => 'off', 'label' => 'WA Gateway Off', 'detail' => null, 'phone' => null];
    @endphp

    @if (($waGate['state'] ?? '') === 'disconnected')
        <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-900 text-xs font-semibold px-4 py-3">
            <p class="font-bold mb-1"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Perangkat WhatsApp belum terhubung</p>
            <p class="font-normal text-[11px] leading-relaxed">
                Token sudah tersimpan, tapi device WaAPI statusnya <strong>DISCONNECTED</strong>
                @if (! empty($waGate['phone']))
                    ({{ $waGate['phone'] }})
                @endif.
                Reminder otomatis <strong>tidak bisa terkirim</strong> sampai device di-
                <a href="https://wa-api.id/app" target="_blank" rel="noopener" class="underline font-bold">wa-api.id/app</a>
                di-scan ulang / dihubungkan kembali.
            </p>
        </div>
    @endif
    @if (($waGate['state'] ?? '') === 'connected')
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 text-xs font-semibold px-4 py-3">
            Device terhubung
            @if (! empty($waGate['phone']))
                · {{ $waGate['phone'] }}
            @endif
            · reminder otomatis siap (jam 08:00 WIB).
        </div>
    @endif

    <form method="POST" action="{{ panel_route('settings.whatsapp.update') }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 items-start">
            {{-- Kiri: koneksi --}}
            <div class="xl:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">WhatsApp Gateway (WaAPI)</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Token dari
                        <a href="https://wa-api.id/app" target="_blank" rel="noopener" class="text-indigo-600 font-bold">wa-api.id/app</a>
                        → Device → Token.
                        Docs:
                        <a href="https://wa-api.id/docs" target="_blank" rel="noopener" class="text-indigo-600 font-bold">wa-api.id/docs</a>
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 px-3 py-2 text-[11px] text-slate-600 dark:text-slate-300">
                    API: <code class="font-mono">POST {{ config('mikpay.whatsapp.waapi_base_url') }}/send</code>
                </div>

                <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-200">
                    <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300" @checked($t?->waapiEnabled())>
                    Aktifkan WhatsApp Gateway
                </label>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Device / API Token WaAPI</label>
                    <input type="password" name="api_token" class="{{ $input }}" placeholder="{{ $t?->hasWaapiToken() ? '•••• tersimpan — isi untuk ganti' : 'waapi_dev_… atau waapi_…' }}" autocomplete="off">
                    @if ($t?->hasWaapiToken())
                        <label class="mt-1.5 flex items-center gap-2 text-[10px] text-rose-600 font-semibold">
                            <input type="checkbox" name="clear_api_token" value="1" class="rounded border-slate-300"> Hapus token
                        </label>
                    @endif
                </div>

                <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2 text-[10px] text-amber-800 dark:text-amber-200">
                    <strong>Bukan satu template saja.</strong> Di kanan, tiap jenis pesan punya teks sendiri.
                    Toggle = aktifkan <em>reminder otomatis</em> untuk jenis itu (H-3 / H-0 / tunggakan).
                    Jadwal cek otomatis: <strong>setiap hari jam 08:00 WIB</strong>.
                    H-3/H-0 sekali di hari itu; <strong>tunggakan/siap isolir hanya sekali</strong> per episode tunggakan (tidak diulang tiap hari).
                    Tombol WA di Billing memakai template “link bayar”.
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl">
                    <i class="fa-solid fa-save"></i> Simpan semua template & toggle
                </button>

                <div class="border-t border-slate-100 dark:border-slate-800 pt-4 space-y-2">
                    <p class="text-[11px] font-bold text-slate-600">Tes koneksi (pesan singkat)</p>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" form="waTestForm" name="test_phone" required placeholder="08xxxxxxxxxx" class="{{ $input }} sm:flex-1">
                        <button type="submit" form="waTestForm" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl">
                            <i class="fa-brands fa-whatsapp"></i> Kirim uji
                        </button>
                    </div>
                </div>
            </div>

            {{-- Kanan: multi template + toggle --}}
            <div class="xl:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Template pesan & reminder otomatis</h3>
                    <p class="text-[11px] text-slate-500 mt-1">Edit isi masing-masing. Nyalakan toggle hanya untuk yang ingin dikirim otomatis (jam 08:00 WIB).</p>
                </div>

                <div class="rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/30 px-3 py-2 text-[10px] text-indigo-800 dark:text-indigo-200 space-y-1">
                    <p class="font-bold">Placeholder</p>
                    <p><code class="font-mono">{nama}</code> · <code class="font-mono">{username}</code> · <code class="font-mono">{periode}</code> · <code class="font-mono">{nominal}</code> · <code class="font-mono">{tanggal}</code> · <code class="font-mono">{link}</code></p>
                    <p class="text-slate-500 dark:text-indigo-300/80"><code class="font-mono">{tanggal}</code> = tanggal jatuh tempo (mis. 31 Jul 2026)</p>
                </div>

                <div class="space-y-3 max-h-[720px] overflow-y-auto pr-1">
                    @foreach ($waDefs as $key => $def)
                        @php
                            $body = old('templates.'.$key, $t ? \App\Support\WaapiTemplates::body($t, $key) : $def['default']);
                            $autoOn = old('auto.'.$key, $t ? \App\Support\WaapiTemplates::autoEnabled($t, $key) : false);
                        @endphp
                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-3 space-y-2 {{ $def['auto'] && $autoOn ? 'ring-1 ring-emerald-500/30 border-emerald-300 dark:border-emerald-500/40' : '' }}">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">{{ $def['title'] }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $def['desc'] }}</p>
                                </div>
                                @if ($def['auto'])
                                    <label class="shrink-0 inline-flex items-center gap-2 text-[10px] font-bold px-2.5 py-1.5 rounded-lg border cursor-pointer {{ $autoOn ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-600' }}">
                                        <input type="checkbox" name="auto[{{ $key }}]" value="1" class="rounded border-slate-300" @checked($autoOn)>
                                        Reminder otomatis
                                    </label>
                                @else
                                    <span class="shrink-0 text-[9px] font-bold px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500">Manual / referensi</span>
                                @endif
                            </div>
                            <textarea name="templates[{{ $key }}]" rows="5" class="{{ $input }} font-mono text-[11px] leading-relaxed">{{ $body }}</textarea>
                            <button type="button" class="text-[10px] font-bold text-indigo-600 hover:underline" onclick="resetWaTpl(this, @js($def['default']))">
                                Kembalikan contoh default
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </form>

    <form id="waTestForm" method="POST" action="{{ panel_route('settings.whatsapp.test') }}" class="hidden">
        @csrf
    </form>
    <script>
    function resetWaTpl(btn, text) {
        const ta = btn.parentElement.querySelector('textarea');
        if (ta) ta.value = text;
    }
    </script>

@else
    <div class="space-y-4">
        @php
            $tunnelHint = null;
            $tunnelApiHost = null;
            try {
                $tenantForTunnel = current_tenant();
                if ($tenantForTunnel?->id) {
                    $tunnelHint = \App\Models\TunnelAccount::query()
                        ->with(['portMaps', 'node'])
                        ->where('tenant_id', $tenantForTunnel->id)
                        ->whereIn('status', ['active', 'pending'])
                        ->latest('id')
                        ->first();
                    $apiMapHint = $tunnelHint?->portMaps->firstWhere('label', 'API');
                    $tunnelApiHost = $apiMapHint?->endpoint();
                }
            } catch (\Throwable) {
                $tunnelHint = null;
            }
        @endphp

        <div class="rounded-2xl border border-violet-200 dark:border-violet-500/30 bg-violet-50/80 dark:bg-violet-500/10 px-4 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-violet-600 text-white flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-shield-halved text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-extrabold text-violet-950 dark:text-violet-100">Belum bisa remote / API gagal dari luar jaringan?</p>
                    <p class="text-[11px] text-violet-900/75 dark:text-violet-200/80 mt-0.5 leading-relaxed">
                        Pasang dulu script OpenVPN di Terminal MikroTik (menu Remote).
                        Setelah tunnel running, isi host API dengan
                        @if ($tunnelApiHost)
                            <span class="font-mono font-bold">{{ $tunnelApiHost }}</span>
                        @else
                            <span class="font-semibold">URL remote API dari halaman Remote</span>
                        @endif
                        — bukan IP lokal.
                    </p>
                </div>
            </div>
            <a href="{{ panel_route('remote') }}#ovpn-setup"
               class="inline-flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shrink-0 shadow-md shadow-violet-600/20">
                Buka Setup OpenVPN <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Koneksi API RouterOS</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Semua paket MikPay berlaku untuk {{ $routerLimit }} router MikroTik per akun. Ingin ganti perangkat? Cukup edit router yang sudah ada.
                </p>
            </div>
            @if ($canAddRouter)
                <a href="{{ panel_route('settings.routers.create') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-indigo-600/20 transition">
                    <i class="fa-solid fa-plus"></i> Tambah Router
                </a>
            @else
                <span class="inline-flex items-center gap-2 text-[11px] font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-3 py-2 rounded-xl">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i> {{ $routers->count() }}/{{ $routerLimit }} router terpakai
                </span>
            @endif
        </div>

        @if ($routers->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-8 text-center space-y-3">
                <div class="w-12 h-12 mx-auto rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                    <i class="fa-solid fa-router text-lg"></i>
                </div>
                <p class="text-sm font-bold text-slate-900 dark:text-white">Belum ada router</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">Tambahkan router MikroTik agar MikPay bisa terhubung via API untuk isolir, PPPoE, dan monitoring.</p>
                <a href="{{ panel_route('settings.routers.create') }}" class="inline-flex items-center gap-2 text-xs font-bold text-indigo-600 hover:text-indigo-500">
                    Tambah router pertama →
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach ($routers as $router)
                    @php $isActive = $activeRouter && (int) $activeRouter->id === (int) $router->id; @endphp
                    <div class="bg-white dark:bg-slate-900 border rounded-2xl p-5 shadow-sm space-y-4 {{ $isActive ? 'border-indigo-400 dark:border-indigo-500/50 ring-1 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $router->displayName() }}</h4>
                                    <span class="text-[10px] font-mono text-slate-400">{{ $router->name }}</span>
                                    @if ($isActive)
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-600 text-white">AKTIF</span>
                                    @endif
                                    @if ($router->is_default)
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20">DEFAULT</span>
                                    @endif
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $router->isEnabled() ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-slate-200 text-slate-500 border-slate-300' }} border">
                                        {{ strtoupper($router->status) }}
                                    </span>
                                </div>
                                <p class="text-xs font-mono text-slate-500 dark:text-slate-400 mt-1">{{ $router->endpointLabel() }} · {{ $router->username }}</p>
                            </div>
                            @if ($router->last_check_ok === true)
                                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"><i class="fa-solid fa-circle-check"></i> API OK</span>
                            @elseif ($router->last_check_ok === false)
                                <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400"><i class="fa-solid fa-circle-xmark"></i> Gagal</span>
                            @else
                                <span class="text-[10px] font-bold text-slate-400">Belum ditest</span>
                            @endif
                        </div>

                        @if ($router->last_check_message)
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-xl px-3 py-2">
                                {{ $router->last_check_message }}
                                @if ($router->last_checked_at)
                                    <span class="block text-[10px] mt-1 text-slate-400">{{ $router->last_checked_at->diffForHumans() }}</span>
                                @endif
                            </p>
                        @endif

                        <div class="flex flex-wrap gap-2 pt-1">
                            @if (! $isActive)
                                <form method="POST" action="{{ panel_route('settings.routers.select', $router) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold px-3 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white transition" @disabled(! $router->isEnabled())>
                                        <i class="fa-solid fa-check-double mr-1"></i> Jadikan Aktif
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ panel_route('settings.routers.test', $router) }}">
                                @csrf
                                <button type="submit" class="text-xs font-bold px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white transition">
                                    <i class="fa-solid fa-plug mr-1"></i> Test Koneksi
                                </button>
                            </form>
                            <a href="{{ panel_route('settings.routers.edit', $router) }}" class="text-xs font-bold px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 transition">
                                <i class="fa-solid fa-pen mr-1"></i> Edit
                            </a>
                            <form method="POST" action="{{ panel_route('settings.routers.destroy', $router) }}" onsubmit="return MikPay.confirmForm(event, 'Hapus router ini? Konfigurasi terkait tidak bisa dikembalikan.', { title: 'Hapus Router', variant: 'danger', confirmText: 'Ya, hapus' })">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold px-3 py-2 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-500/30 dark:hover:bg-rose-500/10 transition">
                                    <i class="fa-solid fa-trash mr-1"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
@endsection
