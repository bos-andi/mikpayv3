@extends('layouts.superadmin')

@section('title', 'Superadmin Dashboard — MikPay')
@section('heading', 'Ringkasan Platform')

@section('content')
@php
    $idr = fn ($v) => 'Rp '.number_format((int) $v, 0, ',', '.');
    $bytes = function ($b) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $b = (float) $b;
        while ($b >= 1024 && $i < count($units) - 1) {
            $b /= 1024;
            $i++;
        }

        return number_format($b, $i >= 3 ? 1 : 0, ',', '.').' '.$units[$i];
    };
    $barColor = fn (int $p) => $p >= 90 ? 'bg-rose-500' : ($p >= 70 ? 'bg-amber-500' : 'bg-emerald-500');
    $hour = (int) wib_now()->format('G');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $host = $status['host'];
    $uptimeDays = intdiv($host['uptime_seconds'], 86400);
    $uptimeHours = intdiv($host['uptime_seconds'] % 86400, 3600);
    $servicesOk = collect($status['services'])->every(fn ($s) => $s['ok']);
    $routers = $status['routers'];
    $payTotal = max(1, array_sum($paymentStats));
    $channelLabels = ['xendit' => 'Xendit', 'andidev_pay' => 'Andidev Pay', 'balance' => 'Saldo', 'superadmin' => 'Manual'];
@endphp

{{-- Hero --}}
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-900 text-white shadow-xl shadow-indigo-900/20 mb-6">
    <div class="absolute -top-24 -right-16 w-72 h-72 rounded-full bg-violet-500/20 blur-3xl" aria-hidden="true"></div>
    <div class="absolute -bottom-24 left-1/3 w-72 h-72 rounded-full bg-indigo-500/20 blur-3xl" aria-hidden="true"></div>
    <div class="relative p-6 sm:p-8 grid grid-cols-1 lg:grid-cols-3 gap-6 items-end">
        <div class="lg:col-span-2">
            <p class="text-xs font-semibold text-indigo-200">{{ wib_now()->locale('id')->translatedFormat('l, d F Y') }}</p>
            <h2 class="mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $greeting }}, Admin 👋</h2>
            <p class="mt-2 text-sm text-slate-300 max-w-xl">
                {{ $activeTenantCount }} dari {{ $tenantCount }} tenant aktif ·
                {{ $routers['online'] }}/{{ $routers['total'] }} router MikroTik terhubung ·
                @if ($servicesOk)
                    <span class="text-emerald-300 font-semibold">semua layanan normal</span>
                @else
                    <span class="text-amber-300 font-semibold">ada layanan yang perlu dicek</span>
                @endif
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('superadmin.payments') }}" class="inline-flex items-center gap-2 rounded-xl bg-white text-slate-900 text-xs font-extrabold px-4 py-2.5 shadow-lg hover:bg-indigo-50 transition">
                    <i class="fa-solid fa-credit-card"></i> Inbox Pembayaran
                    @if ($paymentStats['pending'] > 0)
                        <span class="rounded-full bg-amber-500 text-white text-[10px] px-1.5 py-0.5">{{ $paymentStats['pending'] }}</span>
                    @endif
                </a>
                <a href="{{ route('superadmin.users') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 ring-1 ring-white/15 text-xs font-bold px-4 py-2.5 hover:bg-white/20 transition">
                    <i class="fa-solid fa-users"></i> Tenant
                </a>
                <a href="{{ route('superadmin.subscription-pricing') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 ring-1 ring-white/15 text-xs font-bold px-4 py-2.5 hover:bg-white/20 transition">
                    <i class="fa-solid fa-tags"></i> Paket
                </a>
                <a href="{{ route('superadmin.landing') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 ring-1 ring-white/15 text-xs font-bold px-4 py-2.5 hover:bg-white/20 transition">
                    <i class="fa-solid fa-newspaper"></i> Landing
                </a>
                <a href="{{ route('panel.dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 ring-1 ring-white/15 text-xs font-bold px-4 py-2.5 hover:bg-white/20 transition">
                    <i class="fa-solid fa-display"></i> Panel ISP
                </a>
            </div>
        </div>
        <div class="rounded-2xl bg-white/10 ring-1 ring-white/15 backdrop-blur p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-indigo-200">Pemasukan bulan ini</p>
            <p class="mt-2 text-3xl font-extrabold tabular-nums">{{ $idr($revenue['month']) }}</p>
            <p class="mt-1 text-[11px] text-slate-300">{{ $revenue['month_count'] }} transaksi via payment gateway</p>
            <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px]">
                <span class="text-slate-300">Total sepanjang waktu</span>
                <span class="font-bold tabular-nums">{{ $idr($revenue['total']) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- KPI --}}
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach ([
        ['Total Tenant', $tenantCount, '+'.$newTenantsThisMonth.' bulan ini', 'fa-building', 'from-indigo-500 to-violet-600'],
        ['Tenant Aktif', $activeTenantCount, ($tenantCount > 0 ? round($activeTenantCount / $tenantCount * 100) : 0).'% dari total', 'fa-circle-check', 'from-emerald-500 to-teal-600'],
        ['Total User', $userCount, 'Admin & staf tenant', 'fa-users', 'from-sky-500 to-blue-600'],
        ['Router Online', $routers['online'].'/'.$routers['total'], $routers['offline'] > 0 ? $routers['offline'].' offline' : 'Semua terhubung', 'fa-network-wired', 'from-amber-500 to-orange-600'],
    ] as [$label, $value, $sub, $icon, $gradient])
        <div class="group bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900 tabular-nums">{{ $value }}</p>
                </div>
                <span class="w-11 h-11 shrink-0 rounded-2xl bg-gradient-to-br {{ $gradient }} text-white flex items-center justify-center shadow-md">
                    <i class="fa-solid {{ $icon }}"></i>
                </span>
            </div>
            <p class="mt-3 text-[11px] font-semibold text-slate-500">{{ $sub }}</p>
        </div>
    @endforeach
