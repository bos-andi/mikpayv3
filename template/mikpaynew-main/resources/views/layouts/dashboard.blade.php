<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e1b4b">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('mikpay/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/mikpay-ui.blue.min.css') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/pace.blue.css') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/dashboard-custom.css') }}">
    <style>
        /* Dropdown sidebar: tertutup default, terbuka dengan .menu-open atau saat diklik */
        #sidenav .dropdown-container,
        .sidenav .dropdown-container { display: none !important; }
        #sidenav .dropdown-container.menu-open,
        .sidenav .dropdown-container.menu-open { display: block !important; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
    <div class="wrapper">
        <div id="navbar" class="navbar">
            <div class="navbar-left">
                <button type="button" class="sidebar-toggle sidebar-toggle-mobile" title="Buka/Tutup Menu" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
                <span id="cpage" class="page-title">@yield('heading', 'Dashboard')</span>
            </div>
            <div class="navbar-right">
                <div class="navbar-timer" title="Waktu">
                    <i class="fa fa-clock-o"></i>
                    <span id="timer">{{ $clock['time'] ?? date('H:i:s') }}</span>
                </div>
                @if($full_dashboard_nav ?? true)
                <form id="router-switch-form" action="{{ route('sessions.connect') }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="session" id="router-session-input" value="{{ optional($currentRouter)->session_name }}">
                    <select class="navbar-select navbar-router-select" title="Router" name="session_select" onchange="document.getElementById('router-session-input').value=this.value; this.form.submit();">
                        @foreach($routers ?? [] as $r)
                        <option value="{{ $r->session_name }}" {{ (optional($currentRouter)->session_name ?? '') === $r->session_name ? 'selected' : '' }}>
                            {{ $r->router_name ?: $r->session_name }}
                        </option>
                    @endforeach
                    </select>
                </form>
                @endif
                <a href="{{ route('logout') }}" class="navbar-logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa fa-sign-out"></i> Logout</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
            </div>
        </div>

        <div id="sidenav" class="sidenav">
            <div class="sidebar-brand-bar">
                <div id="brand" class="brand-container">
                    <a href="{{ ($full_dashboard_nav ?? true) ? route('dashboard') : route('langganan') }}" class="brand-link">
                        <span class="brand-text">MIKPAY</span>
                    </a>
                    <button type="button" class="sidebar-toggle sidebar-toggle-desktop" title="Buka/Tutup Menu" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
                </div>
            </div>
            <div class="sidebar-scroll">
            @if($full_dashboard_nav ?? true)
            <div class="menu text-center align-middle card-header"><h3>{{ $identity ?? ($currentRouter->router_name ?? $currentRouter->session_name ?? 'Router') }}</h3></div>
            <a href="{{ route('dashboard') }}" class="menu {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fa fa-dashboard"></i> Dashboard</a>
            @php
                $hotspotOpen = request()->routeIs('hotspot.*');
                $hotspotUsersOpen = request()->routeIs('hotspot.users') || request()->routeIs('hotspot.add-user') || request()->routeIs('hotspot.generate-user');
                $hotspotProfilesOpen = request()->routeIs('hotspot.user-profiles');
            @endphp
            <div class="dropdown-btn {{ $hotspotOpen ? 'active' : '' }}"><i class="fa fa-wifi"></i> Hotspot <i class="fa fa-caret-down"></i></div>
            <div class="dropdown-container {{ $hotspotOpen ? 'menu-open' : '' }}">
                <div class="dropdown-btn {{ $hotspotUsersOpen ? 'active' : '' }}"><i class="fa fa-users"></i> Users <i class="fa fa-caret-down"></i></div>
                <div class="dropdown-container {{ $hotspotUsersOpen ? 'menu-open' : '' }}">
                    <a href="{{ route('hotspot.users') }}" class="menu {{ request()->routeIs('hotspot.users') ? 'active' : '' }}"><i class="fa fa-list"></i> User List</a>
                    <a href="{{ route('hotspot.add-user') }}" class="menu {{ request()->routeIs('hotspot.add-user') ? 'active' : '' }}"><i class="fa fa-user-plus"></i> Add User</a>
                    <a href="{{ route('hotspot.generate-user') }}" class="menu {{ request()->routeIs('hotspot.generate-user') ? 'active' : '' }}"><i class="fa fa-magic"></i> Generate</a>
                </div>

                <div class="dropdown-btn {{ $hotspotProfilesOpen ? 'active' : '' }}"><i class="fa fa-address-book"></i> User Profile <i class="fa fa-caret-down"></i></div>
                <div class="dropdown-container {{ $hotspotProfilesOpen ? 'menu-open' : '' }}">
                    <a href="{{ route('hotspot.user-profiles') }}" class="menu {{ request()->routeIs('hotspot.user-profiles') ? 'active' : '' }}"><i class="fa fa-list"></i> Profile List</a>
                    <a href="{{ route('hotspot.user-profiles.add') }}" class="menu {{ request()->routeIs('hotspot.user-profiles.add') ? 'active' : '' }}"><i class="fa fa-plus"></i> Add Profile</a>
                </div>

                <a href="{{ route('hotspot.active') }}" class="menu {{ request()->routeIs('hotspot.active') ? 'active' : '' }}"><i class="fa fa-wifi"></i> Hotspot Active</a>
                <a href="{{ route('hotspot.hosts') }}" class="menu {{ request()->routeIs('hotspot.hosts') ? 'active' : '' }}"><i class="fa fa-laptop"></i> Hosts</a>
                <a href="{{ route('hotspot.ipbinding') }}" class="menu {{ request()->routeIs('hotspot.ipbinding') ? 'active' : '' }}"><i class="fa fa-address-book"></i> IP Bindings</a>
                <a href="{{ route('hotspot.cookies') }}" class="menu {{ request()->routeIs('hotspot.cookies') ? 'active' : '' }}"><i class="fa fa-hourglass"></i> Cookies</a>
            </div>
            <div class="dropdown-btn {{ request()->routeIs('ppp.*') && !request()->routeIs('ppp.billing') ? 'active' : '' }}"><i class="fa fa-plug"></i> PPP <i class="fa fa-caret-down"></i></div>
            <div class="dropdown-container {{ request()->routeIs('ppp.*') && !request()->routeIs('ppp.billing') ? 'menu-open' : '' }}">
                <a href="{{ route('ppp.secrets') }}" class="menu {{ request()->routeIs('ppp.secrets') ? 'active' : '' }}"><i class="fa fa-users"></i> PPP Secrets</a>
                <a href="{{ route('ppp.profiles') }}" class="menu {{ request()->routeIs('ppp.profiles') ? 'active' : '' }}"><i class="fa fa-pie-chart"></i> PPP Profiles</a>
                <a href="{{ route('ppp.active') }}" class="menu {{ request()->routeIs('ppp.active') ? 'active' : '' }}"><i class="fa fa-wifi"></i> PPP Active</a>
            </div>
            @php
                $keuanganOpen = request()->routeIs('ppp.billing') || request()->routeIs('report.selling') || request()->routeIs('keuangan.*');
            @endphp
            <div class="dropdown-btn {{ $keuanganOpen ? 'active' : '' }}"><i class="fa fa-money"></i> Keuangan <i class="fa fa-caret-down"></i></div>
            <div class="dropdown-container {{ $keuanganOpen ? 'menu-open' : '' }}">
                <a href="{{ route('ppp.billing') }}" class="menu {{ request()->routeIs('ppp.billing') ? 'active' : '' }}"><i class="fa fa-file-text-o"></i> Tagihan PPP</a>
                <a href="{{ route('report.selling') }}" class="menu {{ request()->routeIs('report.selling') ? 'active' : '' }}"><i class="fa fa-bar-chart"></i> Report Penjualan</a>
                <a href="{{ route('keuangan.cashflow') }}" class="menu {{ request()->routeIs('keuangan.cashflow') ? 'active' : '' }}"><i class="fa fa-exchange"></i> Kas Masuk/Keluar</a>
                <a href="{{ route('keuangan.report') }}" class="menu {{ request()->routeIs('keuangan.report') ? 'active' : '' }}"><i class="fa fa-line-chart"></i> Laporan Keuangan</a>
            </div>
            <a href="#" class="menu"><i class="fa fa-ticket"></i> Vouchers</a>
            <div class="dropdown-btn"><i class="fa fa-align-justify"></i> Log <i class="fa fa-caret-down"></i></div>
            <div class="dropdown-container">
                <a href="#" class="menu"><i class="fa fa-wifi"></i> Hotspot Log</a>
                <a href="#" class="menu"><i class="fa fa-users"></i> User Log</a>
            </div>
            <div class="dropdown-btn"><i class="fa fa-gear"></i> System <i class="fa fa-caret-down"></i></div>
            <div class="dropdown-container">
                <a href="#" class="menu"><i class="fa fa-clock-o"></i> Scheduler</a>
            </div>
            <a href="#" class="menu"><i class="fa fa-sitemap"></i> DHCP Leases</a>
            <div class="dropdown-btn"><i class="fa fa-cog"></i> Settings <i class="fa fa-caret-down"></i></div>
            <div class="dropdown-container">
                <a href="{{ route('sessions') }}" class="menu"><i class="fa fa-gear"></i> Admin Settings</a>
            </div>
            <a href="{{ route('langganan') }}" class="menu {{ request()->routeIs('langganan*') ? 'active' : '' }}" style="position: relative; margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 12px;">
                <i class="fa fa-bookmark"></i> Langganan
                <span style="float:right; padding:3px 10px; border-radius:12px; background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.85); font-size: 11px; font-weight: 700; margin-top:2px;">
                    Exp: {{ $subscription_until ?? '—' }}
                </span>
            </a>
            @else
            <div class="menu text-center align-middle card-header"><h3>Langganan</h3></div>
            <p class="text-center" style="color:rgba(255,255,255,0.65);font-size:12px;line-height:1.45;padding:4px 18px 14px;margin:0;">Langganan tidak aktif atau sudah berakhir. Perpanjang di bawah untuk membuka kembali semua menu.</p>
            <a href="{{ route('langganan') }}" class="menu {{ request()->routeIs('langganan*') ? 'active' : '' }}">
                <i class="fa fa-bookmark"></i> Langganan
                <span style="float:right; padding:3px 10px; border-radius:12px; background: rgba(249,115,22,0.35); color: #ffedd5; font-size: 11px; font-weight: 700; margin-top:2px;">
                    Exp: {{ $subscription_until ?? '—' }}
                </span>
            </a>
            @endif
            </div>
        </div>

        <div id="main" class="main">
            <div class="main-container @yield('main_container_class')">
                @if(session('success'))<div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> {{ session('error') }}</div>@endif
                @yield('content')
            </div>
        </div>
    </div>
    <script src="{{ asset('mikpay/jquery.min.js') }}"></script>
    <script src="{{ asset('mikpay/pace.min.js') }}"></script>
    <script>
        (function() {
            function toggleSidebar() { document.body.classList.toggle('sidebar-closed'); }
            function closeSidebar() { document.body.classList.add('sidebar-closed'); }
            
            document.querySelectorAll('.sidebar-toggle').forEach(function(btn) { btn.addEventListener('click', toggleSidebar); });
            document.getElementById('sidebarOverlay')?.addEventListener('click', closeSidebar);
            
            if (window.matchMedia('(max-width: 991px)').matches) {
                document.body.classList.add('sidebar-closed');
            }
            window.matchMedia('(max-width: 991px)').addEventListener('change', function(e) {
                if (e.matches) closeSidebar(); else document.body.classList.remove('sidebar-closed');
            });
            
            var dropdown = document.getElementsByClassName('dropdown-btn');
            for (var i = 0; i < dropdown.length; i++) {
                dropdown[i].addEventListener('click', function(e) {
                    if (this.nextElementSibling && this.nextElementSibling.classList.contains('dropdown-container')) {
                        e.stopPropagation();
                        this.classList.toggle('active');
                        this.nextElementSibling.classList.toggle('menu-open');
                    }
                });
            }
            document.getElementById('sidenav')?.addEventListener('click', function(e) {
                if (window.innerWidth <= 991 && e.target.closest('a.menu')) closeSidebar();
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
