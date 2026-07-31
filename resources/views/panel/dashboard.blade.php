@extends('layouts.panel')

@section('title', 'Dashboard — MikPay')
@section('page', 'dashboard')

@section('content')
@php
    $periodIncome = (int) ($periodIncome ?? 0);
    $pppTotal = (int) ($pppTotal ?? 0);
    $pppActive = (int) ($pppActive ?? 0);
    $pppIsolir = (int) ($pppIsolir ?? 0);
    $paidCount = (int) ($paidCount ?? 0);
    $unpaidCount = (int) ($unpaidCount ?? 0);
    $notSetCount = (int) ($notSetCount ?? 0);
    $periodLabel = $periodLabel ?? now()->translatedFormat('M Y');
@endphp

{{-- Welcome Banner --}}
<div class="p-6 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-cyan-600 dark:from-indigo-900/50 dark:via-slate-900 dark:to-slate-900 border border-indigo-200 dark:border-indigo-500/20 text-white shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold mb-1">Selamat Datang di MikPay Control Center</h2>
        <p class="text-xs opacity-90 dark:text-slate-400">Monitoring jaringan Fiber Optik, OLT, MikroTik, dan Billing.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ panel_route('gis') }}" class="bg-white/10 hover:bg-white/20 dark:bg-slate-800 dark:hover:bg-slate-700 border border-white/20 dark:border-slate-700 text-xs px-3.5 py-2 rounded-xl font-medium transition text-white">
            <i class="fa-solid fa-map-marked-alt text-cyan-300 dark:text-cyan-400 mr-1.5"></i> Buka Peta ODP
        </a>
        <a href="{{ panel_route('remote') }}" class="bg-white/10 hover:bg-white/20 dark:bg-slate-800 dark:hover:bg-slate-700 border border-white/20 dark:border-slate-700 text-xs px-3.5 py-2 rounded-xl font-medium transition text-white">
            <i class="fa-solid fa-sliders text-amber-300 dark:text-amber-400 mr-1.5"></i> Control OLT
        </a>
        <a href="{{ panel_route('billing') }}" class="bg-white/10 hover:bg-white/20 dark:bg-slate-800 dark:hover:bg-slate-700 border border-white/20 dark:border-slate-700 text-xs px-3.5 py-2 rounded-xl font-medium transition text-white">
            <i class="fa-solid fa-file-invoice-dollar text-emerald-300 mr-1.5"></i> Billing
        </a>
    </div>
</div>

@if (! ($hasRouter ?? false))
    <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-xs font-semibold px-4 py-3">
        Belum ada router aktif. Atur di <a href="{{ panel_route('settings') }}" class="underline">Pengaturan Sistem</a>.
    </div>
@elseif (! ($fetchOk ?? false))
    <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs font-semibold px-4 py-3">
        Gagal membaca data PPP dari MikroTik: {{ $fetchMessage ?: 'cek koneksi API router.' }}
    </div>
@endif

