@extends('layouts.panel')

@section('title', 'Laporan Keuangan — MikPay')
@section('page', 'finance')

@section('content')
@php
    $monthsLong = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $financeUrl = function (array $extra = []) use ($section, $range, $year, $month) {
        $params = array_merge([
            'section' => $section,
            'range' => $range,
            'year' => $year,
            'month' => $month,
        ], $extra);

        return panel_route('finance').'?'.http_build_query($params);
    };
    $sections = [
        'overview' => ['label' => 'Ringkasan', 'icon' => 'fa-chart-pie'],
        'pl' => ['label' => 'Laba Rugi', 'icon' => 'fa-scale-balanced'],
        'neraca' => ['label' => 'Neraca', 'icon' => 'fa-landmark'],
        'kas' => ['label' => 'Buku Kas', 'icon' => 'fa-money-bill-transfer'],
    ];
@endphp

<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Laporan Keuangan</p>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="fa-solid {{ $sections[$section]['icon'] ?? 'fa-chart-pie' }} text-indigo-500 text-base"></i>
            {{ $sections[$section]['label'] ?? 'Ringkasan' }}
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
            Router <span class="font-semibold text-indigo-600">{{ $router->displayName() }}</span>
            · Periode <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $periodLabel }}</span>
            · Semi-akuntansi (kas + neraca ringkas)
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ panel_route('finance.export.csv', ['section' => $section, 'range' => $range, 'year' => $year, 'month' => $month]) }}"
           class="inline-flex items-center gap-2 text-xs font-bold px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200">
            <i class="fa-solid fa-file-csv"></i> CSV
        </a>
        <a href="{{ panel_route('finance.export.pdf', ['section' => $section, 'range' => $range, 'year' => $year, 'month' => $month]) }}"
           class="inline-flex items-center gap-2 text-xs font-bold px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white shadow-md shadow-rose-600/20">
            <i class="fa-solid fa-file-pdf"></i> PDF
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

{{-- Period filter --}}
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
    <div class="flex flex-wrap gap-1.5">
        <a href="{{ $financeUrl(['range' => 'monthly']) }}"
           class="text-xs font-bold px-3 py-1.5 rounded-lg border transition {{ $range === 'monthly' ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300' }}">
            Bulanan
        </a>
        <a href="{{ $financeUrl(['range' => 'yearly']) }}"
           class="text-xs font-bold px-3 py-1.5 rounded-lg border transition {{ $range === 'yearly' ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300' }}">
            Tahunan
        </a>
    </div>
    <form method="GET" action="{{ panel_route('finance') }}" class="flex flex-wrap items-center gap-2">
        <input type="hidden" name="section" value="{{ $section }}">
        <input type="hidden" name="range" value="{{ $range }}">
        <select name="year" class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            @foreach ($availableYears as $y)
                <option value="{{ $y }}" @selected((int) $y === (int) $year)>{{ $y }}</option>
            @endforeach
        </select>
        @if ($range === 'monthly')
            <select name="month" class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                @foreach ($monthsLong as $num => $label)
                    <option value="{{ $num }}" @selected((int) $num === (int) $month)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
        <button type="submit" class="text-xs font-bold px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900">Tampilkan</button>
    </form>
</div>

@if ($section === 'overview')
    @include('panel.finance.partials.overview')
@elseif ($section === 'pl')
    @include('panel.finance.partials.pl')
@elseif ($section === 'neraca')
    @include('panel.finance.partials.neraca')
@elseif ($section === 'kas')
    @include('panel.finance.partials.kas')
@endif
@endsection

@push('scripts')
<script>
window.__financeTrend = @json($trend ?? []);
window.__cashCategories = {
    masuk: @json(collect($categoriesIn)->mapWithKeys(fn ($c) => [$c => \App\Models\CashTransaction::categoryLabel($c)])),
    keluar: @json(collect($categoriesOut)->mapWithKeys(fn ($c) => [$c => \App\Models\CashTransaction::categoryLabel($c)])),
};
</script>
@if ($section === 'overview')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
@endif
<script src="{{ asset('js/panel-finance.js') }}?v=2" defer></script>
@endpush
