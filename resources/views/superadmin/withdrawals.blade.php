@extends('layouts.superadmin')

@section('title', 'Penarikan Dana Tenant — MikPay')
@section('heading', 'Penarikan Dana Tenant (Disbursement)')

@section('content')
@php
    $rp = fn ($v) => 'Rp '.number_format((int) $v, 0, ',', '.');
    $count = fn ($s) => (int) ($counts[$s]->c ?? 0);
    $total = fn ($s) => (int) ($counts[$s]->total ?? 0);
    $filters = [
        '' => 'Semua',
        'pending' => 'Menunggu',
        'processing' => 'Diproses',
        'completed' => 'Berhasil',
        'failed' => 'Gagal',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-indigo-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Saldo Xendit Platform</p>
        <p class="text-2xl font-extrabold text-indigo-600 mt-1">{{ $xenditBalance === null ? '—' : $rp($xenditBalance) }}</p>
        <p class="text-[10px] text-slate-500 mt-1">Saldo cash yang bisa didisburse.</p>
    </div>
    <div class="bg-white border border-amber-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Menunggu Konfirmasi</p>
        <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $count('pending') }}</p>
        <p class="text-[10px] text-slate-500 mt-1">{{ $rp($total('pending')) }}</p>
    </div>
    <div class="bg-white border border-sky-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-sky-700">Sedang Ditransfer</p>
        <p class="text-2xl font-extrabold text-sky-600 mt-1">{{ $count('processing') }}</p>
        <p class="text-[10px] text-slate-500 mt-1">{{ $rp($total('processing')) }}</p>
    </div>
    <div class="bg-white border border-emerald-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Berhasil</p>
        <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $count('completed') }}</p>
        <p class="text-[10px] text-slate-500 mt-1">{{ $rp($total('completed')) }}</p>
    </div>
</div>

