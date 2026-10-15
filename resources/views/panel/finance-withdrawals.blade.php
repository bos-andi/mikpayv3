@extends('layouts.panel')

@section('title', 'Penarikan Dana — MikPay')
@section('page', 'finance-withdrawals')

@section('content')
@php
    $rp = fn ($v) => 'Rp '.number_format((int) $v, 0, ',', '.');
    $verified = $account?->isVerified() ?? false;
    $editBank = ! $account || $errors->hasAny(['bank_code', 'account_number', 'account_holder', 'proof', 'ktp', 'current_password']);
    $inputClass = 'w-full text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500';
@endphp

<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Laporan Keuangan</p>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="fa-solid fa-building-columns text-indigo-500 text-base"></i>
            Penarikan Dana
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
            Tarik hasil pembayaran pelanggan yang masuk lewat Xendit MikPay ke rekening bank Anda.
        </p>
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
@if ($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 dark:border-rose-500/30 px-4 py-3 text-xs font-semibold text-rose-700 dark:text-rose-300">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@unless ($usesPlatform)
    <div class="rounded-xl border border-sky-200 bg-sky-50 dark:bg-sky-500/10 dark:border-sky-500/30 px-4 py-3 text-xs text-sky-800 dark:text-sky-200 leading-relaxed">
        <i class="fa-solid fa-circle-info mr-1"></i>
        Tenant Anda memakai payment gateway sendiri, jadi pembayaran baru langsung masuk ke akun gateway Anda.
        Halaman ini hanya untuk saldo yang pernah diterima lewat Xendit MikPay.
    </div>
@endunless

@unless ($settings['enabled'])
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-500/10 dark:border-amber-500/30 px-4 py-3 text-xs font-semibold text-amber-800 dark:text-amber-200">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Penarikan dana sedang dinonaktifkan sementara oleh admin MikPay.
    </div>
@endunless

{{-- Saldo --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-cyan-600 p-5 text-white shadow-lg shadow-indigo-600/20 sm:col-span-2 xl:col-span-1">
        <p class="text-[11px] font-bold uppercase tracking-wider text-white/80">Saldo Bisa Ditarik</p>
        <p class="text-2xl font-extrabold mt-1">{{ $rp($balance['available']) }}</p>
        @if ($balance['on_hold'] > 0)
            <p class="text-[11px] text-white/80 mt-2">
                <i class="fa-regular fa-clock mr-0.5"></i> {{ $rp($balance['on_hold']) }} masih dalam masa settlement {{ $settings['hold_days'] }} hari.
            </p>
        @else
            <p class="text-[11px] text-white/80 mt-2">Siap diajukan kapan saja.</p>
        @endif
        <i class="fa-solid fa-wallet absolute -right-3 -bottom-4 text-7xl text-white/10"></i>
    </div>
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Diterima (Xendit MikPay)</p>
        <p class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $rp($balance['collected']) }}</p>
        <p class="text-[11px] text-slate-400 mt-2">Akumulasi pembayaran online pelanggan.</p>
    </div>
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Sedang Diproses</p>
        <p class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $rp($balance['in_progress']) }}</p>
        <p class="text-[11px] text-slate-400 mt-2">Menunggu konfirmasi / transfer.</p>
    </div>
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Sudah Ditarik</p>
        <p class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $rp($balance['withdrawn']) }}</p>
        <p class="text-[11px] text-slate-400 mt-2">Total dana yang berhasil dikirim.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
    {{-- Rekening tujuan --}}
    <div class="lg:col-span-2 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2 mb-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 inline-flex items-center justify-center"><i class="fa-solid fa-building-columns"></i></span>
                Rekening Tujuan
            </h3>
            @if ($account && $canManage)
                <button type="button" id="btnEditBank" class="text-xs font-bold text-indigo-600 dark:text-indigo-300 hover:underline {{ $editBank ? 'hidden' : '' }}">
                    <i class="fa-solid fa-pen-to-square"></i> {{ $account->isRejected() ? 'Ajukan Ulang' : 'Ganti Rekening' }}
                </button>
            @endif
        </div>

        @if ($account)
            <div id="bankCard" class="{{ $editBank ? 'hidden' : '' }} space-y-3">
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $account->bank_name ?: $account->bank_code }}</p>
                        <span class="inline-flex px-2 py-0.5 rounded-full border text-[10px] font-bold whitespace-nowrap {{ $account->statusBadgeClass() }}">{{ $account->statusLabel() }}</span>
                    </div>
                    <p class="text-lg font-extrabold tracking-wider text-slate-900 dark:text-white mt-1 font-mono">{{ $account->account_number }}</p>
                    <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 mt-0.5">a.n. {{ $account->account_holder }}</p>
                    <div class="flex items-center justify-between gap-2 mt-3">
                        <p class="text-[10px] text-slate-400">Diajukan {{ $account->created_at?->format('d M Y H:i') }}</p>
                        <span class="flex items-center gap-3">
                            <a href="{{ panel_route('finance.withdrawals.bank.proof', $account) }}" target="_blank" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-300 hover:underline">
                                <i class="fa-regular fa-image"></i> Buku tabungan
                            </a>
                            @if ($account->ktp_path)
                                <a href="{{ panel_route('finance.withdrawals.bank.ktp', $account) }}" target="_blank" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-300 hover:underline">
                                    <i class="fa-regular fa-id-card"></i> KTP
                                </a>
                            @endif
                        </span>
                    </div>
                </div>

                @if ($account->isPending())
                    <p class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2.5 text-[11px] text-amber-800 dark:text-amber-200 leading-relaxed">
                        <i class="fa-regular fa-clock mr-1"></i> Rekening sedang diverifikasi admin MikPay (biasanya &lt; 1x24 jam kerja). Penarikan dapat diajukan setelah terverifikasi.
                    </p>
                @elseif ($account->isRejected())
                    <p class="rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 px-3 py-2.5 text-[11px] text-rose-700 dark:text-rose-300 leading-relaxed">
                        <i class="fa-solid fa-circle-xmark mr-1"></i> Ditolak: {{ $account->reject_reason }}. Silakan ajukan ulang dengan data yang benar.
                    </p>
                @else
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">
                        <i class="fa-solid fa-circle-check mr-1"></i> Terverifikasi {{ $account->reviewed_at?->format('d M Y') }} — siap dipakai untuk penarikan.
                    </p>
                @endif
            </div>
        @endif

        @if ($canManage)
            <form id="bankForm" method="POST" action="{{ panel_route('finance.withdrawals.bank') }}" enctype="multipart/form-data" class="space-y-3 {{ $editBank ? '' : 'hidden' }}">
                @csrf
                @if ($account?->isVerified())
                    <p class="rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/30 px-3 py-2 text-[11px] text-sky-800 dark:text-sky-200">
                        Rekening baru perlu diverifikasi ulang. Selama menunggu, penarikan belum bisa diajukan.
                    </p>
                @endif
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Bank</label>
                    <select name="bank_code" required class="{{ $inputClass }}">
                        <option value="">— Pilih bank —</option>
                        @foreach ($banks as $code => $name)
                            <option value="{{ $code }}" @selected(old('bank_code', $account?->bank_code) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Penarikan hanya ke rekening bank. E-wallet (DANA, OVO, GoPay, ShopeePay) tidak didukung.</p>
                    @error('bank_code')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Nomor Rekening / HP</label>
                    <input type="text" name="account_number" inputmode="numeric" required maxlength="20"
                           value="{{ old('account_number', $account?->account_number) }}" class="{{ $inputClass }} font-mono" placeholder="contoh: 1234567890">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Nama Pemilik Rekening</label>
                    <input type="text" name="account_holder" required maxlength="100"
                           value="{{ old('account_holder', $account?->account_holder) }}" class="{{ $inputClass }} uppercase" placeholder="Sesuai buku tabungan">
                    <p class="text-[10px] text-slate-400 mt-1">Nama harus sama persis dengan di bank agar transfer tidak gagal.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Foto Buku Tabungan / Bukti Rekening</label>
                    <label for="proofInput" class="flex items-center gap-3 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-3 cursor-pointer hover:border-indigo-400 transition">
                        <span class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 inline-flex items-center justify-center shrink-0"><i class="fa-solid fa-camera"></i></span>
                        <span class="min-w-0">
                            <span id="proofName" class="block text-xs font-semibold text-slate-700 dark:text-slate-200 truncate">Pilih foto halaman depan buku tabungan</span>
                            <span class="block text-[10px] text-slate-400">JPG, PNG, WEBP, atau PDF · maks 5 MB. E-wallet: screenshot profil akun.</span>
                        </span>
                    </label>
                    <input type="file" id="proofInput" name="proof" required accept="image/jpeg,image/png,image/webp,application/pdf" class="sr-only" data-file-name="proofName" data-file-preview="proofPreview">
                    <img id="proofPreview" alt="" class="hidden mt-2 max-h-40 rounded-lg border border-slate-200 dark:border-slate-700">
                    @error('proof')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Foto KTP Pemilik Rekening</label>
                    <label for="ktpInput" class="flex items-center gap-3 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-3 cursor-pointer hover:border-indigo-400 transition">
                        <span class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 inline-flex items-center justify-center shrink-0"><i class="fa-solid fa-id-card"></i></span>
                        <span class="min-w-0">
                            <span id="ktpName" class="block text-xs font-semibold text-slate-700 dark:text-slate-200 truncate">Pilih foto KTP</span>
                            <span class="block text-[10px] text-slate-400">Nama di KTP harus sama dengan pemilik rekening · JPG, PNG, WEBP, atau PDF · maks 5 MB.</span>
                        </span>
                    </label>
                    <input type="file" id="ktpInput" name="ktp" required accept="image/jpeg,image/png,image/webp,application/pdf" class="sr-only" data-file-name="ktpName" data-file-preview="ktpPreview">
                    <img id="ktpPreview" alt="" class="hidden mt-2 max-h-40 rounded-lg border border-slate-200 dark:border-slate-700">
                    @error('ktp')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Password Akun MikPay</label>
                    <input type="password" name="current_password" required autocomplete="current-password" class="{{ $inputClass }}" placeholder="Konfirmasi dengan password login">
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button type="submit" class="inline-flex items-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl bg-gradient-to-br from-indigo-600 to-cyan-600 text-white shadow-md shadow-indigo-600/20">
                        <i class="fa-solid fa-paper-plane"></i> Ajukan Verifikasi Rekening
                    </button>
                    @if ($account)
                        <button type="button" id="btnCancelBank" class="text-xs font-bold px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300">Batal</button>
                    @endif
                </div>
            </form>
        @elseif (! $account)
            <p class="text-xs text-slate-500">Belum ada rekening. Minta admin tenant untuk mengajukannya.</p>
        @endif
    </div>

    {{-- Ajukan penarikan --}}
    <div class="lg:col-span-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 inline-flex items-center justify-center"><i class="fa-solid fa-money-bill-wave"></i></span>
            Ajukan Penarikan
        </h3>

        @php
            $canRequest = $canManage && $verified && $settings['enabled'] && $balance['available'] >= $settings['min_amount'];
        @endphp

        <form method="POST" action="{{ panel_route('finance.withdrawals.store') }}" class="space-y-3" id="withdrawForm">
            @csrf
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300">Nominal Penarikan</label>
                    <button type="button" id="btnMaxAmount" data-max="{{ $balance['available'] }}" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-300 hover:underline" @disabled(! $canRequest)>Tarik semua</button>
                </div>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">Rp</span>
                    <input type="number" name="amount" id="withdrawAmount" required min="{{ $settings['min_amount'] }}" max="{{ $balance['available'] }}" step="1"
                           value="{{ old('amount') }}" class="{{ $inputClass }} pl-10 text-base font-bold" placeholder="{{ number_format($settings['min_amount'], 0, ',', '.') }}" @disabled(! $canRequest)>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Minimal {{ $rp($settings['min_amount']) }} · Maksimal {{ $rp($balance['available']) }}</p>
            </div>

            <div class="rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-3 text-xs space-y-1.5">
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Biaya admin
                        @if ($settings['fee_flat'] > 0 || $settings['fee_percent'] > 0)
                            ({{ collect([$settings['fee_flat'] > 0 ? $rp($settings['fee_flat']) : null, $settings['fee_percent'] > 0 ? rtrim(rtrim(number_format($settings['fee_percent'], 2, ',', '.'), '0'), ',').'%' : null])->filter()->join(' + ') }})
                        @endif
                    </span>
                    <span id="feePreview">{{ $rp(0) }}</span>
                </div>
                <div class="flex justify-between font-bold text-slate-900 dark:text-white">
                    <span>Diterima di rekening</span>
                    <span id="netPreview" class="text-emerald-600 dark:text-emerald-400">{{ $rp(0) }}</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Catatan (opsional)</label>
                <input type="text" name="note" maxlength="500" value="{{ old('note') }}" class="{{ $inputClass }}" placeholder="mis. setoran bulan Oktober" @disabled(! $canRequest)>
            </div>

            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 text-sm font-bold px-4 py-3 rounded-xl bg-gradient-to-br from-indigo-600 to-cyan-600 text-white shadow-md shadow-indigo-600/20 disabled:opacity-50 disabled:cursor-not-allowed" @disabled(! $canRequest)>
                <i class="fa-solid fa-paper-plane"></i> Ajukan Penarikan
            </button>

            @if (! $account)
                <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold"><i class="fa-solid fa-circle-exclamation"></i> Ajukan rekening tujuan & foto buku tabungan terlebih dahulu.</p>
            @elseif (! $verified)
                <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold"><i class="fa-solid fa-circle-exclamation"></i> Penarikan bisa diajukan setelah rekening diverifikasi admin MikPay.</p>
            @elseif ($balance['available'] < $settings['min_amount'])
                <p class="text-[11px] text-slate-500 dark:text-slate-400"><i class="fa-solid fa-circle-info"></i> Saldo belum mencapai minimal penarikan.</p>
            @elseif (! $canManage)
                <p class="text-[11px] text-slate-500 dark:text-slate-400"><i class="fa-solid fa-lock"></i> Hanya admin tenant yang dapat mengajukan penarikan.</p>
            @endif
        </form>

        <ol class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
            @foreach ([['fa-id-card', 'Daftarkan rekening & buku tabungan'], ['fa-user-shield', 'Admin verifikasi rekening'], ['fa-paper-plane', 'Ajukan penarikan'], ['fa-building-columns', 'Dana dikirim ke rekening']] as $i => [$icon, $label])
                <li class="rounded-xl border border-slate-200 dark:border-slate-800 px-2 py-3">
                    <span class="w-7 h-7 mx-auto rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 inline-flex items-center justify-center text-xs"><i class="fa-solid {{ $icon }}"></i></span>
                    <p class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 mt-1.5 leading-tight">{{ $i + 1 }}. {{ $label }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</div>

{{-- Riwayat --}}
<div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Riwayat Penarikan</h3>
        <span class="text-[11px] text-slate-400">{{ $withdrawals->count() }} terakhir</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3 font-bold">Referensi</th>
                    <th class="text-left px-4 py-3 font-bold">Rekening</th>
                    <th class="text-right px-4 py-3 font-bold">Nominal</th>
                    <th class="text-right px-4 py-3 font-bold">Diterima</th>
                    <th class="text-left px-4 py-3 font-bold">Status</th>
                    <th class="text-left px-4 py-3 font-bold">Keterangan</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($withdrawals as $w)
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <code class="font-mono text-[11px] font-bold text-slate-800 dark:text-slate-200">{{ $w->reference }}</code>
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $w->created_at?->format('d M Y H:i') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $w->bank_code }} · {{ $w->maskedAccountNumber() }}</p>
                            <p class="text-[10px] text-slate-400">{{ $w->account_holder }}</p>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-200">{{ $rp($w->amount) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">
                            {{ $rp($w->net_amount) }}
                            @if ($w->fee > 0)
                                <p class="text-[10px] font-normal text-slate-400">biaya {{ $rp($w->fee) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full border font-bold whitespace-nowrap {{ $w->statusBadgeClass() }}">{{ $w->statusLabel() }}</span>
                            @if ($w->completed_at)
                                <p class="text-[10px] text-slate-400 mt-1">{{ $w->completed_at->format('d M Y H:i') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-[260px] text-slate-500 dark:text-slate-400">
                            @if ($w->note)
                                <p>{{ $w->note }}</p>
                            @endif
                            @if (in_array($w->status, ['rejected', 'failed'], true) && $w->admin_note)
                                <p class="text-rose-600 dark:text-rose-400 font-semibold">{{ $w->admin_note }}</p>
                            @endif
                            @if ($w->transfer_reference)
                                <p>Ref transfer: {{ $w->transfer_reference }}</p>
                            @endif
                            @if (! $w->note && ! $w->transfer_reference && ! in_array($w->status, ['rejected', 'failed'], true))
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($w->isPending() && $canManage)
                                <form method="POST" action="{{ panel_route('finance.withdrawals.cancel', $w) }}" onsubmit="return MikPay.confirmForm(event, 'Batalkan penarikan {{ $w->reference }}? Saldo akan kembali tersedia.', { title: 'Batalkan Penarikan', variant: 'warning', confirmText: 'Ya, batalkan' })">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-bold text-rose-600 hover:underline whitespace-nowrap">Batalkan</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-400">
                            <i class="fa-solid fa-receipt text-2xl mb-2 block text-slate-300 dark:text-slate-600"></i>
                            Belum ada riwayat penarikan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var feeFlat = {{ (int) $settings['fee_flat'] }};
    var feePct = {{ (float) $settings['fee_percent'] }};
    var amount = document.getElementById('withdrawAmount');
    var feeEl = document.getElementById('feePreview');
    var netEl = document.getElementById('netPreview');
    var fmt = function (v) { return 'Rp ' + Math.max(0, v).toLocaleString('id-ID'); };

    function update() {
        var a = parseInt(amount && amount.value, 10) || 0;
        var fee = a > 0 ? feeFlat + Math.round(a * feePct / 100) : 0;
        feeEl.textContent = fmt(fee);
        netEl.textContent = fmt(a - fee);
    }
    if (amount) {
        amount.addEventListener('input', update);
        update();
    }

    var maxBtn = document.getElementById('btnMaxAmount');
    if (maxBtn && amount) {
        maxBtn.addEventListener('click', function () {
            amount.value = maxBtn.dataset.max;
            update();
        });
    }

    var editBtn = document.getElementById('btnEditBank');
    var cancelBtn = document.getElementById('btnCancelBank');
    var form = document.getElementById('bankForm');
    var card = document.getElementById('bankCard');
    function toggleBank(edit) {
        if (form) form.classList.toggle('hidden', !edit);
        if (card) card.classList.toggle('hidden', edit);
        if (editBtn) editBtn.classList.toggle('hidden', edit);
    }
    document.querySelectorAll('[data-file-preview]').forEach(function (input) {
        var nameEl = document.getElementById(input.dataset.fileName);
        var preview = document.getElementById(input.dataset.filePreview);
        input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            if (!f) return;
            nameEl.textContent = f.name + ' (' + Math.ceil(f.size / 1024) + ' KB)';
            if (f.type.indexOf('image/') === 0) {
                preview.src = URL.createObjectURL(f);
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        });
    });

    if (editBtn) editBtn.addEventListener('click', function () { toggleBank(true); });
    if (cancelBtn) cancelBtn.addEventListener('click', function () { toggleBank(false); });

    var wf = document.getElementById('withdrawForm');
    if (wf) {
        wf.addEventListener('submit', function (e) {
            if (wf.dataset.confirmed !== '1') {
                e.preventDefault();
                var a = parseInt(amount.value, 10) || 0;
                var fee = feeFlat + Math.round(a * feePct / 100);
                var ask = window.MikPay && MikPay.confirm
                    ? MikPay.confirm({ title: 'Ajukan Penarikan', message: 'Tarik ' + fmt(a) + '? Dana diterima di rekening: ' + fmt(a - fee) + '.', confirmText: 'Ya, ajukan' })
                    : Promise.resolve(window.confirm('Tarik ' + fmt(a) + '?'));
                ask.then(function (ok) {
                    if (!ok) return;
                    wf.dataset.confirmed = '1';
                    wf.requestSubmit ? wf.requestSubmit() : wf.submit();
                });
                return;
            }
            var btn = wf.querySelector('button[type=submit]');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...'; }
        });
    }
})();
</script>
@endpush
