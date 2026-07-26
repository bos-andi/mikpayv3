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
    {{-- Kredensial + List --}}
    <div class="p-5 rounded-2xl bg-gradient-to-r from-violet-900 via-slate-900 to-slate-900 text-white border border-violet-500/30 shadow-lg space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-violet-600/30 border border-violet-500/40 flex items-center justify-center text-violet-300 text-xl shrink-0">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-extrabold text-base text-white">Akun OpenVPN MikPay</h3>
                        <span class="bg-violet-500/20 text-violet-200 border border-violet-500/40 text-[10px] px-2 py-0.5 rounded-full font-mono font-bold">TCP · MODE=IP</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Node:
                        <span class="text-violet-200 font-mono">{{ $nodeHost }}:{{ $ovpnPort }}</span>
                        · Username sudah siap pakai di script
                    </p>
                </div>
            </div>
            <button type="button" onclick="copyText(@js($tunnelAccount->plainPassword()))" class="text-xs font-bold px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/10">
                <i class="fa-regular fa-copy mr-1"></i> Salin Password
            </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-800">
            <table class="w-full text-left text-[11px] min-w-[640px]">
                <thead>
                    <tr class="bg-slate-950/80 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800">
                                        <th class="px-4 py-3">Username VPN <span class="normal-case font-medium text-slate-500">(permanen)</span></th>
                                        <th class="px-4 py-3">Password</th>
                                        <th class="px-4 py-3">URL Remote (Winbox)</th>
                                        <th class="px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-4 py-3 font-mono font-semibold text-violet-100">
                                            <button type="button" onclick="copyText(@js($tunnelAccount->username))" class="hover:text-white text-left">{{ $tunnelAccount->username }}</button>
                                            <p class="text-[10px] text-slate-500 font-sans font-normal mt-1">Tidak dapat diubah setelah dibuat</p>
                                        </td>
                        <td class="px-4 py-3 font-mono text-slate-200">
                            <button type="button" onclick="copyText(@js($tunnelAccount->plainPassword()))" class="hover:text-violet-300">{{ $tunnelAccount->plainPassword() }}</button>
                        </td>
                        <td class="px-4 py-3">
                            @if ($primaryMap)
                                <button type="button" onclick="copyText(@js($primaryMap->endpoint()))" class="inline-flex items-center gap-2 font-mono text-amber-300 hover:text-amber-200">
                                    <span>{{ $primaryMap->endpoint() }}</span>
                                    <i class="fa-solid fa-right-left text-[9px] text-violet-400"></i>
                                    <span class="text-emerald-400">:{{ $primaryMap->target_port }}</span>
                                </button>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tunnelOnline ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/15 text-amber-300 border border-amber-500/30' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $tunnelOnline ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                                {{ strtoupper($tunnelAccount->status) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3">Port Remote (setelah tunnel connect)</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
                @foreach ($tunnelAccount->portMaps as $map)
                    <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2">
                        <div class="flex justify-between items-center text-slate-400">
                            <span class="font-semibold text-white">{{ $map->label }}</span>
                            <span class="text-[10px] font-mono bg-violet-500/20 text-violet-200 px-1.5 py-0.5 rounded">:{{ $map->target_port }}</span>
                        </div>
                        <div class="font-mono text-xs text-amber-400 truncate bg-slate-900 p-1.5 rounded border border-slate-800 select-all">{{ $map->endpoint() }}</div>
                        <button type="button" onclick="copyText(@js($map->endpoint()))" class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 py-1 rounded text-[11px] font-semibold transition">
                            <i class="fa-regular fa-copy mr-1"></i> Salin
                        </button>
                    </div>
                @endforeach
            </div>
            @if ($apiMap)
                <p class="text-[11px] text-slate-400 mt-3">
                    Untuk Settings → Router, isi host:
                    <button type="button" onclick="copyText(@js($apiMap->endpoint()))" class="font-mono font-bold text-violet-300 hover:underline">{{ $apiMap->endpoint() }}</button>
                    (API :{{ $apiMap->target_port }})
                </p>
            @endif
        </div>
    </div>