{{-- Verifikasi rekening --}}
<div id="verifikasi-rekening" class="bg-white border {{ $pendingAccounts->isNotEmpty() ? 'border-amber-300' : 'border-slate-200' }} rounded-2xl shadow-sm mb-6 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-id-card text-indigo-600"></i> Verifikasi Rekening Tenant
            @if ($pendingAccounts->isNotEmpty())
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-500 text-white">{{ $pendingAccounts->count() }} menunggu</span>
            @endif
        </h2>
        <p class="text-[11px] text-slate-500 hidden sm:block">Cocokkan nama & nomor rekening dengan foto buku tabungan dan KTP sebelum menyetujui.</p>
    </div>

    @forelse ($pendingAccounts as $acc)
        <div class="px-5 py-4 border-b border-slate-100 last:border-0 grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
            <div class="lg:col-span-3 grid grid-cols-2 gap-2">
                @foreach ([
                    ['Buku tabungan', $acc->proof_path, $acc->proofIsPdf(), route('superadmin.withdrawals.accounts.proof', $acc)],
                    ['KTP', $acc->ktp_path, $acc->ktpIsPdf(), $acc->ktp_path ? route('superadmin.withdrawals.accounts.ktp', $acc) : null],
                ] as [$docLabel, $docPath, $docPdf, $docUrl])
                    <div>
                        @if ($docPath)
                            <a href="{{ $docUrl }}" target="_blank" class="block rounded-xl border border-slate-200 bg-slate-50 overflow-hidden h-28 hover:ring-2 hover:ring-indigo-400 transition">
                                @if ($docPdf)
                                    <span class="h-full flex flex-col items-center justify-center text-rose-600 text-xs font-bold"><i class="fa-solid fa-file-pdf text-3xl mb-1"></i>Lihat PDF</span>
                                @else
                                    <img src="{{ $docUrl }}" alt="{{ $docLabel }}" class="w-full h-full object-cover">
                                @endif
                            </a>
                        @else
                            <span class="h-28 rounded-xl border border-dashed border-rose-300 bg-rose-50 flex flex-col items-center justify-center text-rose-600 text-[10px] font-bold text-center px-1">
                                <i class="fa-solid fa-triangle-exclamation text-lg mb-1"></i>Belum diunggah
                            </span>
                        @endif
                        <p class="text-[10px] font-bold text-slate-500 mt-1 text-center">{{ $docLabel }}</p>
                    </div>
                @endforeach
            </div>
            <div class="lg:col-span-5 text-xs">
                <p class="font-semibold text-slate-800">{{ $acc->tenant->name ?? '—' }} <span class="text-slate-400 font-normal">· {{ $acc->tenant->subdomain ?? '' }}</span></p>
                <p class="text-[10px] text-slate-500">Diajukan {{ $acc->user->email ?? '—' }} · {{ $acc->created_at?->format('d M Y H:i') }}</p>
                <div class="mt-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $acc->bank_name ?: $acc->bank_code }}</p>
                    <p class="font-mono text-sm font-bold text-slate-900">{{ $acc->account_number }}</p>
                    <p class="text-slate-600">a.n. {{ $acc->account_holder }}</p>
                </div>
            </div>
            <div class="lg:col-span-4 flex flex-col gap-2">
                @if ($acc->ktp_path)
                    <form method="POST" action="{{ route('superadmin.withdrawals.accounts.verify', $acc) }}"
                          onsubmit="return MikPay.confirmForm(event, 'Verifikasi rekening {{ $acc->bank_code }} {{ $acc->account_number }} a.n. {{ addslashes($acc->account_holder) }}? Pastikan nama sesuai buku tabungan dan KTP.', { title: 'Verifikasi Rekening', confirmText: 'Ya, sesuai' })">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1 px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700">
                            <i class="fa-solid fa-check"></i> Setujui Rekening
                        </button>
                    </form>
                @else
                    <p class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-[11px] text-rose-700">Pengajuan lama tanpa KTP. Tolak dan minta tenant mengajukan ulang dengan foto KTP.</p>
                @endif
                <form method="POST" action="{{ route('superadmin.withdrawals.accounts.reject', $acc) }}" class="flex flex-wrap items-center gap-1">
                    @csrf
                    <input type="text" name="reason" required placeholder="Alasan tolak (mis. nama tidak sesuai)" class="flex-1 min-w-[180px] rounded-lg border border-slate-200 px-2 py-2 text-xs">
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-rose-600 text-white text-xs font-bold hover:bg-rose-700">
                        <i class="fa-solid fa-xmark"></i> Tolak
                    </button>
                </form>
            </div>
        </div>
    @empty
        <p class="px-5 py-6 text-center text-xs text-slate-400">Tidak ada pengajuan rekening yang menunggu verifikasi.</p>
    @endforelse

    @if ($recentAccounts->isNotEmpty())
        <details class="border-t border-slate-100">
            <summary class="px-5 py-3 text-[11px] font-bold text-slate-500 cursor-pointer">Riwayat verifikasi terakhir</summary>
            <div class="px-5 pb-4 space-y-1.5">
                @foreach ($recentAccounts as $acc)
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px]">
                        <span class="inline-flex px-2 py-0.5 rounded-full border font-bold {{ $acc->statusBadgeClass() }}">{{ $acc->statusLabel() }}</span>
                        <span class="font-semibold text-slate-700">{{ $acc->tenant->name ?? '—' }}</span>
                        <span class="text-slate-500">{{ $acc->bank_code }} {{ $acc->account_number }} a.n. {{ $acc->account_holder }}</span>
                        <a href="{{ route('superadmin.withdrawals.accounts.proof', $acc) }}" target="_blank" class="text-indigo-600 font-bold">bukti</a>
                        @if ($acc->ktp_path)
                            <a href="{{ route('superadmin.withdrawals.accounts.ktp', $acc) }}" target="_blank" class="text-indigo-600 font-bold">KTP</a>
                        @endif
                        <span class="text-slate-400">{{ $acc->reviewed_by }} · {{ $acc->reviewed_at?->format('d M H:i') }}</span>
                        @if ($acc->reject_reason)
                            <span class="text-rose-600">— {{ $acc->reject_reason }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </details>
    @endif
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-6 p-5">
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-5">
        <div class="lg:max-w-md">
            <h3 class="text-sm font-bold text-slate-800"><i class="fa-solid fa-store mr-2 text-purple-600"></i>Biaya Layanan Toko Voucher</h3>
            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                Nominal tetap per transaksi yang masuk utuh ke MikPay. Ditanggung pembeli bersama biaya QRIS Xendit, dan tampil ke pembeli sebagai satu baris "Biaya layanan".
                Contoh: voucher {{ $rp($voucherFee['example_price']) }} → pembeli bayar <b>{{ $rp($voucherFee['example_price'] + $voucherFee['example_fee']) }}</b>
                (tenant {{ $rp($voucherFee['example_price']) }}, MikPay {{ $rp($voucherFee['amount']) }}, sisanya potongan Xendit).
            </p>
            <form method="POST" action="{{ route('superadmin.withdrawals.voucher-fee') }}" class="mt-3 flex items-end gap-2">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Biaya layanan / transaksi (Rp)</label>
                    <input type="number" name="voucher_service_fee" min="0" max="100000" step="1" value="{{ old('voucher_service_fee', $voucherFee['amount']) }}" class="w-40 rounded-lg border border-slate-200 px-3 py-2 text-xs">
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700">Simpan</button>
            </form>
        </div>
        <div class="grid grid-cols-3 gap-3 flex-1 lg:max-w-xl">
            @foreach ([['Hari ini', $voucherFee['today']], ['Bulan ini', $voucherFee['month']], ['Total', $voucherFee['total']]] as [$label, $value])
                <div class="rounded-xl bg-purple-50 border border-purple-100 px-3 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-purple-500">{{ $label }}</p>
                    <p class="text-base font-bold text-slate-900 mt-0.5">{{ $rp($value) }}</p>
                </div>
            @endforeach
            <p class="col-span-3 text-[11px] text-slate-500">Pendapatan biaya layanan dari {{ number_format($voucherFee['count'], 0, ',', '.') }} transaksi voucher lunas. Uangnya tetap di saldo Xendit MikPay (tidak masuk saldo tenant).</p>
        </div>
    </div>
</div>

<details class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-6 group" @if ($errors->hasAny(['min_amount', 'fee_flat', 'fee_percent', 'hold_days'])) open @endif>
    <summary class="px-5 py-4 cursor-pointer flex items-center justify-between text-sm font-bold text-slate-800">
        <span><i class="fa-solid fa-sliders mr-2 text-indigo-600"></i>Pengaturan Penarikan</span>
        <span class="text-[11px] font-semibold text-slate-500">
            {{ $settings['enabled'] ? 'Aktif' : 'Nonaktif' }} · min {{ $rp($settings['min_amount']) }} · biaya {{ $rp($settings['fee_flat']) }}{{ $settings['fee_percent'] > 0 ? ' + '.$settings['fee_percent'].'%' : '' }} · tahan {{ $settings['hold_days'] }} hari
        </span>
    </summary>
    <form method="POST" action="{{ route('superadmin.withdrawals.settings') }}" class="px-5 pb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end border-t border-slate-100 pt-4">
        @csrf
        <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
            <input type="checkbox" name="enabled" value="1" class="rounded" @checked($settings['enabled'])> Fitur penarikan aktif
        </label>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Minimal penarikan (Rp)</label>
            <input type="number" name="min_amount" min="10000" value="{{ old('min_amount', $settings['min_amount']) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Biaya tetap / penarikan (Rp)</label>
            <input type="number" name="fee_flat" min="0" value="{{ old('fee_flat', $settings['fee_flat']) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Biaya persen (%)</label>
            <input type="number" name="fee_percent" min="0" max="20" step="0.01" value="{{ old('fee_percent', $settings['fee_percent']) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Masa tahan settlement (hari)</label>
            <input type="number" name="hold_days" min="0" max="30" value="{{ old('hold_days', $settings['hold_days']) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs">
        </div>
        <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-[11px] text-slate-500 max-w-3xl">
                Biaya dipotong dari nominal yang ditarik tenant (untuk menutup biaya disbursement Xendit ± Rp2.775/transfer).
                Masa tahan menahan pembayaran yang belum lewat N hari agar menunggu settlement Xendit.
                Callback disbursement: set URL <code class="font-mono bg-slate-100 px-1 rounded">{{ route('webhooks.xendit') }}</code> di Xendit Dashboard → Settings → Webhooks → Disbursement.
            </p>
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700">
                <i class="fa-solid fa-floppy-disk"></i> Simpan
            </button>
        </div>
    </form>
</details>

<div class="flex flex-wrap gap-1.5 mb-4">
    @foreach ($filters as $key => $label)
        <a href="{{ route('superadmin.withdrawals', $key !== '' ? ['status' => $key] : []) }}"
           class="text-xs font-bold px-3 py-1.5 rounded-lg border transition {{ $status === $key ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300' }}">
            {{ $label }}
            @if ($key !== '' && $count($key) > 0)
                <span class="ml-1 opacity-70">{{ $count($key) }}</span>
            @endif
        </a>
    @endforeach
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3 font-bold">Referensi</th>
                    <th class="text-left px-4 py-3 font-bold">Tenant</th>
                    <th class="text-left px-4 py-3 font-bold">Rekening Tujuan</th>
                    <th class="text-right px-4 py-3 font-bold">Nominal</th>
                    <th class="text-left px-4 py-3 font-bold">Status</th>
                    <th class="text-left px-4 py-3 font-bold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($withdrawals as $w)
                    @php $bal = $balances[$w->tenant_id] ?? null; @endphp
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <code class="font-mono text-[11px] font-bold text-slate-800">{{ $w->reference }}</code>
                            <p class="text-[10px] text-slate-400 mt-1">{{ $w->created_at?->format('d M Y H:i') }}</p>
                            @if ($w->note)
                                <p class="text-[10px] text-slate-500 mt-1 max-w-[160px]">“{{ $w->note }}”</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800">{{ $w->tenant->name ?? '—' }}</p>
                            <p class="text-[10px] text-slate-500">{{ $w->tenant->subdomain ?? '' }} · {{ $w->user->email ?? '—' }}</p>
                            @if ($bal && $w->isPending())
                                <p class="text-[10px] text-slate-500 mt-1">
                                    Diterima {{ $rp($bal['collected']) }} · ditarik {{ $rp($bal['withdrawn']) }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800">{{ $w->bank_name ?: $w->bank_code }}</p>
                            <p class="font-mono text-[11px] text-slate-700">{{ $w->account_number }}</p>
                            <p class="text-[10px] text-slate-500">a.n. {{ $w->account_holder }}</p>
                            @if ($w->payout_account_id)
                                <a href="{{ route('superadmin.withdrawals.accounts.proof', $w->payout_account_id) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700">
                                    <i class="fa-solid fa-circle-check"></i> Rekening terverifikasi · lihat buku tabungan
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <p class="font-bold text-slate-900">{{ $rp($w->net_amount) }}</p>
                            <p class="text-[10px] text-slate-500">diajukan {{ $rp($w->amount) }}</p>
                            @if ($w->fee > 0)
                                <p class="text-[10px] text-slate-500">biaya {{ $rp($w->fee) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full border font-bold whitespace-nowrap {{ $w->statusBadgeClass() }}">{{ $w->statusLabel() }}</span>
                            @if ($w->method)
                                <p class="text-[10px] text-slate-500 mt-1">via {{ $w->method === 'xendit' ? 'Xendit' : 'manual' }}{{ $w->xendit_status ? ' · '.$w->xendit_status : '' }}</p>
                            @endif
                            @if ($w->reviewed_by)
                                <p class="text-[10px] text-slate-400">{{ $w->reviewed_by }} · {{ $w->reviewed_at?->format('d M H:i') }}</p>
                            @endif
                            @if ($w->transfer_reference)
                                <p class="text-[10px] text-slate-500">Ref: {{ $w->transfer_reference }}</p>
                            @endif
                            @if ($w->admin_note)
                                <p class="text-[10px] mt-1 max-w-[200px] {{ in_array($w->status, ['failed', 'rejected'], true) ? 'text-rose-600' : 'text-slate-500' }}">{{ $w->admin_note }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 min-w-[260px]">
                            @if ($w->isPending())
                                <div class="flex flex-wrap gap-1.5 mb-2">
                                    <form method="POST" action="{{ route('superadmin.withdrawals.xendit', $w) }}"
                                          onsubmit="return MikPay.confirmForm(event, 'Kirim {{ $rp($w->net_amount) }} ke {{ $w->bank_code }} {{ $w->account_number }} a.n. {{ addslashes($w->account_holder) }} via Xendit sekarang?', { title: 'Setujui & Kirim', confirmText: 'Ya, kirim' })">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700">
                                            <i class="fa-solid fa-paper-plane"></i> Setujui & Kirim (Xendit)
                                        </button>
                                    </form>
                                </div>
                                <form method="POST" action="{{ route('superadmin.withdrawals.manual', $w) }}" class="flex flex-wrap items-center gap-1 mb-2"
                                      onsubmit="return MikPay.confirmForm(event, 'Tandai {{ $w->reference }} sudah ditransfer manual? Pastikan dana sudah benar-benar dikirim.', { title: 'Transfer Manual', variant: 'warning', confirmText: 'Ya, sudah' })">
                                    @csrf
                                    <input type="text" name="transfer_reference" placeholder="No. ref transfer (opsional)" class="rounded-lg border border-slate-200 px-2 py-1.5 w-40">
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-800 text-white font-bold hover:bg-slate-900">
                                        <i class="fa-solid fa-hand-holding-dollar"></i> Manual
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.withdrawals.reject', $w) }}" class="flex flex-wrap items-center gap-1">
                                    @csrf
                                    <input type="text" name="reason" required placeholder="Alasan tolak" class="rounded-lg border border-slate-200 px-2 py-1.5 w-40">
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-600 text-white font-bold hover:bg-rose-700">
                                        <i class="fa-solid fa-xmark"></i> Tolak
                                    </button>
                                </form>
                            @elseif ($w->isProcessing() && $w->method === 'xendit')
                                <form method="POST" action="{{ route('superadmin.withdrawals.refresh', $w) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-sky-600 text-white font-bold hover:bg-sky-700">
                                        <i class="fa-solid fa-rotate"></i> Cek Status Xendit
                                    </button>
                                </form>
                            @else
                                <span class="text-slate-400">Selesai diproses</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada permintaan penarikan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($withdrawals->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $withdrawals->links() }}</div>
    @endif
</div>
@endsection
