@extends('layouts.panel')

@section('title', 'Remote OLT & MikroTik — MikPay')
@section('page', 'remote')

@section('content')
@php
    $tunnelAccount = $tunnelAccount ?? null;
    $tunnelScripts = $tunnelScripts ?? ['ros6' => '', 'ros7' => ''];
    $routerConnected = ($tenant?->vpn_status ?? null) === 'online';
    $portMaps = $tunnelAccount?->portMaps ?? collect();
    $apiMap = $portMaps->firstWhere('label', 'API');
    $portHelp = [
        'Winbox' => ['icon' => 'fa-desktop', 'text' => 'Buka Winbox, isi kolom Connect To dengan alamat ini.', 'web' => false],
        'API' => ['icon' => 'fa-plug', 'text' => 'Untuk MikPay. Isi di Pengaturan → Router MikroTik.', 'web' => false],
        'OLT' => ['icon' => 'fa-tower-broadcast', 'text' => 'Buka halaman web OLT dari browser.', 'web' => true],
        'Router Client' => ['icon' => 'fa-wifi', 'text' => 'Buka halaman web perangkat di LAN.', 'web' => true],
    ];
    $card = 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm';
    $stepBadge = 'w-7 h-7 rounded-full text-white text-xs font-black flex items-center justify-center shrink-0';
@endphp

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Remote MikroTik & OLT</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Ikuti 3 langkah di bawah agar MikroTik bisa diakses dari mana saja dan terhubung ke MikPay.
        </p>
    </div>
    <span class="px-3 py-1 rounded-full text-xs font-bold flex items-center gap-2 {{ $routerConnected ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-300' }}">
        <span class="w-2 h-2 rounded-full {{ $routerConnected ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
        {{ $routerConnected ? 'MikroTik Terhubung' : 'MikroTik Belum Terhubung' }}
    </span>
</div>

@if (! $tunnelAccount)
    <div class="rounded-2xl border border-dashed border-amber-300 bg-amber-50 dark:bg-amber-500/10 dark:border-amber-500/30 p-6 text-center space-y-2">
        <p class="text-sm font-bold text-amber-900 dark:text-amber-200">Akun VPN belum tersedia</p>
        <p class="text-xs text-amber-800/80 dark:text-amber-200/80">{{ $tunnelBundleNote ?? 'Aktifkan langganan MikPay agar akun VPN gratis dibuat otomatis.' }}</p>
    </div>