{{-- 4 Metrics --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Omset {{ $periodLabel }}</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <i class="fa-solid fa-rupiah-sign"></i>
            </div>
        </div>
        <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">Rp {{ number_format($periodIncome, 0, ',', '.') }}</h3>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">Pembayaran tercatat periode ini</p>
    </div>

    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Pelanggan PPPoE</span>
            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
        <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $pppTotal }} <span class="text-xs font-normal text-slate-500 dark:text-slate-400">Client</span></h3>
        <div class="flex items-center gap-3 mt-2 text-[11px]">
            <span class="text-emerald-600 dark:text-emerald-400 font-semibold"><i class="fa-solid fa-circle text-[8px] mr-1"></i>{{ $pppActive }} Aktif</span>
            <span class="text-rose-600 dark:text-rose-400 font-semibold"><i class="fa-solid fa-circle text-[8px] mr-1"></i>{{ $pppIsolir }} Isolir</span>
        </div>
    </div>

    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Kapasitas ODP</span>
            <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                <i class="fa-solid fa-box-archive"></i>
            </div>
        </div>
        @php
            $odpPortUsed = (int) ($odpPortUsed ?? 0);
            $odpPortTotal = (int) ($odpPortTotal ?? 0);
            $odpBoxCount = (int) ($odpBoxCount ?? 0);
            $odpPct = $odpPortTotal > 0 ? (int) round(($odpPortUsed / $odpPortTotal) * 100) : 0;
        @endphp
        <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">
            {{ $odpPortUsed }} / {{ $odpPortTotal }}
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">Port</span>
        </h3>
        <div class="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full mt-3 overflow-hidden">
            <div class="bg-cyan-500 h-full rounded-full" style="width: {{ min(100, $odpPct) }}%;"></div>
        </div>
        <p class="text-[10px] text-slate-400 mt-2">
            @if ($odpBoxCount > 0)
                {{ $odpBoxCount }} kotak ODP · <a href="{{ panel_route('gis') }}" class="text-cyan-600 hover:underline">Kelola peta</a>
            @else
                Belum ada ODP · <a href="{{ panel_route('gis') }}" class="text-cyan-600 hover:underline">Tambah di Peta GIS</a>
            @endif
        </p>
    </div>

    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition relative">
        <span class="absolute top-3 right-3 text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">PREVIEW</span>
        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Status OLT HSGQ</span>
            <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <i class="fa-solid fa-tower-cell"></i>
            </div>
        </div>
        <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">4 PON <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">NORMAL</span></h3>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">
            Avg Optical Power: <span class="text-emerald-600 dark:text-emerald-400 font-mono font-semibold">-19.4 dBm</span>
        </p>
        <p class="text-[10px] text-slate-400 mt-1">Monitoring OLT menyusul via tunnel</p>
    </div>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm relative">
        <span id="bandwidthLiveBadge" class="absolute top-4 right-4 text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">…</span>
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4 pr-16">
            <div class="min-w-0">
                <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">Live Bandwidth Traffic (MikroTik ROS)</h3>
                <p id="bandwidthSubtitle" class="text-xs text-slate-500 dark:text-slate-400">Mengambil data Rx/Tx dari router…</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs shrink-0">
                <label class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <span class="font-medium">Interface</span>
                    <select id="bandwidthInterface" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-[11px] px-2 py-1.5 max-w-[11rem]">
                        <option value="">Auto (WAN)</option>
                    </select>
                </label>
                <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium"><i class="fa-solid fa-circle text-[8px]"></i> Download (Rx)</span>
                <span class="flex items-center gap-1 text-rose-600 dark:text-rose-400 font-medium"><i class="fa-solid fa-circle text-[8px]"></i> Upload (Tx)</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-4 mb-3 text-[11px] font-mono text-slate-600 dark:text-slate-300">
            <span>Rx: <strong id="bandwidthRxNow" class="text-emerald-600 dark:text-emerald-400">—</strong></span>
            <span>Tx: <strong id="bandwidthTxNow" class="text-rose-600 dark:text-rose-400">—</strong></span>
        </div>
        <div class="h-64 relative">
            <canvas id="chartBandwidth"></canvas>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
        <div>
            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 mb-1">Status Penagihan {{ $periodLabel }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Progres pembayaran — data aktual</p>
            <div class="h-48 relative">
                <canvas id="chartBillingStatus"></canvas>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 text-center text-xs">
            <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20">
                <p class="text-slate-500 dark:text-slate-400 flex items-center justify-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Lunas
                </p>
                <p class="font-bold text-emerald-600 dark:text-emerald-400 text-sm mt-1">{{ $paidCount }} User</p>
            </div>
            <div class="p-2 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-100 dark:border-rose-500/20">
                <p class="text-slate-500 dark:text-slate-400 flex items-center justify-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span> Belum Bayar
                </p>
                <p class="font-bold text-rose-600 dark:text-rose-400 text-sm mt-1">{{ $unpaidCount }} User</p>
            </div>
        </div>
        @if ($notSetCount > 0)
            <p class="text-[10px] text-slate-400 text-center mt-2">{{ $notSetCount }} pelanggan belum diatur tagihan</p>
        @endif
    </div>
</div>

{{-- Alerts --}}
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm relative">
    <span class="absolute top-4 right-4 text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">PREVIEW</span>
    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 mb-4 flex items-center gap-2 pr-16">
        <i class="fa-solid fa-bell text-amber-500"></i> Aktivitas Sistem & Alert Terbaru
    </h3>
    <div class="space-y-3 text-xs">
        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-slate-200">Contoh alert optik (preview)</p>
                    <p class="text-slate-500 dark:text-slate-400">Redaman tinggi akan tampil di sini setelah monitoring OLT aktif.</p>
                </div>
            </div>
            <span class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0">Preview</span>
        </div>

        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-slate-200">Auto-Isolir & reminder WA</p>
                    <p class="text-slate-500 dark:text-slate-400">
                        Isolir otomatis & pengingat WhatsApp (jam 08:00) aktif di scheduler.
                        Saat ini isolir: <span class="font-bold text-rose-600">{{ $pppIsolir }}</span> · Belum bayar: <span class="font-bold text-rose-600">{{ $unpaidCount }}</span>
                    </p>
                </div>
            </div>
            <a href="{{ panel_route('billing') }}" class="text-[10px] font-bold text-indigo-600 shrink-0">Lihat</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.__dashboardBilling = {
    paid: {{ $paidCount }},
    unpaid: {{ $unpaidCount }}
};
window.__dashboardTraffic = {
    url: @json($trafficUrl ?? panel_route('dashboard.bandwidth')),
    hasRouter: @json((bool) ($hasRouter ?? false)),
    pollMs: 2500,
    maxPoints: 40
};
</script>
<script src="{{ asset('js/panel-charts.js') }}?v=4" defer></script>
@endpush