@else
    <div class="rounded-2xl border border-dashed border-amber-300 bg-amber-50 dark:bg-amber-500/10 dark:border-amber-500/30 p-6 text-center space-y-2">
        <p class="text-sm font-bold text-amber-900 dark:text-amber-200">Akun tunnel belum tersedia</p>
        <p class="text-xs text-amber-800/80 dark:text-amber-200/70">{{ $tunnelBundleNote ?? 'Aktifkan langganan MikPay agar 1 slot OpenVPN gratis dibuat otomatis.' }}</p>
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
                Paste sekali di Terminal Winbox. Setelah connect, router bisa di-remote tanpa harus di jaringan lokal.
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
        <textarea id="vpnScriptCode" readonly rows="5" class="w-full bg-slate-950 text-emerald-400 font-mono text-[11px] p-4 rounded-xl border border-slate-800 focus:outline-none resize-none leading-relaxed select-all">{{ $tunnelScripts['ros7'] ?: '# Akun tunnel belum tersedia' }}</textarea>
    </div>
    <div class="rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/80 dark:bg-amber-500/10 px-3 py-2.5 text-[11px] text-amber-950 dark:text-amber-100">
        <p class="font-bold mb-1">Syarat agar remote dari luar jalan:</p>
        <ol class="list-decimal ml-4 space-y-0.5 text-amber-900/80 dark:text-amber-100/80">
            <li>Pastikan script berisi <span class="font-mono font-semibold">ovpn-client</span> (bukan sstp/wireguard).</li>
            <li>Paste → di MikroTik: <span class="font-mono">/interface ovpn-client print</span> harus <strong class="text-emerald-700 dark:text-emerald-400">R</strong>.</li>
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

{{-- OLT section (preview) --}}
<div class="pt-2 border-t border-slate-200 dark:border-slate-800">
    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">OLT &amp; ONU Remote</h3>
    <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-4">Fitur monitoring OLT menyusul — butuh tunnel + API router aktif dulu.</p>

    <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-500 dark:text-amber-400 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-wifi text-lg"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-amber-300">New ONU Discovered on PON 2!</h4>
                <p class="text-xs text-slate-600 dark:text-slate-300">Serial Number: <span class="font-mono bg-amber-100 dark:bg-amber-950 text-amber-900 dark:text-amber-300 px-1.5 py-0.5 rounded border border-amber-500/30 font-semibold">ZTEG-C9812A4</span> (Optical Power: -18.2 dBm)</p>
            </div>
        </div>
        <button onclick="openOnuModal()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs px-4 py-2 rounded-xl transition shrink-0">
            <i class="fa-solid fa-link mr-1"></i> Registrasikan Ke Pelanggan
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 space-y-4 shadow-sm">
            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-tower-broadcast text-amber-500"></i> Status 4-Port EPON/GPON OLT
            </h3>
            <div class="space-y-2 text-xs">
                @foreach ([1 => '+2.5', 2 => '+2.4', 3 => '+2.6', 4 => '+2.3'] as $pon => $tx)
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <p class="font-bold text-slate-800 dark:text-slate-200">PON Port {{ $pon }}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400">ONU Connected</p>
                        </div>
                        <span class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">{{ $tx }} dBm Tx</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 space-y-4 shadow-sm">
            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-indigo-600 dark:text-indigo-400"></i> Remote Action Modem Pelanggan (TR-069 & SNMP)
            </h3>
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-3">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                    <div>
                        <h4 class="font-bold text-xs text-slate-900 dark:text-white">Target Pelanggan: Budi Santoso (PPPoE: budi_home)</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Modem: ZTE F660 V6 | SN: ZTEGC88112233 | ODP-BLOK-A1 Port 1</p>
                    </div>
                    <span class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[10px] px-2 py-0.5 rounded-full font-bold">ONLINE (-17.8 dBm)</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2">
                    <button onclick="showToast('Perintah Reboot Modem telah dikirim via TR-069')" class="p-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-800 dark:text-slate-200 transition text-center space-y-1 shadow-sm">
                        <i class="fa-solid fa-power-off text-rose-500 text-base block"></i>
                        <span>Reboot Modem</span>
                    </button>
                    <button onclick="showToast('Sinyal optik dipindai: RX -17.8 dBm (EXCELLENT)')" class="p-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-800 dark:text-slate-200 transition text-center space-y-1 shadow-sm">
                        <i class="fa-solid fa-wave-square text-cyan-500 text-base block"></i>
                        <span>Cek Sinyal dBm</span>
                    </button>
                    <button onclick="showToast('Form Ganti Nama & Password Wifi Dibuka')" class="p-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-800 dark:text-slate-200 transition text-center space-y-1 shadow-sm">
                        <i class="fa-solid fa-key text-amber-500 text-base block"></i>
                        <span>Ganti Pass Wifi</span>
                    </button>
                    <button onclick="showToast('Membaca Port Forwarding & NAT')" class="p-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-800 dark:text-slate-200 transition text-center space-y-1 shadow-sm">
                        <i class="fa-solid fa-route text-indigo-500 text-base block"></i>
                        <span>Set Port Forward</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
