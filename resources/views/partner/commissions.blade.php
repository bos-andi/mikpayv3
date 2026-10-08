@extends('layouts.partner')

@section('title', 'Komisi')
@section('heading', 'Riwayat Komisi')
@section('subheading', 'Setiap pembayaran langganan dari rujukan Anda')

@php
    $rp = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
    $pct = fn (float $v) => rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
    $date = fn ($d) => $d ? $d->copy()->timezone(app_timezone())->translatedFormat('d M Y') : '—';
@endphp

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    @foreach ([
        ['Total komisi', $balance['total'], 'fa-sack-dollar', 'indigo'],
        ['Bisa ditarik', $balance['available'], 'fa-circle-check', 'emerald'],
        ['Tertahan', $balance['on_hold'], 'fa-clock', 'amber'],
        ['Sudah dicairkan', $balance['withdrawn'], 'fa-building-columns', 'slate'],
    ] as $s)
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500">{{ $s[0] }}</p>
                <span class="w-8 h-8 rounded-lg bg-{{ $s[3] }}-50 text-{{ $s[3] }}-600 flex items-center justify-center text-xs"><i class="fa-solid {{ $s[2] }}"></i></span>
            </div>
            <p class="mt-2 text-xl font-extrabold text-slate-900">{{ $rp($s[1]) }}</p>
        </div>
    @endforeach
</div>

<div class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50/60 px-5 py-4 text-xs text-indigo-900 leading-relaxed flex gap-3">
    <i class="fa-solid fa-circle-info text-indigo-500 mt-0.5"></i>
    <p>
        Komisi <b>{{ $pct($rates['first']) }}%</b> dari pembayaran pertama
        @if ($settings['recurring_months'] > 0 && $rates['recurring'] > 0)
            dan <b>{{ $pct($rates['recurring']) }}%</b> dari perpanjangan selama {{ $settings['recurring_months'] }} bulan.
        @else
            .
        @endif
        @if ($settings['hold_days'] > 0)
            Komisi ditahan <b>{{ $settings['hold_days'] }} hari</b> setelah pembayaran, lalu otomatis bisa ditarik.
        @endif
    </p>
</div>

<div class="mt-5 rounded-2xl bg-white border border-slate-200 overflow-hidden">
    @if ($commissions->isEmpty())
        <div class="px-5 py-14 text-center">
            <span class="inline-flex w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 items-center justify-center text-xl"><i class="fa-solid fa-coins"></i></span>
            <p class="mt-3 font-bold text-slate-700">Belum ada komisi</p>
            <p class="text-xs text-slate-500">Komisi tercatat otomatis saat pelanggan rujukan Anda membayar paket langganan.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">Tanggal</th>
                        <th class="px-5 py-3 text-left font-bold">Pelanggan</th>
                        <th class="px-5 py-3 text-left font-bold">Jenis</th>
                        <th class="px-5 py-3 text-right font-bold">Pembayaran</th>
                        <th class="px-5 py-3 text-right font-bold">Komisi</th>
                        <th class="px-5 py-3 text-left font-bold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($commissions as $c)
                        @php $pkg = \App\Services\SubscriptionPackageService::get((string) $c->package); @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">{{ $date($c->created_at) }}</td>
                            <td class="px-5 py-3">
                                <p class="font-bold text-slate-800">{{ $c->tenant_name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $pkg ? 'Paket '.$pkg['name'] : $c->package }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $c->kind === 'first' ? 'bg-emerald-50 text-emerald-700' : 'bg-cyan-50 text-cyan-700' }}">{{ $c->kindLabel() }}</span>
                            </td>
                            <td class="px-5 py-3 text-right text-slate-600 whitespace-nowrap">{{ $rp($c->base_amount) }} <span class="text-[11px] text-slate-400">× {{ $pct($c->percent) }}%</span></td>
                            <td class="px-5 py-3 text-right font-extrabold whitespace-nowrap {{ $c->isCancelled() ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $rp($c->amount) }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $c->statusBadgeClass() }}">{{ $c->statusLabel() }}</span>
                                @if (! $c->isCancelled() && ! $c->isAvailable())
                                    <p class="text-[10px] text-slate-400 mt-0.5">cair {{ $date($c->available_at) }}</p>
                                @elseif ($c->isCancelled() && $c->cancel_reason)
                                    <p class="text-[10px] text-rose-500 mt-0.5 max-w-[180px] truncate" title="{{ $c->cancel_reason }}">{{ $c->cancel_reason }}</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($commissions->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $commissions->links() }}</div>
        @endif
    @endif
</div>
@endsection
