@extends('layouts.partner')

@section('title', 'Pelanggan Rujukan')
@section('heading', 'Pelanggan Rujukan')
@section('subheading', 'ISP yang mendaftar MikPay lewat link / kode Anda')

@php
    $rp = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->timezone(app_timezone())->translatedFormat('d M Y') : '—';
@endphp

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    @foreach ([
        ['Klik link', $stats['clicks'], 'fa-arrow-pointer', 'indigo'],
        ['Total pendaftar', $stats['signups'], 'fa-user-plus', 'cyan'],
        ['Pernah membayar', $stats['paying'], 'fa-crown', 'violet'],
        ['Berlangganan aktif', $stats['active'], 'fa-signal', 'emerald'],
    ] as $s)
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-{{ $s[3] }}-50 text-{{ $s[3] }}-600 flex items-center justify-center text-sm"><i class="fa-solid {{ $s[2] }}"></i></span>
                <div>
                    <p class="text-xl font-extrabold text-slate-900">{{ number_format($s[1], 0, ',', '.') }}</p>
                    <p class="text-[11px] text-slate-500 font-semibold">{{ $s[0] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-5 rounded-2xl bg-white border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-extrabold text-slate-900">Daftar Rujukan</h3>
        <p class="text-xs text-slate-500">Status diperbarui otomatis mengikuti langganan pelanggan.</p>
    </div>

    @if ($tenants->isEmpty())
        <div class="px-5 py-14 text-center">
            <span class="inline-flex w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 items-center justify-center text-xl"><i class="fa-solid fa-users"></i></span>
            <p class="mt-3 font-bold text-slate-700">Belum ada pelanggan rujukan</p>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Bagikan link referral dari dashboard. Setiap ISP yang mendaftar lewat link Anda akan muncul di sini.</p>
            @if ($partner->isActive())
                <button type="button" onclick="partnerCopy(@js($partner->referralUrl()))" class="mt-4 inline-flex items-center gap-2 rounded-xl gradient-bg text-white text-xs font-bold px-4 py-2.5"><i class="fa-regular fa-copy"></i> Salin link referral</button>
            @endif
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">Pelanggan</th>
                        <th class="px-5 py-3 text-left font-bold">Tanggal daftar</th>
                        <th class="px-5 py-3 text-left font-bold">Status</th>
                        <th class="px-5 py-3 text-left font-bold">Aktif sampai</th>
                        <th class="px-5 py-3 text-right font-bold">Komisi Anda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($tenants as $t)
                        @php
                            $isTrial = ($t->plan ?? 'trial') === 'trial';
                            $active = $t->subscriptionIsActive();
                            [$label, $cls] = match (true) {
                                ! $active => ['Berakhir', 'bg-slate-100 text-slate-600 border-slate-200'],
                                $isTrial => ['Trial', 'bg-amber-50 text-amber-700 border-amber-200'],
                                default => ['Berlangganan', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                            };
                            $pkg = $isTrial ? null : \App\Services\SubscriptionPackageService::get((string) $t->plan);
                            $agg = $commissionByTenant->get($t->id);
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-extrabold">{{ strtoupper(mb_substr($t->name, 0, 1)) }}</span>
                                    <div>
                                        <p class="font-bold text-slate-800">{{ $t->name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $pkg ? 'Paket '.$pkg['name'] : ($isTrial ? 'Masa trial' : '—') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">{{ $date($t->created_at) }}</td>
                            <td class="px-5 py-3"><span class="text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $cls }}">{{ $label }}</span></td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">{{ $date($t->subscriptionEndsAt()) }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @if ($agg)
                                    <p class="font-extrabold text-emerald-600">{{ $rp((int) $agg->total) }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $agg->payments }}× pembayaran</p>
                                @else
                                    <span class="text-slate-400 text-xs">Belum bayar</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($tenants->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $tenants->links() }}</div>
        @endif
    @endif
</div>
@endsection
