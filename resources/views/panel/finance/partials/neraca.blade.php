@php
    $fmt = fn (int $n) => 'Rp '.number_format($n, 0, ',', '.');
    $asOfLabel = $periodEnd->timezone('Asia/Jakarta')->translatedFormat('d F Y');
    $tenantName = current_tenant()?->name ?? 'MikPay';
@endphp

{{-- Laporan neraca vertikal (format formal) --}}
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-4 py-4 border-b border-slate-100 dark:border-slate-800 text-center">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $tenantName }}</p>
        <h3 class="text-base font-extrabold text-slate-900 dark:text-white tracking-wide mt-0.5">NERACA</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Per {{ $asOfLabel }} · {{ $router->displayName() }}</p>
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
                {{-- AKTIVA --}}
                <tr class="bg-slate-100/80 dark:bg-slate-800/50">
                    <td colspan="2" class="px-4 py-2.5 font-extrabold text-slate-900 dark:text-white tracking-wide">AKTIVA (ASET)</td>
                </tr>

                <tr>
                    <td colspan="2" class="px-4 py-2 font-bold text-slate-800 dark:text-slate-100">Aktiva Lancar</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Kas dan Bank</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->kas) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Piutang</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium text-amber-600">{{ $fmt($neraca->piutang) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Persediaan</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->persediaan) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Beban Dibayar di Muka</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->beban_dibayar_dimuka) }}</td>
                </tr>
                <tr class="bg-slate-50/70 dark:bg-slate-950/40">
                    <td class="px-4 py-2.5 pl-8 font-bold text-slate-800 dark:text-slate-100">Total Aktiva Lancar</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-bold">{{ $fmt($neraca->total_aktiva_lancar) }}</td>
                </tr>

                <tr>
                    <td colspan="2" class="px-4 py-2 font-bold text-slate-800 dark:text-slate-100">Aktiva Tetap</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Tanah</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->tanah) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Bangunan</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->bangunan) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-10 text-slate-500 dark:text-slate-400">Akumulasi Penyusutan Bangunan</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium text-rose-600">({{ $fmt($neraca->akum_penyusutan_bangunan) }})</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Inventaris / Peralatan (nilai perolehan)</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->inventaris) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-10 text-slate-500 dark:text-slate-400">Akumulasi Penyusutan Inventaris</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium text-rose-600">({{ $fmt($neraca->akum_penyusutan_inventaris) }})</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Kendaraan</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->kendaraan) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-10 text-slate-500 dark:text-slate-400">Akumulasi Penyusutan Kendaraan</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium text-rose-600">({{ $fmt($neraca->akum_penyusutan_kendaraan) }})</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Aset OLT / Server</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->aset_olt ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-10 text-slate-500 dark:text-slate-400">Akumulasi Penyusutan OLT</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium text-rose-600">({{ $fmt($neraca->akum_penyusutan_olt ?? 0) }})</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Aset ODC / ODP / Cabinet</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->aset_odc_odp ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-10 text-slate-500 dark:text-slate-400">Akumulasi Penyusutan ODC/ODP</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium text-rose-600">({{ $fmt($neraca->akum_penyusutan_odc ?? 0) }})</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Aset Kabel / Fiber Plant</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->aset_kabel ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-10 text-slate-500 dark:text-slate-400">Akumulasi Penyusutan Kabel</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium text-rose-600">({{ $fmt($neraca->akum_penyusutan_kabel ?? 0) }})</td>
                </tr>
                <tr class="bg-slate-50/70 dark:bg-slate-950/40">
                    <td class="px-4 py-2.5 pl-8 font-bold text-slate-800 dark:text-slate-100">Total Aktiva Tetap</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-bold">{{ $fmt($neraca->total_aktiva_tetap) }}</td>
                </tr>

                <tr class="bg-sky-50 dark:bg-sky-500/10 border-y border-sky-100 dark:border-sky-500/20">
                    <td class="px-4 py-3 font-extrabold text-sky-900 dark:text-sky-100 tracking-wide">TOTAL AKTIVA</td>
                    <td class="px-4 py-3 text-right tabular-nums font-extrabold text-sky-800 dark:text-sky-200 text-sm">{{ $fmt($neraca->total_aktiva) }}</td>
                </tr>

                {{-- KEWAJIBAN --}}
                <tr class="bg-slate-100/80 dark:bg-slate-800/50">
                    <td colspan="2" class="px-4 py-2.5 font-extrabold text-slate-900 dark:text-white tracking-wide">KEWAJIBAN (LIABILITAS)</td>
                </tr>

                <tr>
                    <td colspan="2" class="px-4 py-2 font-bold text-slate-800 dark:text-slate-100">Kewajiban Lancar</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Utang Usaha</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium text-rose-600">{{ $fmt($neraca->utang_usaha) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Utang Gaji</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->utang_gaji) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Utang Pajak</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->utang_pajak) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Beban yang Masih Harus Dibayar</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->beban_masih_harus_dibayar) }}</td>
                </tr>
                <tr class="bg-slate-50/70 dark:bg-slate-950/40">
                    <td class="px-4 py-2.5 pl-8 font-bold text-slate-800 dark:text-slate-100">Total Kewajiban Lancar</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-bold">{{ $fmt($neraca->total_kewajiban_lancar) }}</td>
                </tr>

                <tr>
                    <td colspan="2" class="px-4 py-2 font-bold text-slate-800 dark:text-slate-100">Kewajiban Jangka Panjang</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Utang Bank Jangka Panjang</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->utang_bank_jp) }}</td>
                </tr>
                <tr class="bg-slate-50/70 dark:bg-slate-950/40">
                    <td class="px-4 py-2.5 pl-8 font-bold text-slate-800 dark:text-slate-100">Total Kewajiban Jangka Panjang</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-bold">{{ $fmt($neraca->total_kewajiban_jp) }}</td>
                </tr>
                <tr class="bg-rose-50/60 dark:bg-rose-500/10">
                    <td class="px-4 py-2.5 font-bold text-rose-800 dark:text-rose-200">Total Kewajiban</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-bold text-rose-700 dark:text-rose-300">{{ $fmt($neraca->total_kewajiban) }}</td>
                </tr>

                {{-- EKUITAS --}}
                <tr class="bg-slate-100/80 dark:bg-slate-800/50">
                    <td colspan="2" class="px-4 py-2.5 font-extrabold text-slate-900 dark:text-white tracking-wide">EKUITAS</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Modal</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium">{{ $fmt($neraca->modal) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Laba Ditahan</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium {{ $neraca->laba_ditahan >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $fmt($neraca->laba_ditahan) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 pl-8 text-slate-600 dark:text-slate-300">Prive / Penarikan Modal</td>
                    <td class="px-4 py-2 text-right tabular-nums font-medium text-rose-600">({{ $fmt($neraca->prive ?? 0) }})</td>
                </tr>
                <tr class="bg-slate-50/70 dark:bg-slate-950/40">
                    <td class="px-4 py-2.5 pl-8 font-bold text-slate-800 dark:text-slate-100">Total Ekuitas</td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-bold">{{ $fmt($neraca->total_ekuitas) }}</td>
                </tr>

                <tr class="bg-violet-50 dark:bg-violet-500/10 border-y border-violet-100 dark:border-violet-500/20">
                    <td class="px-4 py-3 font-extrabold text-violet-900 dark:text-violet-100 tracking-wide">TOTAL KEWAJIBAN + EKUITAS</td>
                    <td class="px-4 py-3 text-right tabular-nums font-extrabold text-violet-800 dark:text-violet-200 text-sm">{{ $fmt($neraca->total_passiva) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if ($neraca->selisih !== 0)
        <div class="px-4 py-3 border-t border-amber-200 dark:border-amber-500/30 bg-amber-50/80 dark:bg-amber-500/10 text-[11px] text-amber-800 dark:text-amber-200">
            Selisih neraca: <strong>{{ $fmt($neraca->selisih) }}</strong>
            (aktiva {{ $neraca->selisih > 0 ? 'lebih besar' : 'lebih kecil' }}).
            Sesuaikan modal / akun manual, atau catat utang usaha. Piutang otomatis menambah aktiva tanpa menambah ekuitas kas.
        </div>
    @else
        <div class="px-4 py-3 border-t border-emerald-200 dark:border-emerald-500/30 bg-emerald-50/80 dark:bg-emerald-500/10 text-[11px] text-emerald-800 dark:text-emerald-200 font-semibold">
            Neraca seimbang (total aktiva = kewajiban + ekuitas).
        </div>
    @endif

    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-400 space-y-0.5">
        <p>Kas dan Bank = saldo awal kas + pembayaran billing + kas masuk − kas keluar (s/d {{ $asOfLabel }}).</p>
        <p>Piutang = tunggakan billing pelanggan. Utang Usaha = daftar hutang terbuka di bawah.</p>
    </div>
</div>

{{-- Form saldo awal + akun manual --}}
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-1">Isi saldo & akun neraca</h3>
    <p class="text-[10px] text-slate-400 mb-4">
        Kas/Bank, Piutang, Utang Usaha, dan Laba Ditahan dihitung otomatis.
        Belanja aset baru lebih mudah dari tab <strong>Buku Kas → Input belanja aset</strong>.
        Form ini untuk saldo awal, penyusutan, utang lain, dan prive.
    </p>

    <form method="POST" action="{{ panel_route('finance.settings.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="section" value="neraca">
        <input type="hidden" name="range" value="{{ $range }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Saldo awal kas (Rp)</label>
                <input type="number" name="opening_cash" min="0" step="1" required
                       value="{{ old('opening_cash', $settings->opening_cash ?? 0) }}"
                       class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Modal (Rp)</label>
                <input type="number" name="opening_equity" min="0" step="1" required
                       value="{{ old('opening_equity', $settings->opening_equity ?? 0) }}"
                       class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Saldo awal per tanggal</label>
                <input type="date" name="opening_as_of"
                       value="{{ old('opening_as_of', optional($settings->opening_as_of)->format('Y-m-d')) }}"
                       class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            </div>
        </div>

        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Aktiva lancar</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach (\App\Models\FinanceSetting::CURRENT_ASSET_KEYS as $key)
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400">{{ \App\Models\FinanceSetting::MANUAL_LINES[$key] }}</label>
                        <input type="number" name="manual_lines[{{ $key }}]" min="0" step="1"
                               value="{{ old('manual_lines.'.$key, $settings->manualAmount($key)) }}"
                               class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Aktiva tetap (umum + ISP)</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach (\App\Models\FinanceSetting::FIXED_ASSET_KEYS as $key)
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400">{{ \App\Models\FinanceSetting::MANUAL_LINES[$key] }}</label>
                        <input type="number" name="manual_lines[{{ $key }}]" min="0" step="1"
                               value="{{ old('manual_lines.'.$key, $settings->manualAmount($key)) }}"
                               class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Kewajiban &amp; ekuitas lain</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach (array_merge(\App\Models\FinanceSetting::LIABILITY_KEYS, ['prive']) as $key)
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400">{{ \App\Models\FinanceSetting::MANUAL_LINES[$key] }}</label>
                        <input type="number" name="manual_lines[{{ $key }}]" min="0" step="1"
                               value="{{ old('manual_lines.'.$key, $settings->manualAmount($key)) }}"
                               class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="text-xs font-bold px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white">
                Simpan data neraca
            </button>
        </div>
    </form>
</div>

{{-- Hutang usaha detail --}}
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Utang Usaha (detail)</h3>
        <p class="text-[10px] text-slate-400">Masuk ke baris Utang Usaha di neraca. Tandai lunas untuk menguranginya.</p>
    </div>

    <form method="POST" action="{{ panel_route('finance.debts.store') }}" class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40">
        @csrf
        <input type="hidden" name="section" value="neraca">
        <input type="hidden" name="range" value="{{ $range }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Tanggal</label>
            <input type="date" name="tanggal" value="{{ now()->toDateString() }}" required
                   class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2">
        </div>
        <div class="lg:col-span-2">
            <label class="text-[10px] font-bold uppercase text-slate-400">Keterangan</label>
            <input type="text" name="keterangan" required maxlength="255" placeholder="Contoh: Hutang uplink bulan ini"
                   class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2">
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Nominal (Rp)</label>
            <div class="mt-1 flex gap-2">
                <input type="number" name="nominal" min="1" step="1" required placeholder="500000"
                       class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2">
                <button type="submit" class="shrink-0 text-xs font-bold px-3 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white">Tambah</button>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left min-w-[560px]">
            <thead class="bg-slate-50 dark:bg-slate-950/60 text-[10px] uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="px-4 py-2.5 font-bold">Tanggal</th>
                    <th class="px-4 py-2.5 font-bold">Keterangan</th>
                    <th class="px-4 py-2.5 font-bold">Status</th>
                    <th class="px-4 py-2.5 font-bold text-right">Nominal</th>
                    <th class="px-4 py-2.5 font-bold text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($debts as $d)
                    <tr class="{{ $d->paid_at ? 'opacity-60' : '' }}">
                        <td class="px-4 py-2.5 whitespace-nowrap">{{ $d->tanggal?->format('d M Y') }}</td>
                        <td class="px-4 py-2.5">{{ $d->keterangan }}</td>
                        <td class="px-4 py-2.5">
                            @if ($d->paid_at)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600">LUNAS</span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-600">OPEN</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold tabular-nums">{{ $fmt((int) $d->nominal) }}</td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            @if (! $d->paid_at)
                                <form method="POST" action="{{ panel_route('finance.debts.paid', $d) }}" class="inline"
                                      onsubmit="return MikPay.confirmForm(event, 'Tandai hutang ini lunas?', { title: 'Lunas', confirmText: 'Ya' })">
                                    @csrf
                                    <input type="hidden" name="section" value="neraca">
                                    <input type="hidden" name="range" value="{{ $range }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <button type="submit" class="text-emerald-600 hover:text-emerald-700 mr-2" title="Lunas"><i class="fa-solid fa-check"></i></button>
                                </form>
                            @endif
                            <form method="POST" action="{{ panel_route('finance.debts.destroy', $d) }}" class="inline"
                                  onsubmit="return MikPay.confirmForm(event, 'Hapus hutang ini?', { title: 'Hapus', variant: 'danger', confirmText: 'Hapus' })">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="section" value="neraca">
                                <input type="hidden" name="range" value="{{ $range }}">
                                <input type="hidden" name="year" value="{{ $year }}">
                                <input type="hidden" name="month" value="{{ $month }}">
                                <button type="submit" class="text-rose-500 hover:text-rose-600" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada utang usaha tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
