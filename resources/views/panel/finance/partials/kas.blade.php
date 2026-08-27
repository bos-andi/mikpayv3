<div class="grid grid-cols-2 gap-2.5 sm:gap-3">
    <div class="bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-500/30 rounded-2xl p-3 sm:p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-500">Kas masuk periode</p>
        <p class="text-lg sm:text-xl font-extrabold text-emerald-600 mt-1 tabular-nums">Rp {{ number_format($cashInNonBilling, 0, ',', '.') }}</p>
        <p class="text-[10px] text-slate-400 mt-1">Manual (non-billing)</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-500/30 rounded-2xl p-3 sm:p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Kas keluar periode</p>
        <p class="text-lg sm:text-xl font-extrabold text-rose-600 mt-1 tabular-nums">Rp {{ number_format($totalOut, 0, ',', '.') }}</p>
        <p class="text-[10px] text-slate-400 mt-1">Pengeluaran operasional</p>
    </div>
</div>

<div class="rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/80 dark:bg-amber-500/10 px-4 py-3 text-[11px] text-amber-900 dark:text-amber-200">
    <strong>Jangan catat ulang</strong> pembayaran tagihan pelanggan di sini — itu sudah otomatis dari Billing.
    Pakai form ini untuk uang masuk lain (voucher, instalasi) dan pengeluaran operasional (listrik, gaji, bandwidth).
    Belanja OLT/ODC/ONU/kendaraan pakai form <strong>Input belanja aset</strong> di bawah — masuk neraca, tidak memotong laba rugi.
</div>

