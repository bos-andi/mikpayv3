@extends('layouts.panel')

@section('title', 'Hotspot & Voucher — MikPay')
@section('page', 'vouchers')

@section('content')
@php
    $voucherUrl = function (string $section, array $extra = []) use ($filters) {
        $params = array_merge([
            'section' => $section,
            'profile' => $filters['profile'] ?? 'all',
            'comment' => $filters['comment'] ?? '',
            'exp' => ! empty($filters['exp']) ? 1 : null,
        ], $extra);
        $params = array_filter($params, fn ($v) => $v !== null && $v !== '');

        return panel_route('vouchers').'?'.http_build_query($params);
    };
    $sections = [
        'users' => ['label' => 'Voucher / User', 'icon' => 'fa-users'],
        'generate' => ['label' => 'Generate', 'icon' => 'fa-bolt'],
        'profiles' => ['label' => 'Profile', 'icon' => 'fa-layer-group'],
        'active' => ['label' => 'Aktif', 'icon' => 'fa-signal'],
        'report' => ['label' => 'Laporan', 'icon' => 'fa-chart-column'],
        'template' => ['label' => 'Template', 'icon' => 'fa-pen-to-square'],
    ];
@endphp

<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Hotspot & Voucher</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            @if ($router)
                Router <span class="font-semibold text-indigo-600">{{ $router->displayName() }}</span>
                · Data langsung dari MikroTik (prepaid hotspot)
            @else
                Editor template voucher (tanpa koneksi router)
            @endif
        </p>
    </div>
</div>

@if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-500/10 dark:border-emerald-500/30 px-4 py-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 dark:border-rose-500/30 px-4 py-3 text-xs font-semibold text-rose-700 dark:text-rose-300">{{ session('error') }}</div>
@endif
@if (! $fetchOk && $fetchMessage)
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-500/10 dark:border-amber-500/30 px-4 py-3 text-xs font-semibold text-amber-800 dark:text-amber-200">{{ $fetchMessage }}</div>
@endif

<div class="flex flex-wrap gap-1.5 p-1 rounded-2xl bg-slate-100/80 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800">
    @foreach ($sections as $key => $meta)
        <a href="{{ $voucherUrl($key, ['profile' => 'all', 'comment' => '', 'exp' => null]) }}"
           class="inline-flex items-center gap-2 text-xs font-bold px-3.5 py-2 rounded-xl transition {{ $section === $key ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}">
            <i class="fa-solid {{ $meta['icon'] }}"></i>
            <span>{{ $meta['label'] }}</span>
        </a>
    @endforeach
</div>

@if ($section === 'users')
    @include('panel.vouchers.partials.users')
@elseif ($section === 'generate')
    @include('panel.vouchers.partials.generate')
@elseif ($section === 'profiles')
    @include('panel.vouchers.partials.profiles')
@elseif ($section === 'active')
    @include('panel.vouchers.partials.active')
@elseif ($section === 'template')
    @include('panel.vouchers.partials.template')
@else
    @include('panel.vouchers.partials.report')
@endif
@endsection
