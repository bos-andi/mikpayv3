<div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm min-w-0">
        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-500">Total pemasukan</p>
        <p class="text-base sm:text-xl font-extrabold text-emerald-600 mt-1 tabular-nums leading-snug break-words">Rp {{ number_format($totalIn, 0, ',', '.') }}</p>
        <p class="text-[10px] text-slate-400 mt-1">Billing + kas masuk</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm min-w-0">
        <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Pengeluaran</p>
        <p class="text-base sm:text-xl font-extrabold text-rose-600 mt-1 tabular-nums leading-snug break-words">Rp {{ number_format($totalOut, 0, ',', '.') }}</p>
        <p class="text-[10px] text-slate-400 mt-1">Kas keluar periode</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm min-w-0">
        <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">Laba rugi (kas)</p>
        <p class="text-base sm:text-xl font-extrabold mt-1 tabular-nums leading-snug break-words {{ $net >= 0 ? 'text-indigo-600' : 'text-rose-600' }}">Rp {{ number_format($net, 0, ',', '.') }}</p>
        <p class="text-[10px] text-slate-400 mt-1">{{ $paymentCount }} bayar · rata Rp {{ number_format($avgPayment, 0, ',', '.') }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm min-w-0">
        <p class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Outstanding</p>
        <p class="text-base sm:text-xl font-extrabold text-amber-600 mt-1 tabular-nums leading-snug break-words">Rp {{ number_format($outstanding, 0, ',', '.') }}</p>
        <p class="text-[10px] text-slate-400 mt-1">Piutang pelanggan</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Tren 12 bulan</h3>
            <span class="text-[10px] text-slate-400">Masuk vs keluar</span>
        </div>
        <div class="h-56">
            <canvas id="chartFinanceTrend"></canvas>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm space-y-3">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Pintasan</h3>
        <a href="{{ $financeUrl(['section' => 'pl']) }}" class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2.5 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <span><i class="fa-solid fa-scale-balanced text-indigo-500 mr-2"></i>Lihat Laba Rugi</span>
            <i class="fa-solid fa-chevron-right text-slate-400 text-[10px]"></i>
        </a>
        <a href="{{ $financeUrl(['section' => 'neraca']) }}" class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2.5 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <span><i class="fa-solid fa-landmark text-amber-500 mr-2"></i>Lihat Neraca</span>
            <i class="fa-solid fa-chevron-right text-slate-400 text-[10px]"></i>
        </a>
        <a href="{{ $financeUrl(['section' => 'kas']) }}" class="flex items-center justify-between gap-2 rounded-xl border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50/50 dark:bg-indigo-500/10 px-3 py-2.5 text-xs font-semibold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-500/20">
            <span><i class="fa-solid fa-plus mr-2"></i>Input kas masuk/keluar</span>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
        </a>
        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 space-y-1">
            <div class="flex justify-between"><span>Kas (neraca)</span><span class="font-bold tabular-nums text-slate-800 dark:text-slate-100">Rp {{ number_format($neraca->kas, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span>Hutang terbuka</span><span class="font-bold tabular-nums text-rose-600">Rp {{ number_format($neraca->hutang, 0, ',', '.') }}</span></div>
        </div>
    </div>
</div>

@if ($range === 'yearly')
@php $monthsLong = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; @endphp
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Ringkasan per bulan {{ $year }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
            <thead class="bg-slate-50 dark:bg-slate-950/60 text-[10px] uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="px-4 py-2.5 font-bold">Bulan</th>
                    <th class="px-4 py-2.5 font-bold text-right">Billing</th>
                    <th class="px-4 py-2.5 font-bold text-right">Kas masuk</th>
                    <th class="px-4 py-2.5 font-bold text-right">Keluar</th>
                    <th class="px-4 py-2.5 font-bold text-right">Net</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($monthly as $row)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-2.5 font-semibold">
                            <a href="{{ $financeUrl(['range' => 'monthly', 'month' => $row->month]) }}" class="text-indigo-600 hover:underline">{{ $monthsLong[$row->month] }}</a>
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">Rp {{ number_format($row->billing_income, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">Rp {{ number_format($row->cash_in, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums text-rose-600">Rp {{ number_format($row->cash_out, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums font-bold {{ $row->net >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">Rp {{ number_format($row->net, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Mutasi pembayaran billing</h3>
        <span class="text-[10px] text-slate-400">Max 100 terbaru</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left min-w-[640px]">
            <thead class="bg-slate-50 dark:bg-slate-950/60 text-[10px] uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="px-4 py-2.5 font-bold">Tanggal</th>
                    <th class="px-4 py-2.5 font-bold">Pelanggan</th>
                    <th class="px-4 py-2.5 font-bold">Periode</th>
                    <th class="px-4 py-2.5 font-bold">Metode</th>
                    <th class="px-4 py-2.5 font-bold text-right">Nominal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($payments as $p)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-2.5 whitespace-nowrap">{{ optional($p->payment_date)->format('d M Y H:i') }}</td>
                        <td class="px-4 py-2.5">
                            <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $p->display_name ?: $p->customer_name }}</div>
                            @if ($p->display_name)
                                <div class="text-[10px] text-slate-400 font-mono">{{ $p->customer_name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 tabular-nums">{{ $p->period }}</td>
                        <td class="px-4 py-2.5">{{ $p->payment_method ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-emerald-600 tabular-nums">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada pembayaran di periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
