<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex justify-between">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Hotspot aktif</h3>
        <span class="text-[10px] text-slate-400">{{ count($active) }} sesi</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left min-w-[640px]">
            <thead class="bg-slate-50 dark:bg-slate-950/60 text-[10px] uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="px-4 py-2.5 font-bold">User</th>
                    <th class="px-4 py-2.5 font-bold">Address</th>
                    <th class="px-4 py-2.5 font-bold">MAC</th>
                    <th class="px-4 py-2.5 font-bold">Uptime</th>
                    <th class="px-4 py-2.5 font-bold">Bytes</th>
                    <th class="px-4 py-2.5 font-bold">Server</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($active as $a)
                    <tr>
                        <td class="px-4 py-2.5 font-mono font-bold">{{ $a['user'] ?? '' }}</td>
                        <td class="px-4 py-2.5 font-mono">{{ $a['address'] ?? '' }}</td>
                        <td class="px-4 py-2.5 font-mono text-[10px]">{{ $a['mac-address'] ?? '' }}</td>
                        <td class="px-4 py-2.5">{{ $a['uptime'] ?? '' }}</td>
                        <td class="px-4 py-2.5 tabular-nums">↓ {{ $a['bytes-in'] ?? 0 }} / ↑ {{ $a['bytes-out'] ?? 0 }}</td>
                        <td class="px-4 py-2.5">{{ $a['server'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Tidak ada sesi hotspot aktif.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
