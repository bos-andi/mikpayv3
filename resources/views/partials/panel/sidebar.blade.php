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

    $subscriptionLocked = $tenant
        && ($tenant->plan ?? '') !== 'tunnel'
        && $tenant->needsRenewal();

    $financeSections = [
        'overview' => ['label' => 'Ringkasan', 'icon' => 'fa-chart-pie'],
        'pl' => ['label' => 'Laba Rugi', 'icon' => 'fa-scale-balanced'],
        'neraca' => ['label' => 'Neraca', 'icon' => 'fa-landmark'],
        'kas' => ['label' => 'Buku Kas', 'icon' => 'fa-money-bill-transfer'],
    ];
    $financeSection = (string) request('section', request('tab', ''));
    if (! isset($financeSections[$financeSection])) {
        $financeSection = 'overview';
    }
    $financePeriod = $page === 'finance'
        ? array_filter(['range' => request('range'), 'year' => request('year'), 'month' => request('month')])
        : [];
    $financeChildren = [];
    foreach ($financeSections as $key => $meta) {
        $financeChildren[] = [
            'url' => panel_route('finance').'?'.http_build_query(array_merge(['section' => $key], $financePeriod)),
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'active' => $page === 'finance' && $financeSection === $key,
        ];
    }
    $financeChildren[] = [
        'url' => panel_route('finance.withdrawals'),
        'label' => 'Penarikan Dana',
        'icon' => 'fa-building-columns',
        'active' => $page === 'finance-withdrawals',
    ];

    $voucherSections = [
        'users' => ['label' => 'Voucher / User', 'icon' => 'fa-users'],
        'generate' => ['label' => 'Generate Voucher', 'icon' => 'fa-bolt'],
        'profiles' => ['label' => 'Profile', 'icon' => 'fa-layer-group'],
        'active' => ['label' => 'User Aktif', 'icon' => 'fa-signal'],
        'report' => ['label' => 'Laporan Penjualan', 'icon' => 'fa-chart-column'],
        'template' => ['label' => 'Template Voucher', 'icon' => 'fa-pen-to-square'],
    ];
    $voucherSection = (string) request('section', 'users');
    if (! isset($voucherSections[$voucherSection])) {
        $voucherSection = 'users';
    }
    $voucherChildren = [];
    foreach ($voucherSections as $key => $meta) {
        $voucherChildren[] = [
            'url' => panel_route('vouchers').'?section='.$key,
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'active' => $page === 'vouchers' && $voucherSection === $key,
        ];
    }
    $voucherChildren[] = [
        'url' => panel_route('vouchers.shop'),
        'label' => 'Toko Voucher Online',
        'icon' => 'fa-store',
        'active' => $page === 'voucher-shop',
    ];

    $settingsTabs = [
        'mikrotik' => ['label' => 'Router MikroTik', 'icon' => 'fa-network-wired'],
        'payment' => ['label' => 'Payment Gateway', 'icon' => 'fa-credit-card'],
        'billing' => ['label' => 'Billing & Isolir', 'icon' => 'fa-calendar-days'],
        'whatsapp' => ['label' => 'WhatsApp Gateway', 'icon' => 'fa-whatsapp', 'brand' => true],
    ];
    $settingsTab = (string) request('tab', 'mikrotik');
    $settingsTab = $settingsTab === 'xendit' ? 'payment' : $settingsTab;
    if (! isset($settingsTabs[$settingsTab])) {
        $settingsTab = 'mikrotik';
    }
    $authUser = auth()->user();
    $can = fn (string $module) => (bool) $authUser?->canAccessModule($module);
    $settingsChildren = [];
    foreach ($settingsTabs as $key => $meta) {
        if (! $can(\App\Support\TenantAccess::SETTINGS_TABS[$key])) {
            continue;
        }
        $settingsChildren[] = [
            'url' => panel_route('settings').'?tab='.$key,
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'brand' => $meta['brand'] ?? false,
            'active' => $page === 'settings' && $settingsTab === $key,
        ];
    }
    if ($can(\App\Support\TenantAccess::ADMIN)) {
        $settingsChildren[] = [
            'url' => panel_route('settings.users'),
            'label' => 'Manajemen User',
            'icon' => 'fa-user-shield',
            'active' => $page === 'settings-users',
        ];
    }

    $navAll = [
        ['key' => 'dashboard', 'route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie', 'iconColor' => ''],
    ];

    if (($onboarding ?? null) && ($onboarding['eligible'] ?? false) && ! ($onboarding['dismissed'] ?? true)) {
        $navAll[] = [
            'key' => 'onboarding',
            'route' => 'onboarding',
            'label' => 'Mulai Setup',
            'icon' => 'fa-rocket',
            'iconColor' => 'text-indigo-600 dark:text-indigo-400',
            'badge' => ! ($onboarding['all_required_done'] ?? true)
                ? ($onboarding['progress'] ?? 0).'%'
                : null,
            'badgeClass' => 'bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300',
        ];
    }

    $navAll = array_merge($navAll, [
        ['key' => 'gis', 'route' => 'gis', 'label' => 'Peta GIS & ODP', 'icon' => 'fa-map-location-dot', 'iconColor' => 'text-cyan-600 dark:text-cyan-400', 'badge' => 'MAP', 'badgeClass' => 'bg-cyan-500/10 dark:bg-cyan-500/20 text-cyan-700 dark:text-cyan-300'],
        ['key' => 'remote', 'route' => 'remote', 'label' => 'Remote & VPN', 'icon' => 'fa-server', 'iconColor' => 'text-amber-500 dark:text-amber-400', 'badge' => 'SETUP', 'badgeClass' => 'bg-violet-500/10 dark:bg-violet-500/20 text-violet-700 dark:text-violet-300'],
        ['key' => 'customers', 'route' => 'customers', 'label' => 'Data Pelanggan', 'icon' => 'fa-users', 'iconColor' => 'text-blue-500 dark:text-blue-400'],
        ['key' => 'billing', 'route' => 'billing', 'label' => 'Billing & Tagihan', 'icon' => 'fa-file-invoice-dollar', 'iconColor' => 'text-emerald-600 dark:text-emerald-400'],
        ['key' => 'vouchers', 'route' => 'vouchers', 'label' => 'Hotspot & Voucher', 'icon' => 'fa-ticket', 'iconColor' => 'text-purple-600 dark:text-purple-400', 'activeKeys' => ['vouchers', 'voucher-shop'], 'children' => $voucherChildren],
        ['key' => 'finance', 'route' => 'finance', 'label' => 'Laporan Keuangan', 'icon' => 'fa-wallet', 'iconColor' => 'text-emerald-600 dark:text-emerald-400', 'activeKeys' => ['finance', 'finance-withdrawals'], 'children' => $financeChildren],
        ['key' => 'tickets', 'route' => 'tickets', 'label' => 'Tiket Gangguan', 'icon' => 'fa-headset', 'iconColor' => 'text-orange-500 dark:text-orange-400', 'badge' => ($openTicketCount ?? 0) > 0 ? $openTicketCount.' Baru' : null, 'badgeClass' => 'bg-orange-500/10 dark:bg-orange-500/20 text-orange-700 dark:text-orange-300'],
        ['key' => 'subscription', 'route' => 'subscription', 'label' => 'Langganan MikPay', 'icon' => 'fa-crown', 'iconColor' => 'text-violet-600 dark:text-violet-400'],
        [
            'key' => 'settings',
            'route' => 'settings',
            'label' => 'Pengaturan',
            'icon' => 'fa-gear',
            'iconColor' => 'text-slate-500 dark:text-slate-400',
            'badge' => $apiOnline ? 'API OK' : 'API',
            'badgeClass' => $apiOnline
                ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300'
                : 'bg-amber-500/10 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300',
            'activeKeys' => ['settings', 'settings-users'],
            'children' => $settingsChildren,
        ],
    ]);

    $navModule = ['onboarding' => \App\Support\TenantAccess::ADMIN, 'subscription' => \App\Support\TenantAccess::ADMIN];
    $navAll = array_values(array_filter($navAll, function (array $item) use ($can, $navModule) {
        $key = $item['key'];
        if ($key === 'dashboard') {
            return true;
        }
        if ($key === 'settings') {
            return $item['children'] !== [];
        }

        return $can($navModule[$key] ?? $key);
    }));

    // Saat nonaktif: tampilkan semua menu di sidebar, hanya Langganan yang bisa diklik.
    $nav = $navAll;
