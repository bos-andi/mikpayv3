@php
    $brand = config('mikpay.brand');
    $page = trim($__env->yieldContent('page') ?: ($activePage ?? 'dashboard'));
    $tenant = $tenant ?? null;
    $tunnelOnline = $tenant?->vpn_status === 'online' || ($tenant === null);

    $apiOnline = false;
    if ($tenant?->id) {
        $apiOnline = \App\Models\Router::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'enable')
            ->where('last_check_ok', true)
            ->exists();
    }

    $nav = [
        ['key' => 'dashboard', 'route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie', 'iconColor' => ''],
        ['key' => 'gis', 'route' => 'gis', 'label' => 'Peta GIS & ODP', 'icon' => 'fa-map-location-dot', 'iconColor' => 'text-cyan-600 dark:text-cyan-400', 'badge' => 'MAP', 'badgeClass' => 'bg-cyan-500/10 dark:bg-cyan-500/20 text-cyan-700 dark:text-cyan-300'],
        ['key' => 'remote', 'route' => 'remote', 'label' => 'Remote & OpenVPN', 'icon' => 'fa-server', 'iconColor' => 'text-amber-500 dark:text-amber-400', 'badge' => 'SETUP', 'badgeClass' => 'bg-violet-500/10 dark:bg-violet-500/20 text-violet-700 dark:text-violet-300'],
        ['key' => 'customers', 'route' => 'customers', 'label' => 'Data Pelanggan', 'icon' => 'fa-users', 'iconColor' => 'text-blue-500 dark:text-blue-400'],
        ['key' => 'billing', 'route' => 'billing', 'label' => 'Billing & Tagihan', 'icon' => 'fa-file-invoice-dollar', 'iconColor' => 'text-emerald-600 dark:text-emerald-400'],
        ['key' => 'vouchers', 'route' => 'vouchers', 'label' => 'Hotspot & Voucher', 'icon' => 'fa-ticket', 'iconColor' => 'text-purple-600 dark:text-purple-400'],
        ['key' => 'finance', 'route' => 'finance', 'label' => 'Laporan Keuangan', 'icon' => 'fa-wallet', 'iconColor' => 'text-emerald-600 dark:text-emerald-400'],
        ['key' => 'tickets', 'route' => 'tickets', 'label' => 'Tiket Gangguan', 'icon' => 'fa-headset', 'iconColor' => 'text-orange-500 dark:text-orange-400', 'badge' => '2 Baru', 'badgeClass' => 'bg-orange-500/10 dark:bg-orange-500/20 text-orange-700 dark:text-orange-300'],
        [
            'key' => 'settings',
            'route' => 'settings',
            'label' => 'Pengaturan Sistem',
            'icon' => 'fa-gear',
            'iconColor' => 'text-slate-500 dark:text-slate-400',
            'badge' => $apiOnline ? 'API OK' : 'API',
            'badgeClass' => $apiOnline
                ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300'
                : 'bg-amber-500/10 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300',
        ],
    ];
@endphp

<aside class="w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between shrink-0 z-20 transition-all duration-300 shadow-sm">
    <div>
        <div class="h-16 flex items-center px-6 gap-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/50">
            @php
                $logoUrl = asset($brand['logo'] ?? 'logo-mikpay.png');
                $domain = (string) ($brand['domain'] ?? 'mikpay.link');
                $tld = str_contains($domain, '.') ? substr($domain, strpos($domain, '.')) : '.link';
            @endphp
            <img src="{{ $logoUrl }}?v=2" alt="{{ $brand['name'] }}" class="w-9 h-9 object-contain shrink-0">
            <div>
                <h1 class="font-extrabold text-lg tracking-wider text-slate-900 dark:text-white leading-tight">
                    {{ $brand['name'] }}<span class="text-violet-600 dark:text-violet-400">{{ $tld }}</span>
                </h1>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 tracking-wider uppercase font-semibold">
                    {{ $tenant?->name ?? 'ISP & OLT Manager' }}
                </p>
            </div>
        </div>

        <div class="px-4 py-3">
            <div class="p-2.5 rounded-lg bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    @if ($tunnelOnline)
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        </span>
                    @else
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                    @endif
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">OpenVPN Tunnel</span>
                </div>
                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded border font-bold {{ $tunnelOnline ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 border-emerald-500/20' : 'text-slate-500 bg-slate-200/60 border-slate-300 dark:bg-slate-800 dark:border-slate-700' }}">
                    {{ $tunnelOnline ? 'ONLINE' : 'OFFLINE' }}
                </span>
            </div>
            @if ($tenant?->subdomain)
                <p class="mt-2 text-[10px] font-mono text-slate-500 dark:text-slate-400 truncate px-1">
                    {{ $tenant->subdomain }}.{{ config('mikpay.tenant_base_domain') }}
                </p>
            @endif
            @php
                $vpnUser = $tenant?->id
                    ? \App\Models\TunnelAccount::query()->where('tenant_id', $tenant->id)->value('username')
                    : null;
            @endphp
            @if ($vpnUser)
                <p class="mt-1 text-[10px] font-mono text-violet-600/80 dark:text-violet-400/80 truncate px-1" title="Username VPN (permanen)">
                    {{ $vpnUser }}
                </p>
            @endif
        </div>

        <nav class="px-3 py-2 space-y-1 text-sm font-medium">
            @foreach ($nav as $item)
                @php $active = $page === $item['key']; @endphp
                <a
                    href="{{ panel_route($item['route']) }}"
                    class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ $active ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                >
                    <i class="fa-solid {{ $item['icon'] }} w-5 text-center {{ $item['iconColor'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                    @if (! empty($item['badge']))
                        <span class="ml-auto {{ $item['badgeClass'] }} text-[10px] px-1.5 py-0.5 rounded-full font-bold">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 space-y-2">
        @auth
            @if (auth()->user()->isSuperAdmin())
                <a href="{{ route('superadmin.dashboard') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition">
                    <i class="fa-solid fa-shield-halved"></i> Superadmin Panel
                </a>
            @endif
            <div class="flex items-center gap-3 p-2 rounded-lg bg-slate-100 dark:bg-slate-800/40">
                <div class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold ring-2 ring-indigo-500">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="overflow-hidden flex-1 min-w-0">
                    <h4 class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ auth()->user()->name }}</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-rose-500 text-xs p-1" title="Keluar">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 p-2.5 rounded-lg bg-indigo-600 text-white text-xs font-bold">
                Masuk
            </a>
        @endauth
    </div>
</aside>
