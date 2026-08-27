@php
    $fmt = fn (int $n) => 'Rp '.number_format($n, 0, ',', '.');
    $lr = $labaRugi;
    $tenantName = current_tenant()?->name ?? 'MikPay';
@endphp

{{-- Laporan laba rugi vertikal (format formal, pos ISP WiFi) --}}
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-4 py-4 border-b border-slate-100 dark:border-slate-800 text-center">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $tenantName }}</p>
        <h3 class="text-base font-extrabold text-slate-900 dark:text-white tracking-wide mt-0.5">LAPORAN LABA RUGI</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Periode {{ $periodLabel }} · {{ $router->displayName() }} · Basis kas
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs min-w-[520px]">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-950/70 border-b border-slate-200 dark:border-slate-800">
                    <th class="px-4 py-2.5 text-left font-bold text-[10px] uppercase tracking-wider text-slate-500">Uraian</th>
                    <th class="px-4 py-2.5 text-right font-bold text-[10px] uppercase tracking-wider text-slate-500 w-44">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                {{-- PENDAPATAN --}}
                <tr class="bg-slate-100/80 dark:bg-slate-800/50">
                    <td colspan="2" class="px-4 py-2.5 font-extrabold text-slate-900 dark:text-white tracking-wide">PENDAPATAN USAHA</td>
                </tr>
                @foreach ($lr->pendapatan_usaha as $line)
                    <tr>
                        <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">{{ $line->label }}</td>
                        <td class="px-4 py-2 text-right tabular-nums font-medium {{ $line->amount > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                            {{ $fmt((int) $line->amount) }}
                        </td>
                    </tr>
                @endforeach
                <tr class="bg-emerald-50/70 dark:bg-emerald-500/10">
                    <td class="px-4 py-2.5 font-bold text-emerald-800 dark:text-emerald-200">Total Pendapatan Usaha</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-extrabold text-emerald-700 dark:text-emerald-300">{{ $fmt((int) $lr->total_pendapatan) }}</td>
                </tr>

                {{-- BEBAN JARINGAN --}}
                <tr class="bg-slate-100/80 dark:bg-slate-800/50">
                    <td colspan="2" class="px-4 py-2.5 font-extrabold text-slate-900 dark:text-white tracking-wide">BEBAN OPERASIONAL JARINGAN</td>
                </tr>
                @foreach ($lr->beban_jaringan as $line)
                    <tr>
                        <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">{{ $line->label }}</td>
                        <td class="px-4 py-2 text-right tabular-nums font-medium {{ $line->amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            {{ $fmt((int) $line->amount) }}
                        </td>
                    </tr>
                @endforeach
                <tr class="bg-slate-50/70 dark:bg-slate-950/40">
                    <td class="px-4 py-2.5 pl-8 font-bold text-slate-800 dark:text-slate-100">Total Beban Operasional Jaringan</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-bold text-rose-600">{{ $fmt((int) $lr->total_beban_jaringan) }}</td>
                </tr>

                <tr class="bg-sky-50/80 dark:bg-sky-500/10">
                    <td class="px-4 py-2.5 font-bold text-sky-900 dark:text-sky-100">Laba Kotor Usaha</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-extrabold {{ $lr->laba_kotor >= 0 ? 'text-sky-700 dark:text-sky-300' : 'text-rose-600' }}">
                        {{ $fmt((int) $lr->laba_kotor) }}
                    </td>
                </tr>

                {{-- BEBAN UMUM --}}
                <tr class="bg-slate-100/80 dark:bg-slate-800/50">
                    <td colspan="2" class="px-4 py-2.5 font-extrabold text-slate-900 dark:text-white tracking-wide">BEBAN OPERASIONAL UMUM</td>
                </tr>
                @foreach ($lr->beban_umum as $line)
                    <tr>
                        <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">{{ $line->label }}</td>
                        <td class="px-4 py-2 text-right tabular-nums font-medium {{ $line->amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            {{ $fmt((int) $line->amount) }}
                        </td>
                    </tr>
                @endforeach
                <tr class="bg-slate-50/70 dark:bg-slate-950/40">
                    <td class="px-4 py-2.5 pl-8 font-bold text-slate-800 dark:text-slate-100">Total Beban Operasional Umum</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-bold text-rose-600">{{ $fmt((int) $lr->total_beban_umum) }}</td>
                </tr>

                <tr class="bg-rose-50/50 dark:bg-rose-500/10">
                    <td class="px-4 py-2.5 font-bold text-rose-800 dark:text-rose-200">Total Beban</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-extrabold text-rose-700 dark:text-rose-300">{{ $fmt((int) $lr->total_beban) }}</td>
                </tr>

                <tr class="{{ $lr->laba_bersih >= 0 ? 'bg-indigo-50 dark:bg-indigo-500/10 border-y border-indigo-100 dark:border-indigo-500/20' : 'bg-rose-50 dark:bg-rose-500/10 border-y border-rose-100 dark:border-rose-500/20' }}">
                    <td class="px-4 py-3 font-extrabold text-slate-900 dark:text-white tracking-wide">
                        {{ $lr->laba_bersih >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums font-extrabold text-sm {{ $lr->laba_bersih >= 0 ? 'text-indigo-700 dark:text-indigo-300' : 'text-rose-700 dark:text-rose-300' }}">
                        {{ $fmt((int) $lr->laba_bersih) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if (($lr->belanja_aset ?? 0) > 0)
        <div class="px-4 py-3 border-t border-sky-100 dark:border-sky-500/20 bg-sky-50/70 dark:bg-sky-500/10 text-[11px] text-sky-900 dark:text-sky-200">
            Belanja aset periode ini <strong>{{ $fmt((int) $lr->belanja_aset) }}</strong> dicatat di neraca (bukan beban laba rugi).
        </div>
    @endif

    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-400 space-y-0.5">
        <p>Pendapatan Langganan diambil otomatis dari pembayaran Billing. Voucher, instalasi, dan beban diinput di tab Buku Kas.</p>
        <p>Piutang belum tertagih tidak masuk di sini — lihat tab <a href="{{ $financeUrl(['section' => 'neraca']) }}" class="font-semibold text-indigo-600 hover:underline">Neraca</a>.</p>
    </div>
</div>

@if ($range === 'yearly')
@php $monthsLong = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; @endphp
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Rekap laba rugi per bulan — {{ $year }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left min-w-[560px]">
            <thead class="bg-slate-50 dark:bg-slate-950/60 text-[10px] uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="px-4 py-2.5 font-bold">Bulan</th>
                    <th class="px-4 py-2.5 font-bold text-right">Pendapatan</th>
                    <th class="px-4 py-2.5 font-bold text-right">Beban</th>
                    <th class="px-4 py-2.5 font-bold text-right">Laba / Rugi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($monthly as $row)
                    <tr>
                        <td class="px-4 py-2.5 font-semibold">
                            <a href="{{ $financeUrl(['range' => 'monthly', 'month' => $row->month]) }}" class="text-indigo-600 hover:underline">{{ $monthsLong[$row->month] }}</a>
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums text-emerald-600">{{ $fmt((int) $row->total_in) }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums text-rose-600">{{ $fmt((int) $row->cash_out) }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums font-bold {{ $row->net >= 0 ? 'text-indigo-600' : 'text-rose-600' }}">{{ $fmt((int) $row->net) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 dark:bg-slate-950/60 font-bold">
                <tr>
                    <td class="px-4 py-2.5">Total {{ $year }}</td>
                    <td class="px-4 py-2.5 text-right text-emerald-600">{{ $fmt((int) $totalIn) }}</td>
                    <td class="px-4 py-2.5 text-right text-rose-600">{{ $fmt((int) $totalOut) }}</td>
                    <td class="px-4 py-2.5 text-right {{ $net >= 0 ? 'text-indigo-600' : 'text-rose-600' }}">{{ $fmt((int) $net) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif
