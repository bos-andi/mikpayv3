<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col lg:flex-row gap-3 lg:items-end lg:justify-between">
    <form method="GET" action="{{ panel_route('vouchers') }}" class="flex flex-wrap gap-2 items-end flex-1">
        <input type="hidden" name="section" value="users">
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Profile</label>
            <select name="profile" class="mt-1 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                <option value="all">Semua</option>
                @foreach ($profiles as $p)
                    @php $pn = $p['name'] ?? ''; @endphp
                    <option value="{{ $pn }}" @selected(($filters['profile'] ?? '') === $pn)>{{ $pn }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Batch comment</label>
            <select id="commentFilter" name="comment" class="mt-1 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 min-w-[180px]">
                <option value="">Semua</option>
                @foreach ($commentOptions as $c => $cnt)
                    <option value="{{ $c }}" @selected(($filters['comment'] ?? '') === $c)>{{ $c }} ({{ $cnt }})</option>
                @endforeach
            </select>
        </div>
        <label class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 pb-2">
            <input type="checkbox" name="exp" value="1" @checked(! empty($filters['exp'])) class="rounded border-slate-300">
            Hanya expired (1s)
        </label>
        <button type="submit" class="text-xs font-bold px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900">Filter</button>
    </form>
    <div class="flex flex-wrap gap-2">
        @php $batchComment = (string) ($filters['comment'] ?? ''); @endphp
        <button type="button"
                onclick="(function(){var c=document.getElementById('commentFilter')?.value||'{{ $batchComment }}'; if(!c){alert('Pilih batch comment dulu.');return;} window.open('{{ panel_route('vouchers.print') }}?id='+encodeURIComponent(c)+'&qr=no','_blank');})()"
                class="text-xs font-bold px-3 py-2 rounded-xl bg-indigo-600 text-white">Cetak</button>
        <button type="button"
                onclick="(function(){var c=document.getElementById('commentFilter')?.value||'{{ $batchComment }}'; if(!c){alert('Pilih batch comment dulu.');return;} window.open('{{ panel_route('vouchers.print') }}?id='+encodeURIComponent(c)+'&qr=yes','_blank');})()"
                class="text-xs font-bold px-3 py-2 rounded-xl bg-violet-600 text-white">Cetak QR</button>
        <button type="button"
                onclick="(function(){var c=document.getElementById('commentFilter')?.value||'{{ $batchComment }}'; if(!c){alert('Pilih batch comment dulu.');return;} window.open('{{ panel_route('vouchers.print') }}?id='+encodeURIComponent(c)+'&small=yes','_blank');})()"
                class="text-xs font-bold px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200">Cetak Small</button>
        <button type="button"
                onclick="(function(){var c=document.getElementById('commentFilter')?.value||'{{ $batchComment }}'; if(!c){alert('Pilih batch comment dulu.');return;} window.open('{{ panel_route('vouchers.print') }}?id='+encodeURIComponent(c)+'&tpl=thermal&qr=yes','_blank');})()"
                class="text-xs font-bold px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200">Thermal</button>
        @if ($batchComment !== '')
            <form method="POST" action="{{ panel_route('vouchers.users.remove-by-comment') }}"
                  onsubmit="return MikPay.confirmForm(event, 'Hapus semua user batch ini?', { variant: 'danger', confirmText: 'Hapus' })">
                @csrf
                <input type="hidden" name="comment" value="{{ $batchComment }}">
                <button type="submit" class="text-xs font-bold px-3 py-2 rounded-xl border border-rose-300 text-rose-600">Hapus batch</button>
            </form>
        @endif
        <form method="POST" action="{{ panel_route('vouchers.users.remove-expired') }}"
              onsubmit="return MikPay.confirmForm(event, 'Hapus semua user expired?', { variant: 'danger', confirmText: 'Hapus' })">
            @csrf
            <button type="submit" class="text-xs font-bold px-3 py-2 rounded-xl border border-amber-300 text-amber-700">Hapus expired</button>
        </form>
    </div>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex justify-between">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Daftar user hotspot</h3>
        <span class="text-[10px] text-slate-400">{{ count($users) }} user</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left min-w-[720px]">
            <thead class="bg-slate-50 dark:bg-slate-950/60 text-[10px] uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="px-4 py-2.5 font-bold">User</th>
                    <th class="px-4 py-2.5 font-bold">Profile</th>
                    <th class="px-4 py-2.5 font-bold">Uptime / Limit</th>
                    <th class="px-4 py-2.5 font-bold">Comment</th>
                    <th class="px-4 py-2.5 font-bold">Status</th>
                    <th class="px-4 py-2.5 font-bold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($users as $u)
                    @php
                        $id = $u['.id'] ?? '';
                        $disabled = strtolower((string) ($u['disabled'] ?? 'false')) === 'true';
                    @endphp
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 {{ $disabled ? 'opacity-60' : '' }}">
                        <td class="px-4 py-2.5">
                            <div class="font-mono font-bold text-slate-800 dark:text-slate-100">{{ $u['name'] ?? '' }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ $u['password'] ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-2.5">{{ $u['profile'] ?? '—' }}</td>
                        <td class="px-4 py-2.5 tabular-nums">
                            <div>{{ $u['uptime'] ?? '0s' }}</div>
                            <div class="text-[10px] text-slate-400">limit: {{ $u['limit-uptime'] ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-2.5 max-w-[180px] truncate">{{ $u['comment'] ?? '—' }}</td>
                        <td class="px-4 py-2.5">
                            @if ($disabled)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-600">OFF</span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600">ON</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            @php
                                $uname = (string) ($u['name'] ?? '');
                                $upass = (string) ($u['password'] ?? $uname);
                                $vuser = ($upass === $uname ? 'vc' : 'up').'-'.$uname;
                            @endphp
                            <a href="{{ panel_route('vouchers.print', ['user' => $vuser, 'qr' => 'no']) }}" target="_blank" class="text-indigo-600 mr-2" title="Cetak"><i class="fa-solid fa-print"></i></a>
                            <a href="{{ panel_route('vouchers.print', ['user' => $vuser, 'qr' => 'yes']) }}" target="_blank" class="text-violet-600 mr-2" title="Cetak QR"><i class="fa-solid fa-qrcode"></i></a>
                            @if ($id)
                                <form method="POST" action="{{ panel_route('vouchers.users.action', ['id' => $id, 'action' => $disabled ? 'enable' : 'disable']) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="profile" value="{{ $filters['profile'] ?? 'all' }}">
                                    <input type="hidden" name="comment" value="{{ $filters['comment'] ?? '' }}">
                                    <button type="submit" class="text-amber-600 mr-2" title="{{ $disabled ? 'Enable' : 'Disable' }}"><i class="fa-solid {{ $disabled ? 'fa-play' : 'fa-pause' }}"></i></button>
                                </form>
                                <form method="POST" action="{{ panel_route('vouchers.users.action', ['id' => $id, 'action' => 'remove']) }}" class="inline"
                                      onsubmit="return MikPay.confirmForm(event, 'Hapus user ini?', { variant: 'danger', confirmText: 'Hapus' })">
                                    @csrf
                                    <button type="submit" class="text-rose-500" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada user hotspot. Generate di tab Generate.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-3">Tambah user manual</h3>
    <form method="POST" action="{{ panel_route('vouchers.users.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        @csrf
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Username</label>
            <input name="name" required class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Password</label>
            <input name="pass" required class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase text-slate-400">Profile</label>
            <select name="profile" required class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
                @foreach ($profiles as $p)
                    <option value="{{ $p['name'] ?? '' }}">{{ $p['name'] ?? '' }}</option>
                @endforeach
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
            <input name="timelimit" placeholder="1d / 12h / 0" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
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
            <label class="text-[10px] font-bold uppercase text-slate-400">Comment</label>
            <input name="comment" placeholder="opsional" class="mt-1 w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full text-xs font-bold px-4 py-2.5 rounded-xl bg-indigo-600 text-white">Simpan user</button>
        </div>
    </form>
</div>
