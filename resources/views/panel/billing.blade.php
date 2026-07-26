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
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Periode <span class="font-semibold text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}</span>
            · Router <span class="font-semibold text-indigo-600">{{ $router->displayName() }}</span>
            · Tunggakan multi-bulan + auto isolir
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <form method="POST" action="{{ panel_route('billing.auto-isolir') }}" onsubmit="return confirm('Jalankan auto isolir untuk semua pelanggan yang menunggak di router ini?')">
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

<div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total</p>
        <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Menunggak</p>
        <p class="text-2xl font-extrabold text-rose-600 mt-1">{{ $stats['menunggak'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Hari ini</p>
        <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $stats['due_today'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-500">Lunas</p>
        <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $stats['paid'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-rose-400">Outstanding</p>
        <p class="text-sm font-extrabold text-rose-600 mt-1">Rp {{ number_format($stats['outstanding_total'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">Pemasukan periode</p>
        <p class="text-sm font-extrabold text-indigo-600 mt-1">Rp {{ number_format($stats['period_income'], 0, ',', '.') }}</p>
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
                                    @if ($r['monthly_fee'] > 0)
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
                                @if ($r['isolir'] || $r['disabled'])
                                    <span class="ml-1 px-2 py-0.5 rounded-full font-bold text-[10px] bg-rose-500/10 text-rose-600">ISOLIR</span>
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
                                        @endif
                                    @endif
                                    @if ($r['isolir'] || $r['disabled'])
                                        <form method="POST" action="{{ panel_route('billing.open') }}" onsubmit="return confirm('Buka isolir {{ $r['name'] }}?')">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $r['id'] }}">
                                            <input type="hidden" name="name" value="{{ $r['name'] }}">
                                            <button type="submit" class="p-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 rounded text-emerald-600" title="Buka Isolir">
                                                <i class="fa-solid fa-lock-open"></i>
                                            </button>
                                        </form>
                                    @elseif ($r['id'])
                                        <form method="POST" action="{{ panel_route('billing.isolir') }}" onsubmit="return confirm('Isolir {{ $r['name'] }} sekarang?')">
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
                        <td class="p-3 font-bold text-emerald-600">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                        <td class="p-3 text-[11px]">{{ optional($p->payment_date)->format('d M Y H:i') ?: '—' }}</td>
                        <td class="p-3 text-[11px]">{{ $p->payment_method ?: '—' }}</td>
                        <td class="p-3 text-right">
                            <form method="POST" action="{{ panel_route('billing.payments.destroy', $p) }}" onsubmit="return confirm('Hapus pembayaran ini?')" class="inline">
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

{{-- Modal Bayar --}}
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
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nominal (Rp)</label>
                    <input type="number" name="amount" id="pay_amount" required min="1" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Periode (bulan tunggakan)</label>
                    <input type="month" name="period" id="pay_period" value="{{ $period }}" required class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal bayar</label>
                    <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Metode (manual)</label>
                    <select name="payment_method" required class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs">
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer Manual">Transfer Manual</option>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Pembayaran dicatat admin (bukan via Xendit).</p>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Catatan</label>
                <input type="text" name="notes" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs" placeholder="Opsional">
            </div>
            <p id="pay_hint" class="text-[10px] text-slate-500"></p>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-2.5 rounded-xl">Simpan Pembayaran</button>
                <button type="button" onclick="closePayModal()" class="px-4 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700">Batal</button>
            </div>
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
    if (!confirm('Kirim link bayar via WhatsApp ke ' + (phone || '…') + '?')) return;
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

function openPayModal(row) {
    const modal = document.getElementById('payModal');
    document.getElementById('pay_customer_name').value = row.name;
    document.getElementById('pay_customer_label').textContent = (row.display_name || row.name) + ' (' + row.name + ')';
    document.getElementById('pay_arrears').textContent = row.arrears_tooltip
        ? ('Tunggakan: ' + row.arrears_tooltip)
        : '';
    const firstUnpaid = row.first_unpaid_period || row.arrears_periods?.[0] || '{{ $period }}';
    document.getElementById('pay_period').value = firstUnpaid;
    const amount = Number(row.monthly_fee || 0) || Number(row.remaining || 0);
    document.getElementById('pay_amount').value = amount > 0 ? amount : '';
    document.getElementById('pay_hint').textContent = row.arrears_count > 0
        ? 'Periode otomatis diisi bulan tunggakan tertua. Ubah jika ingin bayar bulan lain.'
        : (row.billing_configured ? 'Isi nominal sesuai tarif.' : 'Data tagihan belum diatur — isi nominal manual.');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closePayModal() {
    const modal = document.getElementById('payModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
document.getElementById('payModal')?.addEventListener('click', function (e) {
    if (e.target === this) closePayModal();
});
</script>
@endpush
