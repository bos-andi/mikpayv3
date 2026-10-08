@extends('layouts.partner')

@section('title', 'Pencairan')
@section('heading', 'Pencairan Komisi')
@section('subheading', 'Tarik komisi ke rekening bank / e-wallet Anda')

@php
    $rp = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
    $input = 'w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition';
    $min = (int) $settings['min_withdrawal'];
    $canWithdraw = $partner->isActive() && $partner->hasVerifiedBank() && $balance['available'] >= $min;
    $showBankForm = ! $partner->bank_status || $partner->bank_status === \App\Models\Partner::BANK_REJECTED || $errors->hasAny(['bank_code', 'account_number', 'account_holder', 'proof', 'ktp', 'current_password']);
@endphp

@section('content')
<div class="grid lg:grid-cols-5 gap-5">
    {{-- Balance + withdraw --}}
    <div class="lg:col-span-3 space-y-5">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-500 via-teal-600 to-slate-900 text-white p-6 shadow-xl shadow-emerald-900/20">
            <div class="absolute inset-0 bg-grid-dark opacity-30"></div>
            <div class="absolute -right-10 -top-10 w-48 h-48 rounded-full bg-white/10"></div>
            <div class="relative">
                <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-100">Komisi bisa ditarik</p>
                <p class="mt-1 text-4xl font-extrabold tracking-tight">{{ $rp($balance['available']) }}</p>
                <div class="mt-5 grid grid-cols-3 gap-3 text-xs">
                    <div class="rounded-xl bg-white/10 border border-white/15 p-3">
                        <p class="text-emerald-100">Tertahan</p>
                        <p class="font-extrabold text-sm">{{ $rp($balance['on_hold']) }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 border border-white/15 p-3">
                        <p class="text-emerald-100">Diproses</p>
                        <p class="font-extrabold text-sm">{{ $rp($balance['in_progress']) }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 border border-white/15 p-3">
                        <p class="text-emerald-100">Dicairkan</p>
                        <p class="font-extrabold text-sm">{{ $rp($balance['withdrawn']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5">
            <h3 class="font-extrabold text-slate-900">Ajukan Pencairan</h3>
            <p class="text-xs text-slate-500 mt-0.5">Minimal {{ $rp($min) }} · tanpa potongan biaya · dikirim otomatis ke rekening Anda.</p>
            <div class="mt-3 flex items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3">
                <span class="w-10 h-10 shrink-0 rounded-xl bg-white text-indigo-600 border border-indigo-100 flex flex-col items-center justify-center leading-none">
                    <span class="text-[8px] font-bold uppercase">{{ $nextPayoutDate ? $nextPayoutDate->translatedFormat('M') : '' }}</span>
                    <span class="text-sm font-extrabold">{{ $nextPayoutDate ? $nextPayoutDate->format('d') : '—' }}</span>
                </span>
                <p class="text-xs text-indigo-900 leading-relaxed">
                    @if ($nextPayoutDate)
                        Pencairan diproses <b>setiap tanggal {{ (int) $settings['payout_day'] }}</b>. Ajukan sebelum tanggal tersebut, dana dikirim pada <b>{{ $nextPayoutDate->translatedFormat('d F Y') }}</b>.
                    @else
                        Pengajuan pencairan diproses admin MikPay secepatnya pada hari kerja.
                    @endif
                </p>
            </div>

            @if (! $partner->isActive())
                <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800">Pencairan tersedia setelah akun affiliate aktif.</div>
            @elseif (! $partner->hasVerifiedBank())
                <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800 flex gap-2">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                    <span>{{ $partner->bank_status === 'pending' ? 'Rekening Anda sedang diverifikasi admin. Pencairan bisa diajukan setelah terverifikasi.' : 'Lengkapi rekening pencairan terlebih dahulu (formulir di samping).' }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('partner.withdrawals.store') }}" class="mt-4 space-y-3" id="withdrawForm">
                @csrf
                <div>
                    <label for="amount" class="block text-xs font-bold text-slate-700 mb-1.5">Nominal</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">Rp</span>
                        <input id="amount" type="number" name="amount" min="{{ $min }}" max="{{ $balance['available'] }}" step="1000" value="{{ old('amount', $balance['available'] >= $min ? $balance['available'] : '') }}" @disabled(! $canWithdraw) required class="{{ $input }} pl-10 font-bold disabled:opacity-60">
                    </div>
                    <button type="button" @disabled(! $canWithdraw) onclick="document.getElementById('amount').value = {{ $balance['available'] }}" class="mt-1.5 text-[11px] font-bold text-indigo-600 hover:underline disabled:opacity-50">Tarik semua</button>
                </div>
                <div>
                    <label for="note" class="block text-xs font-bold text-slate-700 mb-1.5">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input id="note" name="note" value="{{ old('note') }}" maxlength="500" @disabled(! $canWithdraw) class="{{ $input }} disabled:opacity-60">
                </div>
                @if ($partner->hasVerifiedBank())
                    <p class="text-[11px] text-slate-500"><i class="fa-solid fa-building-columns mr-1"></i> Dikirim ke <b>{{ $partner->bank_name }}</b> · {{ $partner->account_number }} a.n. <b>{{ $partner->account_holder }}</b></p>
                @endif
                <button type="submit" @disabled(! $canWithdraw) class="w-full inline-flex items-center justify-center gap-2 rounded-xl gradient-bg text-white text-sm font-bold py-3 shadow-md shadow-indigo-500/20 hover:opacity-95 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-paper-plane"></i> Ajukan Pencairan
                </button>
                @if ($partner->isActive() && $partner->hasVerifiedBank() && $balance['available'] < $min)
                    <p class="text-[11px] text-slate-500 text-center">Saldo belum mencapai minimal pencairan {{ $rp($min) }}.</p>
                @endif
            </form>
        </div>
    </div>

    {{-- Bank account --}}
    <div class="lg:col-span-2">
        <div class="rounded-2xl bg-white border border-slate-200 p-5">
            <div class="flex items-center justify-between gap-2">
                <h3 class="font-extrabold text-slate-900">Rekening Pencairan</h3>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $partner->bankStatusBadgeClass() }}">{{ $partner->bankStatusLabel() }}</span>
            </div>

            @if ($partner->bank_status)
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] text-slate-500 font-semibold">{{ $partner->bank_name }}</p>
                    <p class="font-mono text-lg font-extrabold text-slate-900 tracking-wide">{{ $partner->account_number }}</p>
                    <p class="text-xs text-slate-600">a.n. <b>{{ $partner->account_holder }}</b></p>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                        @if ($partner->bank_proof_path)
                            <a href="{{ route('partner.withdrawals.bank.proof') }}" target="_blank" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-indigo-600 hover:underline"><i class="fa-solid fa-book"></i> Buku tabungan</a>
                        @endif
                        @if ($partner->ktp_path)
                            <a href="{{ route('partner.withdrawals.bank.ktp') }}" target="_blank" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-indigo-600 hover:underline"><i class="fa-solid fa-id-card"></i> KTP</a>
                        @endif
                    </div>
                </div>
                @if ($partner->bank_status === 'rejected' && $partner->bank_reject_reason)
                    <p class="mt-3 text-xs text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-3 py-2"><b>Ditolak:</b> {{ $partner->bank_reject_reason }}</p>
                @endif
                @unless ($showBankForm)
                    <button type="button" onclick="document.getElementById('bankForm').classList.toggle('hidden')" class="mt-3 text-xs font-bold text-indigo-600 hover:underline"><i class="fa-solid fa-pen-to-square"></i> Ganti rekening</button>
                @endunless
            @else
                <p class="mt-2 text-xs text-slate-500">Rekening harus atas nama Anda sendiri. Siapkan <b>foto buku tabungan</b> (atau screenshot akun e-wallet) dan <b>foto KTP</b> — nama di keduanya harus sama.</p>
            @endif

            <form method="POST" action="{{ route('partner.withdrawals.bank') }}" enctype="multipart/form-data" id="bankForm" class="mt-4 space-y-3 {{ $showBankForm ? '' : 'hidden' }}">
                @csrf
                <div>
                    <label for="bank_code" class="block text-xs font-bold text-slate-700 mb-1.5">Bank / e-wallet</label>
                    <select id="bank_code" name="bank_code" required class="{{ $input }}">
                        <option value="">Pilih…</option>
                        @foreach ($banks as $code => $name)
                            <option value="{{ $code }}" @selected(old('bank_code', $partner->bank_code) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="account_number" class="block text-xs font-bold text-slate-700 mb-1.5">Nomor rekening / HP e-wallet</label>
                    <input id="account_number" name="account_number" value="{{ old('account_number') }}" inputmode="numeric" required class="{{ $input }} font-mono">
                </div>
                <div>
                    <label for="account_holder" class="block text-xs font-bold text-slate-700 mb-1.5">Nama pemilik rekening</label>
                    <input id="account_holder" name="account_holder" value="{{ old('account_holder', auth()->user()->name) }}" required class="{{ $input }} uppercase">
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-3">
                    <div>
                        <label for="proof" class="block text-xs font-bold text-slate-700 mb-1.5"><i class="fa-solid fa-book text-indigo-500 mr-1"></i> Foto buku tabungan / screenshot akun <span class="text-rose-600">*</span></label>
                        <input id="proof" type="file" name="proof" accept="image/*,application/pdf" required class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="text-[10px] text-slate-400 mt-1">Halaman yang menampilkan nama & nomor rekening.</p>
                    </div>
                    <div>
                        <label for="ktp" class="block text-xs font-bold text-slate-700 mb-1.5"><i class="fa-solid fa-id-card text-indigo-500 mr-1"></i> Foto KTP <span class="text-rose-600">*</span></label>
                        <input id="ktp" type="file" name="ktp" accept="image/*,application/pdf" required class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="text-[10px] text-slate-400 mt-1">Foto jelas, tidak buram, seluruh kartu terlihat.</p>
                    </div>
                    <p class="text-[10px] text-slate-500 leading-relaxed"><i class="fa-solid fa-lock mr-1"></i> JPG, PNG, WEBP, atau PDF · maks 5 MB per file. Dokumen disimpan privat dan hanya dilihat admin MikPay untuk verifikasi.</p>
                </div>
                <x-password-input id="current_password" name="current_password" label="Password akun (konfirmasi)" autocomplete="current-password" required />
                <button type="submit" class="w-full rounded-xl bg-slate-900 text-white text-sm font-bold py-2.5 hover:bg-slate-800">
                    <i class="fa-solid fa-shield-halved mr-1"></i> Kirim untuk Verifikasi
                </button>
            </form>
        </div>
    </div>
</div>

<div class="mt-5 rounded-2xl bg-white border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-extrabold text-slate-900">Riwayat Pencairan</h3>
    </div>
    @if ($withdrawals->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pencairan.</div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">Referensi</th>
                        <th class="px-5 py-3 text-left font-bold">Tanggal</th>
                        <th class="px-5 py-3 text-left font-bold">Tujuan</th>
                        <th class="px-5 py-3 text-right font-bold">Nominal</th>
                        <th class="px-5 py-3 text-left font-bold">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($withdrawals as $w)
                        <tr>
                            <td class="px-5 py-3 font-mono text-xs font-bold text-slate-700">{{ $w->reference }}</td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">{{ format_wib($w->created_at) }}</td>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ $w->bank_code }} · {{ $w->maskedAccount() }}</td>
                            <td class="px-5 py-3 text-right font-extrabold text-slate-900 whitespace-nowrap">{{ $rp($w->amount) }}</td>
                            <td class="px-5 py-3">
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $w->statusBadgeClass() }}">{{ $w->statusLabel() }}</span>
                                @if ($w->transfer_reference)
                                    <p class="text-[10px] text-slate-400 mt-0.5">Ref: {{ $w->transfer_reference }}</p>
                                @endif
                                @if ($w->admin_note && in_array($w->status, ['rejected', 'failed'], true))
                                    <p class="text-[10px] text-rose-500 mt-0.5">{{ $w->admin_note }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($w->isPending())
                                    <form method="POST" action="{{ route('partner.withdrawals.cancel', $w) }}" onsubmit="return confirm('Batalkan pengajuan pencairan ini?')">
                                        @csrf
                                        <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Batalkan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
