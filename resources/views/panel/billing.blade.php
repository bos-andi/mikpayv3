@extends('layouts.panel')

@section('title', 'Billing & Tagihan — MikPay')
@section('page', 'billing')

@section('content')
@php
    $billingUrl = function (?string $filterKey = null) use ($q, $filter) {
        $params = array_filter([
            'q' => $q !== '' ? $q : null,
            'filter' => ($filterKey ?? $filter) !== 'all' ? ($filterKey ?? $filter) : null,
        ], fn ($v) => $v !== null && $v !== '');
        $base = panel_route('billing');

        return $params ? $base.'?'.http_build_query($params) : $base;
    };

    $statusClass = [
        'overdue' => 'bg-rose-500/10 text-rose-600',
        'due_today' => 'bg-amber-500/10 text-amber-600',
        'due_soon' => 'bg-orange-500/10 text-orange-600',
        'partial' => 'bg-sky-500/10 text-sky-600',
        'waiting' => 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-300',
        'paid' => 'bg-emerald-500/10 text-emerald-600',
        'not_set' => 'bg-amber-500/10 text-amber-700',
    ];
@endphp

<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Billing & Tagihan</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
            Periode <span class="font-semibold text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}</span>
            <span class="hidden sm:inline"> · Router <span class="font-semibold text-indigo-600">{{ $router->displayName() }}</span></span>
            <span class="block sm:inline"> · Tunggakan multi-bulan + auto isolir</span>
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <form method="POST" action="{{ panel_route('billing.auto-isolir') }}" onsubmit="return MikPay.confirmForm(event, 'Jalankan auto isolir untuk semua pelanggan yang menunggak di router ini?', { title: 'Auto Isolir', variant: 'warning', confirmText: 'Ya, isolir' })">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 text-xs font-bold px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white shadow-md shadow-rose-600/20">
                <i class="fa-solid fa-lock"></i> Jalankan Auto Isolir
            </button>
        </form>
        <a href="{{ panel_route('customers') }}" class="inline-flex items-center gap-2 text-xs font-bold px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200">
            <i class="fa-solid fa-users"></i> Atur Data Pelanggan
        </a>
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
@if (! $fetchOk)
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-500/10 dark:border-amber-500/30 px-4 py-3 text-xs font-semibold text-amber-800 dark:text-amber-300">
        {{ $fetchMessage }}
    </div>
@endif
@if ($trackingHint ?? false)
    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-4 py-3 text-[11px] text-slate-600 dark:text-slate-300">
        Mulai pencatatan tunggakan belum di-set (env <code class="font-mono">MIKPAY_BILLING_TRACKING_START</code>). Saat ini hanya bulan berjalan yang dihitung — aman untuk instal baru.
    </div>