@else
    {{-- Langkah 1 --}}
    <div id="ovpn-setup" class="{{ $card }} p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="{{ $stepBadge }} bg-indigo-600">1</span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Salin script lalu tempel di Terminal MikroTik</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        Buka <strong>Winbox</strong> (dari jaringan lokal) → klik <strong>New Terminal</strong> → tempel script → tekan <strong>Enter</strong>.
                        Cukup dilakukan sekali.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <select id="vpnVersion" onchange="MikPay.switchVpnScript()" class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-xs rounded-xl px-3 py-2 font-medium text-slate-800 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    <option value="ros7">RouterOS v7</option>
                    <option value="ros6">RouterOS v6</option>
                </select>
                <button type="button" onclick="copyVpnScript()" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs px-4 py-2 rounded-xl font-bold flex items-center gap-1.5 shadow-md shadow-indigo-600/20">
                    <i class="fa-regular fa-copy"></i> Salin Script
                </button>
            </div>
        </div>
        <textarea id="vpnScriptCode" readonly rows="8" class="w-full bg-slate-950 text-emerald-400 font-mono text-[11px] p-4 rounded-xl border border-slate-800 focus:outline-none resize-y leading-relaxed">{{ $tunnelScripts['ros7'] ?: '# Script belum tersedia' }}</textarea>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
            <i class="fa-solid fa-circle-info text-indigo-500 mr-1"></i>
            Tidak tahu versi RouterOS? Di Winbox buka menu <strong>System → Resources</strong>, lihat baris <strong>Version</strong>.
        </p>
    </div>

    {{-- Langkah 2 --}}
    <div class="{{ $card }} p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="{{ $stepBadge }} bg-indigo-600">2</span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pastikan VPN sudah tersambung</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        Tunggu ±1 menit lalu refresh halaman ini. Status di kanan atas harus <strong class="text-emerald-600">MikroTik Terhubung</strong>.
                        Bisa juga dicek di Terminal dengan perintah
                        <button type="button" onclick="copyText('/interface ovpn-client print')" class="font-mono text-[11px] bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded hover:bg-slate-200" title="Salin">/interface ovpn-client print</button>
                        — harus ada huruf <strong>R</strong>.
                    </p>
                </div>
            </div>
            <span class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold {{ $routerConnected ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:border-slate-700' }}">
                <i class="fa-solid {{ $routerConnected ? 'fa-circle-check' : 'fa-hourglass-half' }}"></i>
                {{ $routerConnected ? 'Tersambung' : 'Menunggu' }}
            </span>
        </div>
    </div>

    {{-- Langkah 3 --}}
    <div class="{{ $card }} p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="{{ $stepBadge }} bg-indigo-600">3</span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Hubungkan router ke MikPay</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        Buka Pengaturan → Router MikroTik, lalu isi <strong>Host</strong> dengan alamat
                        @if ($apiMap)
                            <button type="button" onclick="copyText(@js($apiMap->endpoint()))" class="font-mono font-bold text-indigo-600 dark:text-indigo-400 hover:underline" title="Salin">{{ $apiMap->endpoint() }}</button>
                        @else
                            API di bawah
                        @endif
                        beserta username &amp; password MikroTik Anda.
                    </p>
                </div>
            </div>
            <a href="{{ panel_route('settings') }}?tab=mikrotik" class="shrink-0 inline-flex items-center justify-center gap-2 bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 text-white dark:text-slate-900 text-xs font-bold px-4 py-2.5 rounded-xl">
                Buka Pengaturan Router <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>

    {{-- Alamat remote --}}
    @if ($portMaps->isNotEmpty())
        <div class="{{ $card }} p-5 space-y-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Alamat Remote Anda</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Bisa dipakai dari mana saja setelah VPN tersambung.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                @foreach ($portMaps as $map)
                    @php $help = $portHelp[$map->label] ?? ['icon' => 'fa-link', 'text' => '', 'web' => false]; @endphp
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs">
                                <i class="fa-solid {{ $help['icon'] }}"></i>
                            </span>
                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $map->label }}</span>
                        </div>
                        <div class="font-mono text-xs font-semibold text-slate-800 dark:text-slate-100 bg-white dark:bg-slate-900 px-2 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 truncate select-all">{{ $map->endpoint() }}</div>
                        @if ($help['text'])
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug flex-1">{{ $help['text'] }}</p>
                        @endif
                        <div class="flex gap-2">
                            <button type="button" onclick="copyText(@js($map->endpoint()))" class="flex-1 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 py-1.5 rounded-lg text-[11px] font-semibold">
                                <i class="fa-regular fa-copy mr-1"></i> Salin
                            </button>
                            @if ($help['web'])
                                <a href="http://{{ $map->endpoint() }}" target="_blank" rel="noopener" class="flex-1 text-center bg-indigo-600 hover:bg-indigo-500 text-white py-1.5 rounded-lg text-[11px] font-semibold">
                                    <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> Buka
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <details class="group rounded-xl border border-slate-200 dark:border-slate-700">
                <summary class="cursor-pointer list-none px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-300 flex items-center justify-between">
                    <span><i class="fa-solid fa-key mr-1.5 text-slate-400"></i> Detail akun VPN (sudah otomatis ada di script)</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform group-open:rotate-180"></i>
                </summary>
                <div class="px-4 pb-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Username</p>
                        <button type="button" onclick="copyText(@js($tunnelAccount->username))" class="font-mono font-semibold text-slate-800 dark:text-slate-100 hover:text-indigo-600 text-left break-all">{{ $tunnelAccount->username }}</button>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Password</p>
                        <button type="button" onclick="copyText(@js($tunnelAccount->plainPassword()))" class="font-mono font-semibold text-slate-800 dark:text-slate-100 hover:text-indigo-600">{{ $tunnelAccount->plainPassword() }}</button>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Server</p>
                        <span class="font-mono font-semibold text-slate-800 dark:text-slate-100">{{ $tunnelAccount->nodeHostname() }}:{{ $tunnelAccount->node?->ovpn_port ?? $tunnelAccount->server_port }}</span>
                    </div>
                </div>
            </details>
        </div>
    @endif
@endif

