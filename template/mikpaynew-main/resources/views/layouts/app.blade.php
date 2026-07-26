<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#008BC9">
    <title>@yield('title', 'MIKPAY') - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('mikpay/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/mikpay-ui.blue.min.css') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/pace.blue.css') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/dashboard-custom.css') }}">
    @stack('styles')
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
    <div class="wrapper">
        <div id="navbar" class="navbar">
            <div class="navbar-left">
                <button type="button" class="sidebar-toggle sidebar-toggle-mobile" title="Toggle Sidebar" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
                <span id="cpage" class="page-title">@yield('heading', 'Dashboard')</span>
            </div>
            <div class="navbar-right">
                <div class="navbar-user-chip">
                    <span class="navbar-user-dot"></span>
                    {{ auth()->user()?->username ?? 'user' }}
                </div>
                <a id="logout" href="{{ route('logout') }}" class="navbar-logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa fa-sign-out"></i> Logout</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
            </div>
        </div>

        <div id="sidenav" class="sidenav">
            <div class="sidebar-brand-bar">
                <div id="brand" class="brand-container">
                    <a href="{{ route('sessions') }}" class="brand-link">
                        <span class="brand-text">MIKPAY</span>
                    </a>
                    <button type="button" class="sidebar-toggle sidebar-toggle-desktop" title="Toggle Sidebar" aria-label="Toggle sidebar"><i class="fa fa-bars"></i></button>
                </div>
            </div>
            <div class="sidebar-scroll">
            <div class="card-header sidebar-user-card">
                <div class="sidebar-user-avatar">{{ strtoupper(substr(auth()->user()?->username ?? 'U', 0, 1)) }}</div>
                <div class="sidebar-user-meta">
                    <h3>{{ auth()->user()?->username ?? 'Pengguna' }}</h3>
                    <small>{{ session('selected_router') ?: 'Belum pilih router' }}</small>
                </div>
            </div>

            @php
                // Dua varian sidebar: (1) area setup/konfigurasi lengkap, (2) menu ringkas dashboard operasional.
                $isAdminSettingsArea = request()->routeIs('sessions')
                    || request()->routeIs('sessions.fonnte')
                    || request()->routeIs('routers.*')
                    || request()->routeIs('admin.*');
            @endphp

            {{-- Sidebar seperti template untuk Sessions + Add/Edit Router --}}
            @if($isAdminSettingsArea)
                <div class="sidebar-group-title">Utama</div>
                <a href="{{ route('dashboard') }}" class="menu {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fa fa-dashboard"></i> Dashboard</a>
                <a href="#" class="menu {{ request()->routeIs('sessions.settings') ? 'active' : '' }}"><i class="fa fa-cogs"></i> Session Settings</a>

                <div class="sidebar-group-title">Branding</div>
                <a href="{{ route('admin.upload-logo') }}" class="menu {{ request()->routeIs('admin.upload-logo*') ? 'active' : '' }}"><i class="fa fa-upload"></i> Upload Logo</a>
                <a href="{{ route('admin.template-editor') }}" class="menu {{ request()->routeIs('admin.template-editor*') ? 'active' : '' }}"><i class="fa fa-edit"></i> Template Editor</a>

                <div class="sidebar-group-title">Konfigurasi</div>
                <a href="{{ route('sessions') }}" class="menu {{ request()->routeIs('sessions') && ! request()->routeIs('sessions.fonnte') ? 'active' : '' }}"><i class="fa fa-gear"></i> Admin Settings</a>
                <a href="{{ route('sessions.fonnte') }}" class="menu {{ request()->routeIs('sessions.fonnte') ? 'active' : '' }}"><i class="fa fa-whatsapp"></i> WhatsApp (Fonnte)</a>
                <a href="{{ route('routers.create') }}" class="menu {{ request()->routeIs('routers.create') || request()->routeIs('routers.edit') ? 'active' : '' }}"><i class="fa fa-plus"></i> Add Router</a>

                <div class="sidebar-footer-cta">
                    <a href="{{ route('langganan') }}" class="menu {{ request()->routeIs('langganan*') ? 'active' : '' }}">
                        <i class="fa fa-bookmark"></i> Langganan
                        <span class="sidebar-exp-chip">Exp: {{ $subscription_until ?? '—' }}</span>
                    </a>
                </div>
            @else
                <div class="sidebar-group-title">Menu</div>
                <a href="{{ route('sessions') }}" class="menu {{ request()->routeIs('sessions') ? 'active' : '' }}"><i class="fa fa-gear"></i> Admin Settings</a>
                <a href="{{ route('routers.create') }}" class="menu {{ request()->routeIs('routers.create') || request()->routeIs('routers.edit') ? 'active' : '' }}"><i class="fa fa-plus"></i> Tambah Router</a>
                <a href="{{ route('dashboard') }}" class="menu {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fa fa-tachometer"></i> Dashboard</a>
                <a href="#" class="menu"><i class="fa fa-wifi"></i> Hotspot</a>
                <a href="#" class="menu"><i class="fa fa-plug"></i> PPP</a>
                <a href="#" class="menu"><i class="fa fa-cog"></i> Settings</a>
                @if(auth()->user()?->isSuperAdmin())
                <a href="{{ route('superadmin.dashboard') }}" class="menu {{ request()->routeIs('superadmin.*') ? 'active' : '' }}"><i class="fa fa-shield"></i> Super Admin</a>
                @endif
            @endif
            </div>
        </div>

        <div id="main" class="main">
            <div class="main-container">
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
            if (window.matchMedia('(max-width: 991px)').matches) document.body.classList.add('sidebar-closed');
            window.matchMedia('(max-width: 991px)').addEventListener('change', function(e) {
                if (e.matches) closeSidebar(); else document.body.classList.remove('sidebar-closed');
            });
            document.getElementById('sidenav')?.addEventListener('click', function(e) {
                if (window.innerWidth <= 991 && e.target.closest('a.menu')) closeSidebar();
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
