<div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
    <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Profile hotspot di router</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left min-w-[520px]">
                <thead class="bg-slate-50 dark:bg-slate-950/60 text-[10px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5 font-bold">Nama</th>
                        <th class="px-4 py-2.5 font-bold">Shared</th>
                        <th class="px-4 py-2.5 font-bold">Rate limit</th>
                        <th class="px-4 py-2.5 font-bold">Pool</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($profiles as $p)
                        <tr>
                            <td class="px-4 py-2.5 font-semibold">
                                <a href="{{ $voucherUrl('users', ['profile' => $p['name'] ?? '', 'comment' => '', 'exp' => null]) }}" class="text-indigo-600 hover:underline">{{ $p['name'] ?? '' }}</a>
                            </td>
                            <td class="px-4 py-2.5">{{ $p['shared-users'] ?? '—' }}</td>
                            <td class="px-4 py-2.5 font-mono">{{ $p['rate-limit'] ?? '—' }}</td>
                            <td class="px-4 py-2.5">{{ $p['address-pool'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Belum ada profile.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-3">Tambah profile</h3>
        <form method="POST" action="{{ panel_route('vouchers.profiles.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Nama profile</label>
                <input name="name" required placeholder="1Hari-5rb" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Shared users</label>
                    <input type="number" name="sharedusers" value="1" min="1" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Rate limit</label>
                    <input name="ratelimit" placeholder="5M/5M" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                </div>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Address pool</label>
                <select name="ppool" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    <option value="none">none</option>
                    @foreach ($pools as $pool)
                        <option value="{{ $pool['name'] ?? '' }}">{{ $pool['name'] ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Mode expired</label>
                <select name="expmode" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    <option value="0">0 — tanpa expired otomatis</option>
                    <option value="rem">rem — hapus setelah validity</option>
                    <option value="ntf">ntf — notice (limit 1s)</option>
                    <option value="remc" selected>remc — hapus + catat penjualan</option>
                    <option value="ntfc">ntfc — notice + catat penjualan</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Validity</label>
                    <input name="validity" value="1d" placeholder="1d / 12h" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Harga (Rp)</label>
                    <input type="number" name="price" value="5000" min="0" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Harga jual (cetak)</label>
                    <input type="number" name="sprice" value="5000" min="0" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400">Lock MAC</label>
                    <select name="lockunlock" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                        <option value="Disable">Disable</option>
                        <option value="Enable">Enable</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase text-slate-400">Parent queue</label>
                <select name="parent" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                    <option value="none">none</option>
                    @foreach ($queues as $q)
                        <option value="{{ $q['name'] ?? '' }}">{{ $q['name'] ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="w-full text-xs font-bold px-4 py-2.5 rounded-xl bg-indigo-600 text-white">Simpan profile</button>
        </form>
    </div>
</div>
