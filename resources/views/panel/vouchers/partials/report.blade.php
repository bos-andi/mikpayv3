<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row gap-3 sm:items-end sm:justify-between">
    <div>
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Laporan penjualan voucher</h3>
        <p class="text-[10px] text-slate-400 mt-0.5">
            Dari <span class="font-mono">/system/script</span> owner
            <span class="font-mono font-semibold">{{ $sellingOwner ?: '—' }}</span>
            (butuh profile mode remc/ntfc)
        </p>
    </div>
    <form method="GET" action="{{ panel_route('vouchers') }}" class="flex gap-2 items-end">
        <input type="hidden" name="section" value="report">
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Owner (bulan)</label>
            <input name="owner" value="{{ $sellingOwner }}" placeholder="aug2026" class="mt-1 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
        </div>
        <button type="submit" class="text-xs font-bold px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900">Tampilkan</button>
    </form>
</div>

<div class="grid grid-cols-2 gap-3">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase text-slate-400">Transaksi</p>
        <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ count($sellingRows) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase text-emerald-500">Omset</p>
        <p class="text-2xl font-extrabold text-emerald-600 mt-1 tabular-nums">Rp {{ number_format($sellingTotal, 0, ',', '.') }}</p>
    </div>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left min-w-[640px]">
            <thead class="bg-slate-50 dark:bg-slate-950/60 text-[10px] uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="px-4 py-2.5 font-bold">Tanggal</th>
                    <th class="px-4 py-2.5 font-bold">User</th>
                    <th class="px-4 py-2.5 font-bold">Profile</th>
                    <th class="px-4 py-2.5 font-bold">Validity</th>
                    <th class="px-4 py-2.5 font-bold">MAC</th>
                    <th class="px-4 py-2.5 font-bold text-right">Harga</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($sellingRows as $row)
                    <tr>
                        <td class="px-4 py-2.5 whitespace-nowrap">{{ $row['date'] }} {{ $row['time'] }}</td>
                        <td class="px-4 py-2.5 font-mono font-bold">{{ $row['user'] }}</td>
                        <td class="px-4 py-2.5">{{ $row['profile'] }}</td>
                        <td class="px-4 py-2.5">{{ $row['validity'] }}</td>
                        <td class="px-4 py-2.5 font-mono text-[10px]">{{ $row['mac'] }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-emerald-600 tabular-nums">Rp {{ number_format($row['price'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                            Belum ada penjualan tercatat. Pakai mode expired <strong>remc</strong> / <strong>ntfc</strong> pada profile.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