@endif

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-3">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm min-w-0">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total</p>
        <p class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white mt-1 tabular-nums">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm min-w-0">
        <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Menunggak</p>
        <p class="text-xl sm:text-2xl font-extrabold text-rose-600 mt-1 tabular-nums">{{ $stats['menunggak'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm min-w-0">
        <p class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Hari ini</p>
        <p class="text-xl sm:text-2xl font-extrabold text-amber-600 mt-1 tabular-nums">{{ $stats['due_today'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm min-w-0">
        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-500">Lunas</p>
        <p class="text-xl sm:text-2xl font-extrabold text-emerald-600 mt-1 tabular-nums">{{ $stats['paid'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm min-w-0 col-span-2 sm:col-span-1">
        <p class="text-[10px] font-bold uppercase tracking-wider text-rose-400">Outstanding</p>
        <p class="text-sm sm:text-base font-extrabold text-rose-600 mt-1 tabular-nums leading-snug break-words">Rp {{ number_format($stats['outstanding_total'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm min-w-0 col-span-2 sm:col-span-1">
        <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">Pemasukan periode</p>
        <p class="text-sm sm:text-base font-extrabold text-indigo-600 mt-1 tabular-nums leading-snug break-words">Rp {{ number_format($stats['period_income'], 0, ',', '.') }}</p>
    </div>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row gap-3 sm:items-center">
    <form method="GET" action="{{ panel_route('billing') }}" class="flex-1 flex flex-col sm:flex-row gap-2">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari PPPoE, nama, HP, zona..."
                   class="w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs focus:outline-none focus:border-indigo-500">
        </div>
        @if ($filter !== 'all')
            <input type="hidden" name="filter" value="{{ $filter }}">
        @endif
        <button type="submit" class="text-xs font-bold px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900">Cari</button>
    </form>
    <div class="flex flex-wrap gap-1.5">
        @foreach ([
            'all' => 'Semua',
            'menunggak' => 'Menunggak',
            'overdue' => 'Terlambat',
            'due_today' => 'Hari Ini',
            'due_soon' => 'Segera',
            'partial' => 'Sebagian',
            'paid' => 'Lunas',
            'not_set' => 'Belum diatur',
        ] as $key => $label)
            <a href="{{ $billingUrl($key) }}"
               class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg border transition {{ $filter === $key ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tagihan & tunggakan</h3>
            <span class="text-[10px] text-slate-400">{{ $rows->count() }} baris</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[1000px]">
                <thead class="bg-slate-100 dark:bg-slate-950 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Pelanggan</th>
                        <th class="p-3">Paket / Tarif</th>
                        <th class="p-3">Jatuh tempo</th>
                        <th class="p-3">Tunggakan</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="p-3">
                                <p class="font-bold text-slate-900 dark:text-slate-100">{{ $r['display_name'] ?: $r['name'] }}</p>
                                <p class="font-mono text-[10px] text-slate-400">{{ $r['name'] }}</p>
                                @if ($r['phone'])
                                    <p class="text-[10px] text-slate-500 mt-0.5">{{ $r['phone'] }}</p>
                                @endif
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 font-semibold">
                                    {{ $r['package_label'] ?: '-' }}
                                </span>
                                <p class="font-mono text-[10px] mt-1">
                                    @if (!empty($r['has_period_discount']))
                                        <span class="text-amber-700 dark:text-amber-400 font-bold" title="Harga akhir periode {{ $period }}">
                                            Rp {{ number_format($r['period_fee'], 0, ',', '.') }}
                                        </span>
                                        @if (($r['monthly_fee'] ?? 0) > 0 && (int) $r['period_fee'] !== (int) $r['monthly_fee'])
                                            <span class="block text-slate-400 line-through">Rp {{ number_format($r['monthly_fee'], 0, ',', '.') }}</span>
                                        @endif
                                        <span class="block text-[9px] font-bold text-amber-600 mt-0.5">DISKON BLN INI</span>
                                    @elseif (($r['monthly_fee'] ?? 0) > 0)
                                        Rp {{ number_format($r['monthly_fee'], 0, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </p>
                            </td>
                            <td class="p-3">
                                <p class="font-semibold">{{ $r['due_date_label'] ?: '—' }}</p>
                                <p class="text-[10px] text-slate-400">{{ $r['message'] }}</p>
                            </td>
                            <td class="p-3">
                                @if (($r['arrears_count'] ?? 0) > 0)
                                    <p class="font-bold text-rose-600" title="{{ $r['arrears_tooltip'] }}">{{ $r['arrears_label'] }}</p>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach (($r['arrears_period_labels'] ?? []) as $i => $pl)
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-600" title="{{ $r['arrears_periods'][$i] ?? '' }}">{{ $pl }}</span>
                                        @endforeach
                                    </div>
                                    @if (($r['outstanding_total'] ?? 0) > 0)
                                        <p class="text-[10px] text-slate-500 mt-1">Total sisa Rp {{ number_format($r['outstanding_total'], 0, ',', '.') }}</p>
                                    @endif
                                @else
                                    <span class="text-[10px] font-bold text-emerald-600">{{ $r['billing_configured'] ? 'Lunas' : '—' }}</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded-full font-bold text-[10px] {{ $statusClass[$r['status']] ?? $statusClass['waiting'] }}">
                                    {{ strtoupper($r['status_label']) }}
                                </span>
                                @if ($r['status'] === 'partial' && ($r['remaining'] ?? 0) > 0)
                                    <p class="text-[10px] font-semibold text-sky-600 mt-1">Sisa Rp {{ number_format($r['remaining'], 0, ',', '.') }}</p>
                                @endif
                                @if ($r['isolir'] || $r['disabled'])
                                    <span class="ml-1 px-2 py-0.5 rounded-full font-bold text-[10px] bg-rose-500/10 text-rose-600">ISOLIR</span>
                                @elseif (!empty($r['promise_active']) && !empty($r['promised_pay_date']))
                                    <span class="ml-1 px-2 py-0.5 rounded-full font-bold text-[10px] bg-violet-500/10 text-violet-700" title="{{ $r['promised_pay_note'] ?? ($r['isolir_label'] ?? '') }}">
                                        JANJI {{ \Carbon\Carbon::parse($r['promised_pay_date'])->translatedFormat('d M') }}
                                    </span>
                                @elseif (!empty($r['promise_overdue']))
                                    <span class="ml-1 px-2 py-0.5 rounded-full font-bold text-[10px] bg-rose-500/10 text-rose-600" title="{{ $r['isolir_label'] ?? '' }}">JANJI LEWAT</span>
                                    @if ($r['should_isolir'])
                                        <span class="ml-1 px-2 py-0.5 rounded-full font-bold text-[10px] bg-amber-500/10 text-amber-700">SIAP ISOLIR</span>
                                    @endif
                                @elseif ($r['should_isolir'])
                                    <span class="ml-1 px-2 py-0.5 rounded-full font-bold text-[10px] bg-amber-500/10 text-amber-700">SIAP ISOLIR</span>
                                @elseif (($r['days_to_isolir'] ?? null) !== null && ($r['arrears_count'] ?? 0) > 0)
                                    <span class="ml-1 px-2 py-0.5 rounded-full font-bold text-[10px] bg-amber-500/10 text-amber-700" title="{{ $r['isolir_label'] ?? '' }}">
                                        {{ strtoupper($r['isolir_label'] ?: ($r['days_to_isolir'].' hari lagi siap isolir')) }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    @if ($r['status'] !== 'paid' || ($r['arrears_count'] ?? 0) > 0)
                                        <button
                                            type="button"
                                            class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white"
                                            onclick="openPayModal(@js($r))"
                                        >
                                            Bayar
                                        </button>
                                        @if (($r['outstanding_total'] ?? 0) > 0 && ($r['billing_configured'] ?? false))
                                            <button
                                                type="button"
                                                class="p-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 rounded text-indigo-600"
                                                title="Buat & salin link bayar"
                                                onclick="copyPaymentLink(@js($r['name']), this)"
                                            >
                                                <i class="fa-solid fa-link"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="p-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 rounded text-emerald-600"
                                                title="Kirim reminder WA + link bayar"
                                                @disabled(! $r['phone'])
                                                onclick="sendPaymentLinkWa(@js($r['name']), @js($r['phone'] ?: ''), this)"
                                            >
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="p-1.5 {{ !empty($r['is_promised']) ? 'bg-violet-500/20 text-violet-700' : 'bg-violet-500/10 text-violet-600' }} hover:bg-violet-500/20 rounded"
                                                title="{{ !empty($r['promised_pay_date']) ? 'Janji bayar: '.$r['promised_pay_date'] : 'Janji bayar (tunda isolir)' }}"
                                                onclick="openPromiseModal(@js($r))"
                                            >
                                                <i class="fa-regular fa-calendar-check"></i>
                                            </button>
                                        @endif
                                    @endif
                                    <button
                                        type="button"
                                        class="p-1.5 {{ !empty($r['has_period_discount']) ? 'bg-amber-500/20 text-amber-700' : 'bg-amber-500/10 text-amber-600' }} hover:bg-amber-500/20 rounded"
                                        title="{{ !empty($r['has_period_discount']) ? 'Harga akhir bulan ini: Rp '.number_format($r['period_fee'], 0, ',', '.') : 'Set harga akhir untuk bulan tertentu' }}"
                                        onclick="openPeriodFeeModal(@js($r))"
                                    >
                                        <i class="fa-solid fa-tags"></i>
                                    </button>
                                    @if ($r['isolir'] || $r['disabled'])
                                        <form method="POST" action="{{ panel_route('billing.open') }}" onsubmit="return MikPay.confirmForm(event, 'Buka isolir {{ $r['name'] }}?', { title: 'Buka Isolir', confirmText: 'Ya, buka' })">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $r['id'] }}">
                                            <input type="hidden" name="name" value="{{ $r['name'] }}">
                                            <button type="submit" class="p-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 rounded text-emerald-600" title="Buka Isolir">
                                                <i class="fa-solid fa-lock-open"></i>
                                            </button>
                                        </form>
                                    @elseif ($r['id'])
                                        <form method="POST" action="{{ panel_route('billing.isolir') }}" onsubmit="return MikPay.confirmForm(event, 'Isolir {{ $r['name'] }} sekarang?', { title: 'Isolir Pelanggan', variant: 'warning', confirmText: 'Ya, isolir' })">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $r['id'] }}">
                                            <input type="hidden" name="name" value="{{ $r['name'] }}">
                                            <button type="submit" class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 rounded text-rose-600" title="Isolir manual">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ panel_route('customers.edit') }}?name={{ urlencode($r['name']) }}"
                                       class="p-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 rounded text-indigo-600" title="Edit data">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-slate-500">Tidak ada data untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pembayaran terbaru</h3>
        <span class="text-[10px] text-slate-400">{{ $recentPayments->count() }} entri</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs min-w-[720px]">
            <thead class="bg-slate-100 dark:bg-slate-950 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="p-3">Pelanggan</th>
                    <th class="p-3">Invoice / Periode</th>
                    <th class="p-3">Nominal</th>
                    <th class="p-3">Tanggal</th>
                    <th class="p-3">Metode</th>
                    <th class="p-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                @forelse ($recentPayments as $p)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td class="p-3">
                            <p class="font-bold text-slate-900 dark:text-slate-100">{{ $p->display_name ?: $p->customer_name }}</p>
                            <p class="font-mono text-[10px] text-slate-400">{{ $p->customer_name }}</p>
                        </td>
                        <td class="p-3">
                            <p class="font-mono text-[10px]">{{ $p->invoice_id ?: '—' }}</p>
                            <p class="text-[10px] text-slate-500">{{ $p->period }}</p>
                        </td>
                        <td class="p-3">
                            <p class="font-bold text-emerald-600">Rp {{ number_format($p->amount, 0, ',', '.') }}</p>
                            @if ($p->status === 'partial')
                                <p class="text-[10px] font-bold text-sky-600 mt-0.5">Sebagian · sisa Rp {{ number_format((int) $p->remaining_amount, 0, ',', '.') }}</p>
                            @elseif ($p->status === 'paid')
                                <p class="text-[10px] font-semibold text-emerald-600/80 mt-0.5">Lunas periode</p>
                            @endif
                        </td>
                        <td class="p-3 text-[11px]">{{ optional($p->payment_date)->format('d M Y H:i') ?: '—' }}</td>
                        <td class="p-3 text-[11px]">{{ $p->payment_method ?: '—' }}</td>
                        <td class="p-3 text-right">
                            <form method="POST" action="{{ panel_route('billing.payments.destroy', $p) }}" onsubmit="return MikPay.confirmForm(event, 'Hapus pembayaran ini? Data tidak bisa dikembalikan.', { title: 'Hapus Pembayaran', variant: 'danger', confirmText: 'Ya, hapus' })" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded" title="Hapus">
                                    <i class="fa-solid fa-trash text-[10px]"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-xs text-slate-500">Belum ada pembayaran dicatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Bayar (Tunai / Transfer Manual — mendukung cicilan) --}}
<div id="payModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Catat Pembayaran</h3>
            <button type="button" onclick="closePayModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ panel_route('billing.payments.store') }}" class="space-y-3" autocomplete="off">
            @csrf
            <input type="hidden" name="customer_name" id="pay_customer_name">
            <div>
                <p class="text-[11px] font-bold text-slate-500 mb-1">Pelanggan</p>
                <p id="pay_customer_label" class="text-sm font-bold text-slate-900 dark:text-white"></p>
                <p id="pay_arrears" class="text-[10px] text-rose-600 mt-1"></p>
            </div>

            <div id="pay_summary" class="grid grid-cols-3 gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-3 text-center">
                <div>
                    <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400">Tagihan</p>
                    <p id="pay_sum_fee" class="text-[11px] font-bold text-slate-800 dark:text-slate-100 mt-0.5">—</p>
                </div>
                <div>
                    <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400">Sudah masuk</p>
                    <p id="pay_sum_paid" class="text-[11px] font-bold text-emerald-600 mt-0.5">—</p>
                </div>
                <div>
                    <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400">Sisa</p>
                    <p id="pay_sum_remain" class="text-[11px] font-bold text-rose-600 mt-0.5">—</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Periode mulai</label>
                    <input type="month" name="period" id="pay_period" value="{{ $period }}" required class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs" onchange="onPayPeriodChange()">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Bayar berapa bulan?</label>
                    <select name="advance_months" id="pay_advance_months" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs" onchange="onAdvanceMonthsChange()">
                        <option value="1">1 bulan (periode ini / cicilan)</option>
                        <option value="2">2 bulan (di muka)</option>
                        <option value="3">3 bulan (di muka)</option>
                        <option value="4">4 bulan (di muka)</option>
                        <option value="5">5 bulan (di muka)</option>
                        <option value="6">6 bulan (di muka)</option>
                        <option value="12">12 bulan (1 tahun di muka)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nominal (Rp)</label>
                    <input type="number" name="amount" id="pay_amount" required min="1" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs" oninput="updatePayPreview()">
                    <div id="pay_quick_btns" class="flex flex-wrap gap-1.5 mt-2">
                        <button type="button" onclick="setPayQuick('full')" class="text-[10px] font-bold px-2 py-1 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100">Lunas sisa</button>
                        <button type="button" onclick="setPayQuick('half')" class="text-[10px] font-bold px-2 py-1 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">50% sisa</button>
                        <button type="button" onclick="setPayQuick(50000)" class="text-[10px] font-bold px-2 py-1 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">Rp 50rb</button>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal bayar</label>
                    <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs">
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Metode (manual)</label>
                <select name="payment_method" required class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs">
                    <option value="Tunai">Tunai</option>
                    <option value="Transfer Manual">Transfer Manual</option>
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Khusus catat admin — bukan Xendit. Bayar di muka hanya di sini.</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Catatan</label>
                <input type="text" name="notes" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs" placeholder="Opsional, mis. cicilan 1">
            </div>
            <p id="pay_preview" class="text-[11px] font-semibold text-slate-700 dark:text-slate-200"></p>
            <p id="pay_hint" class="text-[10px] text-slate-500"></p>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-2.5 rounded-xl">Simpan Pembayaran</button>
                <button type="button" onclick="closePayModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Janji Bayar --}}
<div id="promiseModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Janji Bayar</h3>
            <button type="button" onclick="closePromiseModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="text-[11px] text-slate-500 leading-relaxed">
            Tunda isolir otomatis sampai tanggal janji. Setelah tanggal itu lewat dan masih belum lunas, pelanggan siap diisolir.
        </p>
        <form id="promiseForm" method="POST" action="{{ panel_route('billing.promises.store') }}" class="space-y-3" autocomplete="off">
            @csrf
            <input type="hidden" name="customer_name" id="promise_customer_name">
            <div>
                <p class="text-[11px] font-bold text-slate-500 mb-1">Pelanggan</p>
                <p id="promise_customer_label" class="text-sm font-bold text-slate-900 dark:text-white"></p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Periode tunggakan</label>
                    <input type="month" name="period" id="promise_period" required class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal janji bayar</label>
                    <input type="date" name="promised_pay_date" id="promise_date" required class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs">
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Catatan</label>
                <input type="text" name="promised_pay_note" id="promise_note" maxlength="255" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs" placeholder="Opsional, mis. transfer Sabtu sore">
            </div>
            <p id="promise_hint" class="text-[10px] text-slate-500"></p>
            <div class="flex flex-wrap gap-2 pt-1">
                <button type="submit" class="flex-1 bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold py-2.5 rounded-xl">Simpan Janji</button>
                <button type="button" id="promise_clear_btn" onclick="clearPromise()" class="hidden px-3 py-2.5 rounded-xl border border-rose-200 text-rose-600 text-xs font-bold hover:bg-rose-50">Hapus janji</button>
                <button type="button" onclick="closePromiseModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600">Batal</button>
            </div>
        </form>
        <form id="promiseClearForm" method="POST" action="{{ panel_route('billing.promises.destroy') }}" class="hidden">
            @csrf
            @method('DELETE')
            <input type="hidden" name="customer_name" id="promise_clear_customer">
            <input type="hidden" name="period" id="promise_clear_period">
        </form>
    </div>
</div>

{{-- Modal Harga Akhir Periode --}}
<div id="periodFeeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Harga Akhir Bulan Ini</h3>
            <button type="button" onclick="closePeriodFeeModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="text-[11px] text-slate-500 leading-relaxed">
            Isi harga yang ditagihkan untuk <strong>satu periode saja</strong>. Bulan berikutnya otomatis kembali ke tarif normal pelanggan.
        </p>
        <form id="periodFeeForm" method="POST" action="{{ panel_route('billing.period-fees.store') }}" class="space-y-3" autocomplete="off">
            @csrf
            <input type="hidden" name="customer_name" id="period_fee_customer_name">
            <div>
                <p class="text-[11px] font-bold text-slate-500 mb-1">Pelanggan</p>
                <p id="period_fee_customer_label" class="text-sm font-bold text-slate-900 dark:text-white"></p>
                <p id="period_fee_normal" class="text-[10px] text-slate-400 mt-0.5"></p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Periode</label>
                    <input type="month" name="period" id="period_fee_period" required class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Harga akhir (Rp)</label>
                    <input type="number" name="amount" id="period_fee_amount" required min="0" step="1000" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs font-mono" placeholder="150000">
                </div>
            </div>
            <p id="period_fee_hint" class="text-[10px] text-slate-500"></p>
            <div class="flex flex-wrap gap-2 pt-1">
                <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold py-2.5 rounded-xl">Simpan Harga</button>
                <button type="button" id="period_fee_clear_btn" onclick="clearPeriodFee()" class="hidden px-3 py-2.5 rounded-xl border border-rose-200 text-rose-600 text-xs font-bold hover:bg-rose-50">Hapus diskon</button>
                <button type="button" onclick="closePeriodFeeModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600">Batal</button>
            </div>
        </form>
        <form id="periodFeeClearForm" method="POST" action="{{ panel_route('billing.period-fees.destroy') }}" class="hidden">
            @csrf
            @method('DELETE')
            <input type="hidden" name="customer_name" id="period_fee_clear_customer">
            <input type="hidden" name="period" id="period_fee_clear_period">
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const paymentLinkUrl = @js(panel_route('billing.payment-links.store'));
const paymentLinkCsrf = @js(csrf_token());

async function createPaymentLink(customerName, sendWa = false) {
    const body = new FormData();
    body.append('_token', paymentLinkCsrf);
    body.append('customer_name', customerName);
    if (sendWa) body.append('send_wa', '1');

    const res = await fetch(paymentLinkUrl, {
        method: 'POST',
        body,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    let data = {};
    try {
        data = await res.json();
    } catch (_) {
        data = { ok: false, message: 'Respons server tidak valid.' };
    }

    return { res, data };
}

async function copyTextSafe(text) {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch (_) {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        const ok = document.execCommand('copy');
        ta.remove();
        return ok;
    }
}

async function copyPaymentLink(customerName, btn) {
    if (btn) btn.disabled = true;
    try {
        const { res, data } = await createPaymentLink(customerName, false);
        if (!res.ok || data.ok === false || !data.url) {
            (window.showToast || alert)(data.message || 'Gagal membuat link.');
            return;
        }
        const copied = await copyTextSafe(data.url);
        (window.showToast || alert)(copied ? 'Link bayar disalin.' : ('Link: ' + data.url));
    } catch (e) {
        (window.showToast || alert)(e?.message || 'Gagal membuat link.');
    } finally {
        if (btn) btn.disabled = false;
    }
}

async function sendPaymentLinkWa(customerName, phone, btn) {
    const ok = await MikPay.confirm({
        title: 'Kirim WhatsApp',
        message: 'Kirim link bayar via WhatsApp ke ' + (phone || '…') + '?',
        confirmText: 'Ya, kirim',
    });
    if (!ok) return;
    if (btn) btn.disabled = true;
    try {
        const { res, data } = await createPaymentLink(customerName, true);
        if (data.url) await copyTextSafe(data.url);
        (window.showToast || alert)(data.message || (res.ok ? 'Terkirim.' : 'Gagal kirim WA.'));
    } catch (e) {
        (window.showToast || alert)(e?.message || 'Gagal kirim WA.');
    } finally {
        if (btn) btn.disabled = false;
    }
}

function formatRp(n) {
    const v = Math.max(0, Math.round(Number(n) || 0));
    return 'Rp ' + v.toLocaleString('id-ID');
}

let __payRow = null;

function addMonthsYm(ym, n) {
    const parts = String(ym || '').split('-');
    const y = Number(parts[0]) || new Date().getFullYear();
    const m = Number(parts[1]) || (new Date().getMonth() + 1);
    const d = new Date(y, m - 1 + n, 1);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
}

function advancePeriodList(start, n) {
    const months = Math.max(1, Math.min(12, Number(n) || 1));
    const out = [];
    for (let i = 0; i < months; i++) out.push(addMonthsYm(start, i));
    return out;
}

function formatPeriodLabel(ym) {
    try {
        const [y, m] = String(ym).split('-');
        const names = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        return (names[Number(m) - 1] || m) + ' ' + y;
    } catch (e) {
        return ym;
    }
}

function periodFee(row, period) {
    const map = row?.fee_by_period || {};
    if (period && map[period] != null) return Math.max(0, Number(map[period]));
    if (period && row?.has_period_discount && period === '{{ $period }}') {
        return Math.max(0, Number(row?.period_fee ?? row?.monthly_fee ?? 0));
    }
    return Math.max(0, Number(row?.monthly_fee || 0));
}

function periodRemain(row, period) {
    const fee = periodFee(row, period);
    const map = row?.shortfall_by_period || {};
    if (period && map[period] != null) return Math.max(0, Number(map[period]));
    if (period && (row?.arrears_periods || []).includes(period)) return fee;
    // Periode berjalan yang dipilih
    if (period && period === (row?.first_unpaid_period)) {
        return Math.max(0, Number(row?.remaining ?? fee));
    }
    // Periode depan (belum terbit otomatis) → tarif periode / normal
    return fee > 0 ? fee : 0;
}

function getAdvanceMonths() {
    return Math.max(1, Math.min(12, Number(document.getElementById('pay_advance_months')?.value || 1)));
}

function refreshPaySummary() {
    const row = __payRow || {};
    const period = document.getElementById('pay_period')?.value || '';
    const fee = periodFee(row, period);
    const advance = getAdvanceMonths();
    const periods = advancePeriodList(period, advance);
    const remainOne = periodRemain(row, period);
    const remainTotal = periods.reduce((s, p) => s + periodRemain(row, p), 0);
    const paid = fee > 0 ? Math.max(0, fee - remainOne) : 0;
    const set = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
    const feeTotal = periods.reduce((s, p) => s + periodFee(row, p), 0);

    if (advance > 1) {
        set('pay_sum_fee', feeTotal > 0 ? formatRp(feeTotal) : '—');
        set('pay_sum_paid', 'Di muka');
        set('pay_sum_remain', remainTotal > 0 ? formatRp(remainTotal) : '—');
    } else {
        set('pay_sum_fee', fee > 0 ? formatRp(fee) : '—');
        set('pay_sum_paid', fee > 0 ? formatRp(paid) : '—');
        set('pay_sum_remain', remainOne > 0 ? formatRp(remainOne) : (fee > 0 ? 'Lunas' : '—'));
    }

    const quick = document.getElementById('pay_quick_btns');
    if (quick) quick.classList.toggle('hidden', advance > 1);

    return { fee, paid, remain: remainOne, remainTotal, advance, periods };
}

function updatePayPreview() {
    const { remain, remainTotal, advance, periods } = refreshPaySummary();
    const amount = Math.max(0, Number(document.getElementById('pay_amount')?.value || 0));
    const preview = document.getElementById('pay_preview');
    const hint = document.getElementById('pay_hint');
    if (!preview) return;

    const labels = (periods || []).map(formatPeriodLabel).join(', ');

    if (advance > 1) {
        if (hint) {
            hint.textContent = 'Bayar di muka: ' + labels + '. Saat bulan itu tiba, sudah terhitung lunas.';
        }
        if (!amount) {
            preview.textContent = 'Nominal disarankan total lunas untuk ' + advance + ' bulan.';
            return;
        }
        if (amount >= remainTotal) {
            preview.textContent = 'Akan melunasi: ' + labels + '.';
        } else {
            preview.textContent = 'Nominal kurang dari total ' + formatRp(remainTotal) + ' — dialokasikan berurutan mulai ' + formatPeriodLabel(periods[0]) + '.';
        }
        return;
    }

    if (hint && __payRow) {
        hint.textContent = (__payRow.arrears_count > 0)
            ? '1 bulan / cicilan. Isolir baru dibuka setelah lunas. Untuk bayar di muka, pilih 2–12 bulan.'
            : '1 bulan / cicilan. Pilih 2–12 bulan untuk bayar di muka.';
    }

    if (!amount) {
        preview.textContent = 'Boleh cicil — isi nominal kurang dari sisa.';
        return;
    }
    if (amount < remain) {
        preview.textContent = 'Setelah ini, sisa periode: ' + formatRp(remain - amount) + ' (status Sebagian).';
    } else if (amount === remain) {
        preview.textContent = 'Periode ini akan lunas.';
    } else {
        preview.textContent = 'Lebih dari sisa periode — kelebihan dialihkan ke tunggakan berikutnya.';
    }
}

function setPayQuick(mode) {
    const { remain } = refreshPaySummary();
    let val = remain;
    if (mode === 'half') val = Math.max(1, Math.floor(remain / 2));
    else if (typeof mode === 'number') val = Math.min(mode, remain > 0 ? remain : mode);
    const input = document.getElementById('pay_amount');
    if (input) input.value = val > 0 ? val : '';
    updatePayPreview();
}

function fillSuggestedAmount() {
    const { remain, remainTotal, advance } = refreshPaySummary();
    const need = advance > 1 ? remainTotal : remain;
    const input = document.getElementById('pay_amount');
    if (input) input.value = need > 0 ? need : '';
    updatePayPreview();
}

function onPayPeriodChange() {
    fillSuggestedAmount();
}

function onAdvanceMonthsChange() {
    fillSuggestedAmount();
}

function openPayModal(row) {
    __payRow = row;
    const modal = document.getElementById('payModal');
    document.getElementById('pay_customer_name').value = row.name;
    document.getElementById('pay_customer_label').textContent = (row.display_name || row.name) + ' (' + row.name + ')';
    document.getElementById('pay_arrears').textContent = row.arrears_tooltip
        ? ('Tunggakan: ' + row.arrears_tooltip)
        : '';
    const firstUnpaid = row.first_unpaid_period || row.arrears_periods?.[0] || '{{ $period }}';
    document.getElementById('pay_period').value = firstUnpaid;
    const adv = document.getElementById('pay_advance_months');
    if (adv) adv.value = '1';
    fillSuggestedAmount();
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closePayModal() {
    const modal = document.getElementById('payModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    __payRow = null;
}
document.getElementById('payModal')?.addEventListener('click', function (e) {
    if (e.target === this) closePayModal();
});

function openPromiseModal(row) {
    document.getElementById('promise_customer_name').value = row.name;
    document.getElementById('promise_customer_label').textContent = (row.display_name || row.name) + ' (' + row.name + ')';
    const period = row.promise_period || row.first_unpaid_period || row.arrears_periods?.[0] || '{{ $period }}';
    document.getElementById('promise_period').value = period;
    document.getElementById('promise_date').value = row.promised_pay_date || '';
    document.getElementById('promise_note').value = row.promised_pay_note || '';
    document.getElementById('promise_clear_customer').value = row.name;
    document.getElementById('promise_clear_period').value = period;
    const clearBtn = document.getElementById('promise_clear_btn');
    if (clearBtn) clearBtn.classList.toggle('hidden', !row.is_promised);
    const hint = document.getElementById('promise_hint');
    if (hint) {
        hint.textContent = row.promised_pay_date
            ? ('Janji aktif sampai ' + row.promised_pay_date + '. Isolir ditunda hingga tanggal itu lewat.')
            : 'Contoh: pelanggan janji bayar 1 Agustus → isi tanggal itu. Mulai 2 Agustus baru siap isolir.';
    }
    const modal = document.getElementById('promiseModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closePromiseModal() {
    const modal = document.getElementById('promiseModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function clearPromise() {
    const ok = await MikPay.confirm({
        title: 'Hapus Janji Bayar',
        message: 'Hapus janji bayar pelanggan ini? Isolir akan mengikuti aturan biasa lagi.',
        variant: 'danger',
        confirmText: 'Ya, hapus',
    });
    if (!ok) return;
    document.getElementById('promise_clear_period').value = document.getElementById('promise_period').value;
    document.getElementById('promiseClearForm').submit();
}

document.getElementById('promiseModal')?.addEventListener('click', function (e) {
    if (e.target === this) closePromiseModal();
});
document.getElementById('promise_period')?.addEventListener('change', function () {
    document.getElementById('promise_clear_period').value = this.value;
});

let __periodFeeRow = null;

function openPeriodFeeModal(row) {
    __periodFeeRow = row;
    document.getElementById('period_fee_customer_name').value = row.name;
    document.getElementById('period_fee_customer_label').textContent = (row.display_name || row.name) + ' (' + row.name + ')';
    const normal = Number(row.monthly_fee || 0);
    document.getElementById('period_fee_normal').textContent = normal > 0
        ? ('Tarif normal: ' + formatRp(normal))
        : 'Tarif normal belum diisi di data pelanggan.';
    const period = '{{ $period }}';
    document.getElementById('period_fee_period').value = period;
    const override = row.fee_by_period && row.fee_by_period[period] != null
        ? Number(row.fee_by_period[period])
        : (row.has_period_discount ? Number(row.period_fee) : '');
    document.getElementById('period_fee_amount').value = override !== '' ? override : (normal || '');
    document.getElementById('period_fee_clear_customer').value = row.name;
    document.getElementById('period_fee_clear_period').value = period;
    const hasOverride = !!(row.fee_by_period && row.fee_by_period[period] != null) || !!row.has_period_discount;
    document.getElementById('period_fee_clear_btn')?.classList.toggle('hidden', !hasOverride);
    const hint = document.getElementById('period_fee_hint');
    if (hint) {
        hint.textContent = hasOverride
            ? 'Diskon aktif untuk periode ini. Hapus untuk kembali ke tarif normal.'
            : 'Contoh: tarif 170.000 → isi 150.000. Hanya berlaku di bulan yang dipilih.';
    }
    syncPeriodFeeUi();
    const modal = document.getElementById('periodFeeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function syncPeriodFeeUi() {
    const row = __periodFeeRow || {};
    const period = document.getElementById('period_fee_period')?.value || '';
    const map = row.fee_by_period || {};
    const hasOverride = period && map[period] != null;
    document.getElementById('period_fee_clear_btn')?.classList.toggle('hidden', !hasOverride);
    document.getElementById('period_fee_clear_period').value = period;
    if (hasOverride) {
        document.getElementById('period_fee_amount').value = Number(map[period]);
    }
}

function closePeriodFeeModal() {
    const modal = document.getElementById('periodFeeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    __periodFeeRow = null;
}

async function clearPeriodFee() {
    const ok = await MikPay.confirm({
        title: 'Hapus Diskon Periode',
        message: 'Hapus harga khusus periode ini? Tagihan kembali ke tarif normal.',
        variant: 'danger',
        confirmText: 'Ya, hapus',
    });
    if (!ok) return;
    document.getElementById('period_fee_clear_period').value = document.getElementById('period_fee_period').value;
    document.getElementById('periodFeeClearForm').submit();
}

document.getElementById('periodFeeModal')?.addEventListener('click', function (e) {
    if (e.target === this) closePeriodFeeModal();
});
document.getElementById('period_fee_period')?.addEventListener('change', syncPeriodFeeUi);
</script>
@endpush
