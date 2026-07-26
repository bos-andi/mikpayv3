@php
    $isDash = request()->routeIs('superadmin.dashboard');
    $isPay = request()->routeIs('superadmin.payments');
    $isUsers = request()->routeIs('superadmin.users');
    $isSub = request()->routeIs('superadmin.subscription-pricing');
@endphp
<nav class="sa-sidebar-nav" aria-label="Menu Super Admin">
    <a href="{{ route('superadmin.dashboard') }}" class="sa-nav-item {{ $isDash ? 'active' : '' }}">
        <i class="fa fa-tachometer"></i>
        <span>Dashboard</span>
    </a>
    <a href="{{ route('superadmin.payments') }}" class="sa-nav-item {{ $isPay ? 'active' : '' }}">
        <i class="fa fa-credit-card"></i>
        <span>Pembayaran</span>
        @if(isset($navPendingCount) && (int) $navPendingCount > 0)
            <span class="sa-nav-badge">{{ $navPendingCount }}</span>
        @endif
    </a>
    <a href="{{ route('superadmin.users') }}" class="sa-nav-item {{ $isUsers ? 'active' : '' }}">
        <i class="fa fa-users"></i>
        <span>Pengguna</span>
    </a>
    <a href="{{ route('superadmin.subscription-pricing') }}" class="sa-nav-item {{ $isSub ? 'active' : '' }}">
        <i class="fa fa-cube"></i>
        <span>Paket langganan</span>
    </a>
</nav>