<script>
    window.__tunnelScripts = @js($tunnelScripts);
    function copyVpnScript() {
        const ta = document.getElementById('vpnScriptCode');
        if (!ta) return;
        navigator.clipboard.writeText(ta.value).then(() => showToast && showToast('Script disalin — tempel di Terminal MikroTik'));
    }
    function copyText(t) {
        navigator.clipboard.writeText(t).then(() => showToast && showToast('Disalin'));
    }
    if (location.hash === '#ovpn-setup') {
        document.getElementById('ovpn-setup')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
</script>

{{-- Monitoring OLT --}}
@php
    $oltDevices = $oltDevices ?? collect();
    $oltGisNodes = $oltGisNodes ?? collect();
@endphp
<div class="{{ $card }} p-5 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Monitoring OLT</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                OLT terdeteksi otomatis dari MikroTik. Anda juga bisa menambahkannya manual.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="syncOlts()" class="inline-flex items-center gap-1.5 bg-white dark:bg-slate-800 hover:bg-slate-50 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs px-3 py-2 rounded-xl transition">
                <i class="fa-solid fa-arrows-rotate"></i> Deteksi Ulang
            </button>
            @if ($oltDevices->isNotEmpty())
                <button type="button" onclick="pollAllOlts()" class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs px-3 py-2 rounded-xl transition">
                    <i class="fa-solid fa-signal"></i> Cek Status
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div>
            <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Perangkat Terdaftar</h4>
            @if ($oltDevices->isEmpty())
                <p class="text-xs text-slate-500 py-6 text-center rounded-xl border border-dashed border-slate-200 dark:border-slate-700">Belum ada OLT terdaftar.</p>
            @else
                <div class="space-y-2">
                    @foreach ($oltDevices as $device)
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $device->name }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">
                                        {{ strtoupper($device->vendor) }}
                                        @if ($device->management_ip)
                                            · <span class="font-mono">{{ $device->management_ip }}:{{ $device->web_port }}</span>
                                        @endif
                                        @if (($device->meta['auto_detected'] ?? false))
                                            <span class="text-emerald-600 font-bold">· Otomatis</span>
                                        @endif
                                    </p>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border
                                    {{ $device->status === 'online' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($device->status === 'offline' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-100 text-slate-500 border-slate-200') }}">
                                    {{ $device->statusLabel() }}
                                </span>
                            </div>
                            @if ($device->last_polled_at)
                                <p class="text-[10px] text-slate-400 mt-2">
                                    Terakhir dicek: {{ format_wib($device->last_polled_at) }}
                                    @if ($device->avg_rx_power)
                                        · Rx rata-rata {{ number_format($device->avg_rx_power, 1) }} dBm
                                    @endif
                                </p>
                            @endif
                            @if ($device->poll_message)
                                <p class="text-[10px] text-slate-500 mt-1">{{ $device->poll_message }}</p>
                            @endif
                            <div class="flex flex-wrap gap-2 mt-2">
                                <button type="button" onclick="pollOlt({{ $device->id }})" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-amber-500 text-slate-950">
                                    Cek Sekarang
                                </button>
                                <form method="POST" action="{{ panel_route('olt.destroy', $device) }}" class="inline" onsubmit="return MikPay.confirmForm(event, 'Hapus OLT {{ $device->name }}?', { title: 'Hapus OLT', variant: 'danger', confirmText: 'Hapus' })">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg border border-rose-300 text-rose-600">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <details class="group rounded-xl border border-slate-200 dark:border-slate-700 self-start" @if ($oltDevices->isEmpty()) open @endif>
            <summary class="cursor-pointer list-none px-4 py-3 text-xs font-bold text-slate-700 dark:text-slate-200 flex items-center justify-between">
                <span><i class="fa-solid fa-plus mr-1.5 text-amber-500"></i> Tambah OLT Manual</span>
                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform group-open:rotate-180"></i>
            </summary>
            <form method="POST" action="{{ panel_route('olt.store') }}" class="px-4 pb-4 space-y-3">
                @csrf
                <div>
                    <label class="text-[11px] font-bold text-slate-500">Nama OLT</label>
                    <input type="text" name="name" required placeholder="OLT Utama"
                           class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-3 py-2">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-slate-500">Merek</label>
                        <select name="vendor" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-3 py-2">
                            <option value="hsgq">HSGQ</option>
                            <option value="hioso">Hioso</option>
                            <option value="zte">ZTE</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-slate-500">IP OLT</label>
                        <input type="text" name="management_ip" placeholder="192.168.1.2"
                               class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-3 py-2 font-mono">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-slate-500">Port Web</label>
                        <input type="number" name="web_port" value="80" min="1" max="65535"
                               class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-3 py-2">
                    </div>
                </div>
                @if ($oltGisNodes->isNotEmpty())
                    <div>
                        <label class="text-[11px] font-bold text-slate-500">Hubungkan ke titik OLT di Peta GIS</label>
                        <select name="odp_id" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-3 py-2">
                            <option value="">— Tidak dihubungkan —</option>
                            @foreach ($oltGisNodes as $node)
                                <option value="{{ $node->id }}">{{ $node->code }}{{ $node->name ? ' · '.$node->name : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs py-2.5 rounded-xl">
                    <i class="fa-solid fa-plus mr-1"></i> Tambah OLT
                </button>
            </form>
        </details>
    </div>
</div>

<script>
async function syncOlts() {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    try {
        const res = await fetch('{{ panel_route('olt.sync') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (showToast) showToast(data.message || 'Deteksi OLT selesai');
        setTimeout(() => location.reload(), 800);
    } catch (e) {
        if (showToast) showToast('Gagal mendeteksi OLT dari MikroTik');
    }
}
async function pollOlt(id) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    try {
        const res = await fetch(`{{ panel_route('olt.poll', ['olt' => '__ID__']) }}`.replace('__ID__', id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (showToast) showToast(data.message || 'Pengecekan selesai');
        setTimeout(() => location.reload(), 800);
    } catch (e) {
        if (showToast) showToast('Gagal mengecek OLT');
    }
}
async function pollAllOlts() {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    try {
        const res = await fetch('{{ panel_route('olt.poll-all') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        });
        const data = await res.json();
        const s = data.summary || {};
        if (showToast) showToast(`Cek selesai: ${s.online || 0} online, ${s.offline || 0} offline`);
        setTimeout(() => location.reload(), 800);
    } catch (e) {
        if (showToast) showToast('Gagal mengecek semua OLT');
    }
}
</script>
@endsection
