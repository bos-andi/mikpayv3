@if ($lastGenerate)
@php $printComment = (string) ($lastGenerate['comment'] ?? ''); @endphp
<div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-500/10 dark:border-emerald-500/30 px-4 py-3 text-xs text-emerald-800 dark:text-emerald-200 flex flex-wrap items-center justify-between gap-2">
    <div>
        Batch terakhir: <strong>{{ $lastGenerate['qty'] ?? 0 }}</strong> voucher ·
        comment <span class="font-mono">{{ $printComment }}</span> ·
        {{ $lastGenerate['date'] ?? '' }}
    </div>
    <div class="flex flex-wrap gap-2 font-bold">
        <a href="{{ panel_route('vouchers.print', ['id' => $printComment, 'qr' => 'no']) }}" target="_blank" class="text-indigo-600 hover:underline">Cetak</a>
        <a href="{{ panel_route('vouchers.print', ['id' => $printComment, 'qr' => 'yes']) }}" target="_blank" class="text-violet-600 hover:underline">Cetak QR</a>
        <a href="{{ panel_route('vouchers.print', ['id' => $printComment, 'small' => 'yes']) }}" target="_blank" class="text-slate-700 dark:text-slate-200 hover:underline">Small</a>
        <a href="{{ panel_route('vouchers.print', ['id' => $printComment, 'tpl' => 'thermal', 'qr' => 'yes']) }}" target="_blank" class="text-slate-700 dark:text-slate-200 hover:underline">Thermal</a>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-3">Generate voucher massal</h3>
        <form method="POST" action="{{ panel_route('vouchers.generate') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @csrf
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Jumlah</label>
                <input type="number" name="qty" value="10" min="1" max="500" required class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Mode</label>
                <select name="user" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    <option value="vc">VC — username = password</option>
                    <option value="up">UP — username ≠ password</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Panjang kode</label>
                <select name="userl" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    @foreach ([4,5,6,7,8,3] as $l)
                        <option value="{{ $l }}" @selected($l === 6)>{{ $l }} karakter</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Karakter</label>
                <select name="char" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    <option value="mix">mix (a-z + angka)</option>
                    <option value="mix1">MIX1 (A-Z + angka)</option>
                    <option value="mix2">mix2 (a-zA-Z + angka)</option>
                    <option value="lower">lower</option>
                    <option value="upper">UPPER</option>
                    <option value="num">angka saja</option>
                    <option value="upplow">a-zA-Z</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Prefix (opsional)</label>
                <input name="prefix" maxlength="6" placeholder="AN-" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Profile</label>
                <select name="profile" required class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    @forelse ($profiles as $p)
                        <option value="{{ $p['name'] ?? '' }}">{{ $p['name'] ?? '' }}</option>
                    @empty
                        <option value="">Belum ada profile — buat di tab Profile</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Server</label>
                <select name="server" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    <option value="all">all</option>
                    @foreach ($servers as $s)
                        <option value="{{ $s['name'] ?? '' }}">{{ $s['name'] ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Time limit</label>
                <input name="timelimit" placeholder="kosong = ikut profile" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Data limit</label>
                <div class="mt-1 flex gap-2">
                    <input type="number" step="0.1" name="datalimit" placeholder="0" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    <select name="mbgb" class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-2">
                        <option value="1048576">MB</option>
                        <option value="1073741824">GB</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Catatan batch</label>
                <input name="adcomment" placeholder="jualan-pasaran" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="w-full text-xs font-bold px-4 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white" @disabled(count($profiles) === 0)>
                    <i class="fa-solid fa-bolt mr-1"></i> Generate ke MikroTik
                </button>
            </div>
        </form>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm text-xs text-slate-600 dark:text-slate-300 space-y-2">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Catatan</h3>
        <p>Voucher dibuat langsung di MikroTik <span class="font-mono">/ip/hotspot/user</span>.</p>
        <p>Harga & masa aktif mengikuti <strong>Profile</strong> (on-login), bukan form generate.</p>
        <p>Satu batch punya comment sama — dipakai untuk cetak / hapus massal.</p>
        <a href="{{ $voucherUrl('profiles') }}" class="inline-flex font-bold text-indigo-600 hover:underline">Kelola profile dulu →</a>
    </div>
</div>
