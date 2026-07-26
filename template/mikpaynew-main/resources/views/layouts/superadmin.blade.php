<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('mikpay/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/mikpay-ui.blue.min.css') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/dashboard-custom.css') }}">
    <style>
        body {
            margin: 0;
            background: #eef2f7;
        }
        .sa-navbar {
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sa-brand {
            font-weight: 800;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sa-badge {
            font-size: 10px;
            background: rgba(249, 115, 22, 0.2);
            border: 1px solid rgba(249, 115, 22, 0.45);
            color: #fed7aa;
            padding: 3px 7px;
            border-radius: 999px;
        }
        .sa-shell {
            width: 100%;
            max-width: none;
            margin: 16px 0;
            padding: 0 20px;
            box-sizing: border-box;
            display: flex;
            gap: 18px;
            align-items: flex-start;
        }
        .sa-sidebar {
            flex: 0 0 220px;
            position: sticky;
            top: 16px;
        }
        .sa-sidebar-nav {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .sa-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            border-radius: 10px;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .sa-nav-item i {
            width: 18px;
            text-align: center;
            color: #64748b;
        }
        .sa-nav-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .sa-nav-item:hover i {
            color: #4D44B5;
        }
        .sa-nav-item.active {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            color: #312e81;
        }
        .sa-nav-item.active i {
            color: #4D44B5;
        }
        .sa-nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-weight: 800;
            background: #f97316;
            color: #fff;
            padding: 2px 7px;
            border-radius: 999px;
            min-width: 20px;
            text-align: center;
        }
        .sa-main {
            flex: 1;
            min-width: 0;
        }
        @media (max-width: 900px) {
            .sa-shell {
                flex-direction: column;
            }
            .sa-sidebar {
                flex: none;
                width: 100%;
                position: static;
            }
            .sa-sidebar-nav {
                flex-direction: row;
                flex-wrap: wrap;
            }
            .sa-nav-item {
                flex: 1 1 auto;
                justify-content: center;
                min-width: 120px;
            }
            .sa-nav-badge {
                margin-left: 6px;
            }
        }
        .sa-logout {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff !important;
            text-decoration: none;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 700;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="sa-navbar">
        <div class="sa-brand">
            <span>MIKPAY</span>
            <span class="sa-badge">SUPER ADMIN</span>
            <span style="opacity:.7;font-size:12px;">@yield('heading', 'Payment Inbox')</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <a href="{{ route('dashboard') }}" style="color:#cbd5e1; font-size:12px; text-decoration:none;">Dashboard User</a>
            <a href="{{ route('superadmin.logout') }}" class="sa-logout" onclick="event.preventDefault(); document.getElementById('sa-logout-form').submit();">
                <i class="fa fa-sign-out"></i> Logout
            </a>
            <form id="sa-logout-form" action="{{ route('superadmin.logout') }}" method="POST" style="display:none;">@csrf</form>
        </div>
    </div>

    @php
        $navPendingCount = \App\Models\PendingPayment::where('status', 'pending')->count();
    @endphp
    <div class="sa-shell">
        <aside class="sa-sidebar">
            @include('superadmin.partials.nav')
        </aside>
        <main class="sa-main">
            @if(session('success'))<div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> {{ session('error') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger">{!! implode('<br>', $errors->all()) !!}</div>@endif
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>