{{-- Dual input forms --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-500/30 rounded-2xl p-4 shadow-sm">
        <h3 class="text-sm font-bold text-emerald-700 dark:text-emerald-300 mb-3">
            <i class="fa-solid fa-arrow-down mr-1.5"></i> Input uang masuk
        </h3>
        <form method="POST" action="{{ panel_route('finance.cash.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="section" value="kas">
            <input type="hidden" name="range" value="{{ $range }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="tipe" value="masuk">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ now()->toDateString() }}" required
                           class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Nominal (Rp)</label>
                    <input type="number" name="nominal" min="1" step="1" required placeholder="100000"
                           class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Kategori</label>
                    <select name="kategori" required class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                        @foreach ($categoriesIn as $cat)
                            <option value="{{ $cat }}">{{ \App\Models\CashTransaction::categoryLabel($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Metode</label>
                    <select name="metode" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                        <option value="tunai">Tunai</option>
                        <option value="transfer">Transfer</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Keterangan</label>
                <input type="text" name="keterangan" maxlength="500" placeholder="Opsional"
                       class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            </div>
            <button type="submit" class="w-full text-xs font-bold px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white">
                Simpan pemasukan
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-500/30 rounded-2xl p-4 shadow-sm">
        <h3 class="text-sm font-bold text-rose-700 dark:text-rose-300 mb-3">
            <i class="fa-solid fa-arrow-up mr-1.5"></i> Input uang keluar
        </h3>
        <form method="POST" action="{{ panel_route('finance.cash.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="section" value="kas">
            <input type="hidden" name="range" value="{{ $range }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="tipe" value="keluar">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ now()->toDateString() }}" required
                           class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Nominal (Rp)</label>
                    <input type="number" name="nominal" min="1" step="1" required placeholder="250000"
                           class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Kategori</label>
                    <select name="kategori" required class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                        @foreach ($categoriesOut as $cat)
                            <option value="{{ $cat }}">{{ \App\Models\CashTransaction::categoryLabel($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Metode</label>
                    <select name="metode" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                        <option value="tunai">Tunai</option>
                        <option value="transfer" selected>Transfer</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Keterangan</label>
                <input type="text" name="keterangan" maxlength="500" placeholder="Contoh: Bayar listrik ODC"
                       class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            </div>
            <button type="submit" class="w-full text-xs font-bold px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white">
                Simpan pengeluaran
            </button>
        </form>
    </div>
</div>

{{-- Input belanja aset / modal --}}
<div class="bg-white dark:bg-slate-900 border border-sky-200 dark:border-sky-500/30 rounded-2xl p-4 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
        <div>
            <h3 class="text-sm font-bold text-sky-800 dark:text-sky-200">
                <i class="fa-solid fa-warehouse mr-1.5"></i> Input belanja aset (untuk Neraca)
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                Catat pembelian OLT, ODC/ODP, ONU, kabel, kendaraan, dll.
                Kas berkurang &amp; aset di neraca bertambah — <strong>tidak masuk beban Laba Rugi</strong>.
            </p>
        </div>
        <a href="{{ panel_route('finance') }}?section=neraca&range={{ $range }}&year={{ $year }}&month={{ $month }}"
           class="text-[11px] font-bold text-indigo-600 hover:underline">Lihat / edit saldo neraca →</a>
    </div>
    <form method="POST" action="{{ panel_route('finance.assets.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        @csrf
        <input type="hidden" name="section" value="kas">
        <input type="hidden" name="range" value="{{ $range }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Tanggal</label>
            <input type="date" name="tanggal" value="{{ now()->toDateString() }}" required
                   class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Nominal (Rp)</label>
            <input type="number" name="nominal" min="1" step="1" required placeholder="5000000"
                   class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
        </div>
        <div class="lg:col-span-2">
            <label class="text-[10px] font-bold uppercase text-slate-400">Jenis aset</label>
            <select name="asset_key" required class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                @foreach ($assetPurchaseKeys ?? \App\Models\FinanceSetting::ASSET_PURCHASE_KEYS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Metode bayar</label>
            <select name="metode" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                <option value="transfer" selected>Transfer</option>
                <option value="tunai">Tunai</option>
                <option value="lainnya">Lainnya</option>
            </select>
        </div>
        <div class="sm:col-span-2 lg:col-span-3">
            <label class="text-[10px] font-bold uppercase text-slate-400">Keterangan</label>
            <input type="text" name="keterangan" maxlength="500" placeholder="Contoh: Beli OLT Huawei 16 PON"
                   class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
        </div>
        <div class="sm:col-span-2 lg:col-span-2 flex items-end">
            <label class="inline-flex items-center gap-2 text-[11px] text-slate-600 dark:text-slate-300 pb-2">
                <input type="hidden" name="pay_from_cash" value="0">
                <input type="checkbox" name="pay_from_cash" value="1" checked class="rounded border-slate-300">
                Kurangi kas (bayar tunai/transfer)
            </label>
        </div>
        <div class="sm:col-span-2 lg:col-span-1 flex items-end">
            <button type="submit" class="w-full text-xs font-bold px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white">
                Simpan aset
            </button>
        </div>
    </form>
    <p class="text-[10px] text-slate-400 mt-3">
        Centang “Kurangi kas” jika dibayar dari kas/bank. Matikan jika aset awal / hibah (hanya menambah nilai di neraca).
        Akumulasi penyusutan &amp; utang lain diisi di tab <strong>Neraca</strong>.
    </p>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Riwayat buku kas</h3>
        <span class="text-[10px] text-slate-400">Periode {{ $periodLabel }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left min-w-[640px]">
            <thead class="bg-slate-50 dark:bg-slate-950/60 text-[10px] uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="px-4 py-2.5 font-bold">Tanggal</th>
                    <th class="px-4 py-2.5 font-bold">Tipe</th>
                    <th class="px-4 py-2.5 font-bold">Kategori</th>
                    <th class="px-4 py-2.5 font-bold">Metode</th>
                    <th class="px-4 py-2.5 font-bold">Keterangan</th>
                    <th class="px-4 py-2.5 font-bold text-right">Nominal</th>
                    <th class="px-4 py-2.5 font-bold text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($cashItems as $c)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-2.5 whitespace-nowrap">{{ $c->tanggal?->format('d M Y') }}</td>
                        <td class="px-4 py-2.5">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $c->tipe === 'masuk' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600' }}">
                                {{ strtoupper($c->tipe) }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5">{{ \App\Models\CashTransaction::categoryLabel($c->kategori) }}</td>
                        <td class="px-4 py-2.5">{{ $c->metode }}</td>
                        <td class="px-4 py-2.5 text-slate-500 max-w-[200px] truncate">{{ $c->keterangan ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-right font-bold tabular-nums {{ $c->tipe === 'masuk' ? 'text-emerald-600' : 'text-rose-600' }}">
                            Rp {{ number_format($c->nominal, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <form method="POST" action="{{ panel_route('finance.cash.destroy', $c) }}"
                                  onsubmit="return MikPay.confirmForm(event, 'Hapus transaksi kas ini?', { title: 'Hapus', variant: 'danger', confirmText: 'Hapus' })">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="section" value="kas">
                                <input type="hidden" name="range" value="{{ $range }}">
                                <input type="hidden" name="year" value="{{ $year }}">
                                <input type="hidden" name="month" value="{{ $month }}">
                                <button type="submit" class="text-rose-500 hover:text-rose-600" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada transaksi kas di periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($cashItems->hasPages())
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
            {{ $cashItems->links() }}
        </div>
    @endif
</div>
