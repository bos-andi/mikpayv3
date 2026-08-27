@extends('layouts.panel')

@section('title', 'Remote OLT & MikroTik — MikPay')
@section('page', 'remote')

@section('content')
@php
    $tunnelAccount = $tunnelAccount ?? null;
    $tunnelScripts = $tunnelScripts ?? ['ros6' => '', 'ros7' => ''];
    $tunnelOnline = $tunnelAccount && $tunnelAccount->isUsable();
    $primaryMap = $tunnelAccount
        ? ($tunnelAccount->portMaps->firstWhere('label', 'Winbox') ?? $tunnelAccount->portMaps->first())
        : null;
    $apiMap = $tunnelAccount
        ? ($tunnelAccount->portMaps->firstWhere('label', 'API') ?? null)
        : null;
    $oltMap = $tunnelAccount
        ? ($tunnelAccount->portMaps->firstWhere('label', 'OLT') ?? null)
        : null;
    $routerClientMap = $tunnelAccount
        ? ($tunnelAccount->portMaps->firstWhere('label', 'Router Client') ?? null)
        : null;
    $nodeHost = $tunnelAccount?->nodeHostname();
    $ovpnPort = $tunnelAccount?->node?->ovpn_port ?? $tunnelAccount?->server_port;
@endphp

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Setup OpenVPN & Remote MikroTik</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ $tunnelBundleNote ?? 'Bonus gratis OpenVPN untuk langganan MikPay. Pasang script di Terminal MikroTik agar bisa di-remote dari mana saja.' }}
        </p>
    </div>
    <span class="px-3 py-1 rounded-full text-xs font-bold flex items-center gap-2 {{ $tunnelOnline ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-amber-500/10 border border-amber-500/20 text-amber-700' }}">
        <span class="w-2 h-2 rounded-full {{ $tunnelOnline ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
        {{ $tunnelOnline ? 'Akun Tunnel Siap' : 'Tunnel Pending' }}
    </span>
</div>

{{-- Wizard 3 langkah --}}
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm">
    <ol class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <li class="flex items-start gap-3 p-3 rounded-xl bg-violet-50/80 dark:bg-violet-500/10 border border-violet-100 dark:border-violet-500/20">
            <span class="w-7 h-7 rounded-lg bg-violet-600 text-white text-xs font-black flex items-center justify-center shrink-0">1</span>
            <div>
                <p class="text-xs font-extrabold text-slate-900 dark:text-white">Salin script</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Pilih ROS 6/7 lalu salin perintah OpenVPN di bawah.</p>
            </div>
        </li>
        <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
            <span class="w-7 h-7 rounded-lg bg-slate-800 dark:bg-slate-700 text-white text-xs font-black flex items-center justify-center shrink-0">2</span>
            <div>
                <p class="text-xs font-extrabold text-slate-900 dark:text-white">Paste di Terminal</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Buka Winbox (lokal sekali) → Terminal → tempel → Enter.</p>
            </div>
        </li>
        <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
            <span class="w-7 h-7 rounded-lg bg-slate-800 dark:bg-slate-700 text-white text-xs font-black flex items-center justify-center shrink-0">3</span>
            <div>
                <p class="text-xs font-extrabold text-slate-900 dark:text-white">Remote & isi API</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Winbox ke URL remote, lalu isi host API di
                    <a href="{{ panel_route('settings') }}?tab=mikrotik" class="text-violet-600 font-bold hover:underline">Settings → Router</a>.
                </p>
            </div>
        </li>
    </ol>
</div>