</div>

{{-- Status server --}}
<div class="flex flex-wrap items-end justify-between gap-3 mb-3">
    <div>
        <h2 class="text-base font-extrabold text-slate-900">Status Server</h2>
        <p class="text-xs text-slate-500">Diperbarui {{ \Carbon\Carbon::parse($status['checked_at'])->timezone(config('mikpay.timezone'))->format('H:i:s') }} WIB · otomatis tiap 1 menit</p>
    </div>
    <a href="{{ route('superadmin.dashboard', ['refresh' => 1]) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700 px-3 py-2 hover:bg-slate-50 transition">
        <i class="fa-solid fa-rotate"></i> Cek sekarang
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
    {{-- Node VPN --}}
    <div class="xl:col-span-2 space-y-4">
        @forelse ($status['nodes'] as $node)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-gradient-to-r from-slate-900 to-indigo-950 text-white flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-11 h-11 rounded-2xl bg-white/10 ring-1 ring-white/15 flex items-center justify-center"><i class="fa-solid fa-server"></i></span>
                        <div>
                            <p class="text-sm font-extrabold">{{ $node['label'] }}
                                @if ($node['is_default'])<span class="ml-1 text-[9px] font-bold uppercase tracking-wider rounded-full bg-indigo-500/40 px-2 py-0.5">Default</span>@endif
                            </p>
                            <p class="text-[11px] font-mono text-slate-300">{{ $node['hostname'] }} · OpenVPN TCP :{{ $node['ovpn_port'] }}{{ $node['region'] ? ' · '.$node['region'] : '' }}</p>
                        </div>
                    </div>
                    @if (! $node['is_active'])
                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-500/30 px-3 py-1 text-[11px] font-bold">Nonaktif</span>
                    @elseif ($node['online'])
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-400/15 ring-1 ring-emerald-300/30 px-3 py-1 text-[11px] font-bold text-emerald-200">
                            <span class="relative flex w-2 h-2"><span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span><span class="relative inline-flex w-2 h-2 rounded-full bg-emerald-400"></span></span>
                            Online
                        </span>
                    @else
                        <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/20 ring-1 ring-rose-300/30 px-3 py-1 text-[11px] font-bold text-rose-200">
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span> Offline
                        </span>
                    @endif
                </div>

                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Tersambung</p>
                            <p class="mt-1 text-2xl font-extrabold text-emerald-700 tabular-nums">{{ $node['connected'] ?? '—' }}</p>
                            <p class="text-[10px] text-emerald-700/80">router live via VPN</p>
                        </div>
                        <div class="rounded-2xl bg-indigo-50 border border-indigo-100 p-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-700">Akun VPN</p>
                            <p class="mt-1 text-2xl font-extrabold text-indigo-700 tabular-nums">{{ $node['accounts_active'] }}<span class="text-sm text-indigo-400">/{{ $node['accounts_total'] }}</span></p>
                            <p class="text-[10px] text-indigo-700/80">aktif / terdaftar</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @foreach ([['Kuota port remote', $node['ports'], 'port'], ['Kuota IP VPN', $node['ips'], 'IP']] as [$qLabel, $q, $unit])
                            <div>
                                <div class="flex items-baseline justify-between text-xs">
                                    <span class="font-bold text-slate-700">{{ $qLabel }}</span>
                                    <span class="font-extrabold text-slate-900 tabular-nums">{{ number_format($q['free'], 0, ',', '.') }} <span class="font-semibold text-slate-500">{{ $unit }} tersedia</span></span>
                                </div>
                                <div class="mt-1.5 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $barColor($q['percent']) }}" style="width: {{ max(2, $q['percent']) }}%"></div>
                                </div>
                                <p class="mt-1 text-[10px] text-slate-500 tabular-nums">{{ number_format($q['used'], 0, ',', '.') }} dari {{ number_format($q['total'], 0, ',', '.') }} terpakai ({{ $q['percent'] }}%)</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-6 text-sm text-slate-500">Belum ada node VPN.</div>
        @endforelse

        {{-- Router tenant --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center"><i class="fa-solid fa-network-wired"></i></span>
                    <div>
                        <p class="text-sm font-extrabold text-slate-900">Router MikroTik Tenant</p>
                        <p class="text-[11px] text-slate-500">Status dari sesi VPN yang sedang tersambung</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-[11px] font-bold">
                    <span class="rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1">{{ $routers['online'] }} online</span>
                    @if ($routers['offline'] > 0)
                        <span class="rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1">{{ $routers['offline'] }} offline</span>
                    @endif
                </div>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($routers['items'] as $r)
                    @php
                        $stateStyle = [
                            'online' => ['bg-emerald-500', 'text-emerald-700', 'Online'],
                            'offline' => ['bg-rose-500', 'text-rose-700', 'Offline'],
                            'unknown' => ['bg-slate-400', 'text-slate-500', 'Belum dicek'],
                            'disabled' => ['bg-slate-300', 'text-slate-400', 'Nonaktif'],
                        ][$r['state']];
                    @endphp
                    <div class="px-5 py-3 flex items-center gap-3">
                        <span class="w-2.5 h-2.5 shrink-0 rounded-full {{ $stateStyle[0] }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-900 truncate">{{ $r['tenant'] }} <span class="font-semibold text-slate-400">· {{ $r['name'] }}</span></p>
                            <p class="text-[11px] font-mono text-slate-500 truncate">{{ $r['endpoint'] }}{{ $r['via_tunnel'] ? ' · via VPN' : '' }}</p>
                        </div>
                        <span class="text-[11px] font-bold {{ $stateStyle[1] }}">{{ $stateStyle[2] }}</span>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">Belum ada router terdaftar.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- VPS & layanan --}}
    <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center"><i class="fa-solid fa-microchip"></i></span>
                <div class="min-w-0">
                    <p class="text-sm font-extrabold text-slate-900">Server Aplikasi</p>
                    <p class="text-[11px] font-mono text-slate-500 truncate">{{ $host['hostname'] }} · uptime {{ $uptimeDays }} hari {{ $uptimeHours }} jam</p>
                </div>
            </div>
            <div class="mt-5 space-y-4">
                @foreach ([
                    ['CPU', $host['cpu_percent'], 'load '.implode(' / ', $host['load']).' · '.$host['cores'].' core'],
                    ['RAM', $host['memory']['percent'], $bytes($host['memory']['used']).' / '.$bytes($host['memory']['total']).' · sisa '.$bytes($host['memory']['total'] - $host['memory']['used'])],
                    ['Disk', $host['disk']['percent'], $bytes($host['disk']['used']).' / '.$bytes($host['disk']['total']).' · sisa '.$bytes($host['disk']['total'] - $host['disk']['used'])],
                ] as [$rLabel, $pct, $detail])
                    <div>
                        <div class="flex items-baseline justify-between text-xs">
                            <span class="font-bold text-slate-700">{{ $rLabel }}</span>
                            <span class="font-extrabold text-slate-900 tabular-nums">{{ $pct }}%</span>
                        </div>
                        <div class="mt-1.5 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $barColor($pct) }}" style="width: {{ max(2, $pct) }}%"></div>
                        </div>
                        <p class="mt-1 text-[10px] text-slate-500 tabular-nums">{{ $detail }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <p class="text-sm font-extrabold text-slate-900">Layanan</p>
                <span class="text-[11px] font-bold {{ $servicesOk ? 'text-emerald-600' : 'text-amber-600' }}">
                    {{ collect($status['services'])->where('ok', true)->count() }}/{{ count($status['services']) }} normal
                </span>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($status['services'] as $svc)
                    <div class="px-5 py-3 flex items-center gap-3">
                        <span class="w-8 h-8 shrink-0 rounded-xl {{ $svc['ok'] ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center text-xs"><i class="fa-solid {{ $svc['icon'] }}"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-900">{{ $svc['name'] }}</p>
                            <p class="text-[10px] text-slate-500 truncate">{{ $svc['detail'] }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-[10px] font-bold {{ $svc['ok'] ? 'text-emerald-600' : 'text-rose-600' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $svc['ok'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                            {{ $svc['ok'] ? 'OK' : 'Masalah' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Pembayaran --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <p class="text-sm font-extrabold text-slate-900">Status Pembayaran</p>
        <p class="text-[11px] text-slate-500">Langganan & isi saldo tenant</p>
        <div class="mt-4 flex h-3 rounded-full overflow-hidden bg-slate-100">
            <div class="bg-emerald-500" style="width: {{ $paymentStats['approved'] / $payTotal * 100 }}%"></div>
            <div class="bg-amber-400" style="width: {{ $paymentStats['pending'] / $payTotal * 100 }}%"></div>
            <div class="bg-rose-400" style="width: {{ $paymentStats['rejected'] / $payTotal * 100 }}%"></div>
        </div>
        <div class="mt-4 space-y-2.5">
            @foreach ([['Lunas', $paymentStats['approved'], 'bg-emerald-500'], ['Menunggu dibayar', $paymentStats['pending'], 'bg-amber-400'], ['Batal / kedaluwarsa', $paymentStats['rejected'], 'bg-rose-400']] as [$pLabel, $pVal, $pColor])
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-2 text-slate-600"><span class="w-2.5 h-2.5 rounded-full {{ $pColor }}"></span>{{ $pLabel }}</span>
                    <span class="font-extrabold text-slate-900 tabular-nums">{{ $pVal }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <p class="text-sm font-extrabold text-slate-900">Transaksi Terbaru</p>
            <a href="{{ route('superadmin.payments') }}" class="text-[11px] font-bold text-indigo-600 hover:underline">Lihat semua →</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($recentPayments as $p)
                @php
                    $badge = match ($p->status) {
                        'approved' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Lunas'],
                        'pending' => ['bg-amber-50 text-amber-700 border-amber-200', 'Menunggu'],
                        default => ['bg-rose-50 text-rose-700 border-rose-200', 'Batal'],
                    };
                @endphp
                <div class="px-5 py-3 flex items-center gap-3">
                    <span class="w-9 h-9 shrink-0 rounded-xl {{ $p->isDeposit() ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600' }} flex items-center justify-center text-xs">
                        <i class="fa-solid {{ $p->isDeposit() ? 'fa-wallet' : 'fa-box-open' }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-900 truncate">{{ $p->tenant->name ?? '—' }}</p>
                        <p class="text-[10px] text-slate-500 truncate">
                            {{ $p->isDeposit() ? 'Isi saldo' : 'Langganan '.\App\Services\SubscriptionPackageService::planLabel((string) $p->package) }} · {{ $channelLabels[$p->payment_channel] ?? ($p->payment_channel ?: '—') }} · {{ format_wib($p->created_at) }}
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-xs font-extrabold text-slate-900 tabular-nums">{{ $idr($p->amount) }}</p>
                        <span class="inline-block mt-0.5 rounded-full border px-2 py-0.5 text-[9px] font-bold {{ $badge[0] }}">{{ $badge[1] }}</span>
                    </div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-slate-500">Belum ada transaksi.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