@endphp

<div id="panelSidebarBackdrop" class="fixed inset-0 bg-slate-900/50 backdrop-blur-[1px] z-40 hidden lg:hidden" aria-hidden="true"></div>

<aside id="panelSidebar" class="fixed inset-y-0 left-0 w-64 max-w-[85vw] bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between z-50 shadow-xl transition-transform duration-300 ease-out -translate-x-full lg:translate-x-0 lg:static lg:shrink-0 lg:shadow-sm lg:max-w-none">
    <div class="min-h-0 flex-1 flex flex-col overflow-y-auto">
        <div class="h-16 flex items-center px-4 sm:px-6 gap-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/50 shrink-0">
            @php
                $logoUrl = asset($brand['logo'] ?? 'logo-mikpay.png');
                $domain = (string) ($brand['domain'] ?? 'mikpay.link');
                $tld = str_contains($domain, '.') ? substr($domain, strpos($domain, '.')) : '.link';
            @endphp
            <img src="{{ $logoUrl }}?v=2" alt="{{ $brand['name'] }}" class="w-9 h-9 object-contain shrink-0">
            <div class="min-w-0 flex-1">
                <h1 class="font-extrabold text-lg tracking-wider text-slate-900 dark:text-white leading-tight truncate">
                    {{ $brand['name'] }}<span class="text-violet-600 dark:text-violet-400">{{ $tld }}</span>
                </h1>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 tracking-wider uppercase font-semibold truncate">
                    {{ $tenant?->name ?? 'ISP & OLT Manager' }}
                </p>
            </div>
            <button type="button" id="panelSidebarClose" class="lg:hidden w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center shrink-0" aria-label="Tutup menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
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
                @php
                    $active = in_array($page, $item['activeKeys'] ?? [$item['key']], true);
                    $locked = $subscriptionLocked && ($item['key'] ?? '') !== 'subscription';
                    $children = $item['children'] ?? [];
                @endphp
                @if ($locked)
                    <span
                        title="Perpanjang langganan dulu untuk membuka menu ini"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 dark:text-slate-600 opacity-60 cursor-not-allowed select-none"
                    >
                        <i class="fa-solid {{ $item['icon'] }} w-5 text-center {{ $item['iconColor'] }} opacity-50"></i>
                        <span class="truncate">{{ $item['label'] }}</span>
                        @if (! empty($item['badge']))
                            <span class="ml-auto shrink-0 whitespace-nowrap bg-slate-100 dark:bg-slate-800 text-slate-400 text-[9px] px-1.5 py-0.5 rounded-full font-bold">{{ $item['badge'] }}</span>
                        @endif
                    </span>
                @elseif ($children)
                    <button
                        type="button"
                        data-nav-group-toggle
                        aria-expanded="{{ $active ? 'true' : 'false' }}"
                        aria-controls="navGroup-{{ $item['key'] }}"
                        class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition text-left {{ $active ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                    >
                        <i class="fa-solid {{ $item['icon'] }} w-5 text-center {{ $item['iconColor'] }}"></i>
                        <span class="truncate">{{ $item['label'] }}</span>
                        @if (! empty($item['badge']))
                            <span class="ml-auto shrink-0 whitespace-nowrap {{ $item['badgeClass'] }} text-[9px] px-1.5 py-0.5 rounded-full font-bold">{{ $item['badge'] }}</span>
                        @endif
                        <i data-nav-chevron class="fa-solid fa-chevron-down text-[10px] text-slate-400 shrink-0 transition-transform duration-200 {{ empty($item['badge']) ? 'ml-auto' : '' }} {{ $active ? 'rotate-180' : '' }}"></i>
                    </button>
                    <div id="navGroup-{{ $item['key'] }}" class="ml-5 pl-3 border-l border-slate-200 dark:border-slate-700 space-y-0.5 {{ $active ? '' : 'hidden' }}">
                        @foreach ($children as $child)
                            <a
                                href="{{ $child['url'] }}"
                                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] transition {{ $child['active'] ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 font-semibold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                            >
                                <i class="{{ ($child['brand'] ?? false) ? 'fa-brands' : 'fa-solid' }} {{ $child['icon'] }} w-4 text-center text-xs"></i>
                                <span class="truncate">{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <a
                        href="{{ panel_route($item['route']) }}"
                        class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ $active ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                    >
                        <i class="fa-solid {{ $item['icon'] }} w-5 text-center {{ $item['iconColor'] }}"></i>
                        <span class="truncate">{{ $item['label'] }}</span>
                        @if (! empty($item['badge']))
                            <span class="ml-auto shrink-0 whitespace-nowrap {{ $item['badgeClass'] }} text-[9px] px-1.5 py-0.5 rounded-full font-bold">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>
    </div>

    <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 space-y-2">
        @auth
            @if (auth()->user()->isSuperAdmin())
                <a href="{{ central_url('/superadmin') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition">
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

<script>
document.querySelectorAll('[data-nav-group-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var group = document.getElementById(btn.getAttribute('aria-controls'));
        var open = btn.getAttribute('aria-expanded') !== 'true';
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (group) group.classList.toggle('hidden', !open);
        var chevron = btn.querySelector('[data-nav-chevron]');
        if (chevron) chevron.classList.toggle('rotate-180', open);
    });
});
</script>