@if ($tunnelAccount)
    {{-- Kredensial + List — tampilan terang --}}
    <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-violet-50 border border-violet-100 flex items-center justify-center text-violet-600 text-xl shrink-0">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-extrabold text-base text-slate-900">Akun OpenVPN MikPay</h3>
                        <span class="bg-violet-50 text-violet-700 border border-violet-200 text-[10px] px-2 py-0.5 rounded-full font-mono font-bold">TCP · MODE=IP</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Node:
                        <span class="text-violet-700 font-mono font-semibold">{{ $nodeHost }}:{{ $ovpnPort }}</span>
                        · Username sudah siap pakai di script
                    </p>
                </div>
            </div>
            <button type="button" onclick="copyText(@js($tunnelAccount->plainPassword()))" class="text-xs font-bold px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700">
                <i class="fa-regular fa-copy mr-1"></i> Salin Password
            </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-[11px] min-w-[480px]">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                        <th class="px-4 py-3">Username VPN <span class="normal-case font-medium text-slate-400">(permanen)</span></th>
                        <th class="px-4 py-3">Password</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-white">
                        <td class="px-4 py-3 font-mono font-semibold text-violet-700">
                            <button type="button" onclick="copyText(@js($tunnelAccount->username))" class="hover:text-violet-900 text-left">{{ $tunnelAccount->username }}</button>
                            <p class="text-[10px] text-slate-400 font-sans font-normal mt-1">Tidak dapat diubah setelah dibuat</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-800">
                            <button type="button" onclick="copyText(@js($tunnelAccount->plainPassword()))" class="hover:text-violet-700">{{ $tunnelAccount->plainPassword() }}</button>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tunnelOnline ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $tunnelOnline ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                                {{ strtoupper($tunnelAccount->status) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Port Remote (setelah tunnel connect)</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
                @foreach ($tunnelAccount->portMaps as $map)
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                        <div class="flex justify-between items-center text-slate-500">
                            <span class="font-semibold text-slate-900">{{ $map->label }}</span>
                            <span class="text-[10px] font-mono bg-violet-50 text-violet-700 border border-violet-100 px-1.5 py-0.5 rounded">:{{ $map->target_port }}</span>
                        </div>
                        <div class="font-mono text-xs text-amber-800 truncate bg-white p-1.5 rounded border border-slate-200 select-all">{{ $map->endpoint() }}</div>
                        <button type="button" onclick="copyText(@js($map->endpoint()))" class="w-full bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 py-1 rounded text-[11px] font-semibold transition">
                            <i class="fa-regular fa-copy mr-1"></i> Salin
                        </button>
                    </div>
                @endforeach
            </div>
            @if ($apiMap)
                <p class="text-[11px] text-slate-500 mt-3">
                    Untuk Settings → Router, isi host:
                    <button type="button" onclick="copyText(@js($apiMap->endpoint()))" class="font-mono font-bold text-violet-700 hover:underline">{{ $apiMap->endpoint() }}</button>
                    (API :{{ $apiMap->target_port }})
                </p>
            @endif
        </div>
    </div>
@else
    <div class="rounded-2xl border border-dashed border-amber-300 bg-amber-50 p-6 text-center space-y-2">
        <p class="text-sm font-bold text-amber-900">Akun tunnel belum tersedia</p>
        <p class="text-xs text-amber-800/80">{{ $tunnelBundleNote ?? 'Aktifkan langganan MikPay agar 1 slot OpenVPN gratis dibuat otomatis.' }}</p>
    </div>
@endif

{{-- Script Terminal — langkah inti --}}
<div id="ovpn-setup" class="bg-white dark:bg-slate-900 border border-violet-200 dark:border-violet-500/30 rounded-2xl p-5 shadow-sm space-y-4 ring-1 ring-violet-500/10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span class="w-6 h-6 rounded-md bg-violet-600 text-white text-[10px] font-black flex items-center justify-center">2</span>
                <i class="fa-solid fa-terminal text-violet-600 dark:text-violet-400"></i>
                Script Terminal MikroTik
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Untuk MikroTik yang belum pernah dikonfig MikPay — paste sekali: OVPN + firewall + NAT OLT/Router Client.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <select id="vpnVersion" onchange="MikPay.switchVpnScript()" class="bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs rounded-xl p-2 font-medium text-slate-800 dark:text-slate-200 focus:outline-none focus:border-violet-500">
                <option value="ros7">RouterOS v7 (OpenVPN)</option>
                <option value="ros6">RouterOS v6 (OpenVPN)</option>
            </select>
            <button type="button" onclick="MikPay.copyVpnScript(); showToast && showToast('Script OpenVPN disalin')" class="bg-violet-600 hover:bg-violet-500 text-white text-xs px-3 py-2 rounded-xl font-bold flex items-center gap-1.5 shadow-md shadow-violet-600/20">
                <i class="fa-regular fa-copy"></i> Salin Script
            </button>
        </div>
    </div>
    <div class="relative">
        <textarea id="vpnScriptCode" readonly rows="18" class="w-full bg-slate-950 text-emerald-400 font-mono text-[11px] p-4 rounded-xl border border-slate-800 focus:outline-none resize-y leading-relaxed select-all">{{ $tunnelScripts['ros7'] ?: '# Akun tunnel belum tersedia' }}</textarea>
    </div>
    <div class="rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/80 dark:bg-amber-500/10 px-3 py-2.5 text-[11px] text-amber-950 dark:text-amber-100">
        <p class="font-bold mb-1">Syarat agar remote dari luar jalan:</p>
        <ol class="list-decimal ml-4 space-y-0.5 text-amber-900/80 dark:text-amber-100/80">
            <li>Pastikan script berisi komentar <span class="font-mono font-semibold">MikPay</span> dan <span class="font-mono font-semibold">ovpn-client</span> (bukan sstp/wireguard).</li>
            <li>Salin semua → paste di Terminal MikroTik → cek: <span class="font-mono">/interface ovpn-client print</span> harus <strong class="text-emerald-700 dark:text-emerald-400">R</strong>.</li>
            <li>Baru buka Winbox ke URL remote (port 1000+) / isi host API di Settings.</li>
        </ol>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px] text-slate-500">
        <p>
            Cek status di MikroTik:
            <code class="font-mono text-[10px] bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">/interface ovpn-client print</code>
            — harus <strong class="text-emerald-600">R</strong> (running).
        </p>
        <p>
            Tunnel standalone:
            <a href="https://{{ config('mikpay.tunnel_domain', 'tunnel.mikpay.link') }}" class="text-violet-600 font-bold hover:underline" target="_blank" rel="noopener">tunnel.mikpay.link</a>
        </p>
    </div>
</div>

{{-- Langkah 3 CTA --}}
<div class="bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-start gap-3">
        <span class="w-7 h-7 rounded-lg bg-slate-800 text-white text-xs font-black flex items-center justify-center shrink-0">3</span>
        <div>
            <p class="text-sm font-bold text-slate-900 dark:text-white">Hubungkan MikPay ke router via API</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Setelah tunnel running, tambah/edit router di Settings. Pakai host remote API
                @if ($apiMap)
                    (<span class="font-mono font-semibold text-violet-600">{{ $apiMap->endpoint() }}</span>)
                @else
                    (lihat port API di atas)
                @endif
                — bukan IP lokal.
            </p>
        </div>
    </div>
    <a href="{{ panel_route('settings') }}?tab=mikrotik" class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shrink-0">
        Buka Settings → Router <i class="fa-solid fa-arrow-right text-[10px]"></i>
    </a>
</div>

<script>
    window.__tunnelScripts = @js($tunnelScripts);
    function switchVpnScript() {
        const v = document.getElementById('vpnVersion')?.value || 'ros7';
        const ta = document.getElementById('vpnScriptCode');
        if (ta && window.__tunnelScripts) ta.value = window.__tunnelScripts[v] || '';
    }
    function copyVpnScript() {
        const ta = document.getElementById('vpnScriptCode');
        if (!ta) return;
        navigator.clipboard.writeText(ta.value).then(() => showToast && showToast('Script OpenVPN disalin — paste di Terminal MikroTik'));
    }
    function copyText(t) {
        navigator.clipboard.writeText(t).then(() => showToast && showToast('Disalin'));
    }
    if (location.hash === '#ovpn-setup') {
        document.getElementById('ovpn-setup')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
</script>

{{-- OLT & perangkat LAN via tunnel --}}
<div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-4">
    <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">OLT &amp; Router Client (jarak jauh)</h3>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 max-w-2xl">
            Akses web OLT lewat tunnel — sama seperti mytunnel. Alur:
            <span class="font-mono text-violet-700 dark:text-violet-400">browser → an-1:1002 → VPS → tunnel IP:25 → NAT MikroTik → IP LAN OLT</span>.
            Monitoring ONU/TR-069 di panel belum aktif; yang siap dipakai sekarang adalah buka web OLT langsung.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 space-y-3 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-tower-broadcast"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Web OLT</h4>
                        <p class="text-[11px] text-slate-500">Port publik → target tunnel :{{ $oltMap?->target_port ?? 25 }}</p>
                    </div>
                </div>
                @if ($tunnelOnline && $oltMap)
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">SIAP</span>
                @else
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">TUNNEL OFF</span>
                @endif
            </div>
            @if ($oltMap)
                <div class="font-mono text-xs text-amber-900 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2 select-all">{{ $oltMap->endpoint() }}</div>
                <div class="flex flex-wrap gap-2">
                    <a href="http://{{ $oltMap->endpoint() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs px-3 py-2 rounded-xl transition">
                        <i class="fa-solid fa-external-link"></i> Buka Web OLT
                    </a>
                    <button type="button" onclick="copyText(@js($oltMap->endpoint()))" class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-semibold text-xs px-3 py-2 rounded-xl transition">
                        <i class="fa-regular fa-copy"></i> Salin
                    </button>
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Di MikroTik, rule <span class="font-semibold">MikPay REMOTE OLT</span> harus mengarah ke IP LAN OLT yang benar
                    (<code class="text-[10px] bg-slate-100 px-1 rounded">/ip firewall nat print where comment~"OLT"</code>).
                </p>
            @else
                <p class="text-xs text-slate-500">Port map OLT belum ada. Refresh halaman Remote setelah akun tunnel dibuat.</p>
            @endif
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 space-y-3 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-router"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Router Client</h4>
                        <p class="text-[11px] text-slate-500">Port publik → target tunnel :{{ $routerClientMap?->target_port ?? 19 }}</p>
                    </div>
                </div>
                @if ($tunnelOnline && $routerClientMap)
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">SIAP</span>
                @else
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">TUNNEL OFF</span>
                @endif
            </div>
            @if ($routerClientMap)
                <div class="font-mono text-xs text-violet-900 bg-violet-50 border border-violet-100 rounded-xl px-3 py-2 select-all">{{ $routerClientMap->endpoint() }}</div>
                <div class="flex flex-wrap gap-2">
                    <a href="http://{{ $routerClientMap->endpoint() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 bg-violet-600 hover:bg-violet-500 text-white font-bold text-xs px-3 py-2 rounded-xl transition">
                        <i class="fa-solid fa-external-link"></i> Buka Router Client
                    </a>
                    <button type="button" onclick="copyText(@js($routerClientMap->endpoint()))" class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-semibold text-xs px-3 py-2 rounded-xl transition">
                        <i class="fa-regular fa-copy"></i> Salin
                    </button>
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Sama seperti OLT: NAT di MikroTik harus mengarah ke IP perangkat LAN (router pelanggan / CPE).
                </p>
            @else
                <p class="text-xs text-slate-500">Port map Router Client belum ada.</p>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 p-4 text-[11px] text-slate-600 dark:text-slate-400 space-y-1">
        <p class="font-bold text-slate-800 dark:text-slate-200 text-xs">Catatan singkat</p>
        <p>• <span class="font-mono">:1001</span> = API RouterOS untuk Settings → Router (bukan browser OLT).</p>
        <p>• <span class="font-mono">:1002</span> = web management OLT (yang sudah Anda uji).</p>
        <p>• Kartu “New ONU / Reboot Modem / TR-069” sebelumnya hanya mock UI — belum baca OLT sungguhan.</p>
    </div>
</div>
@endsection
