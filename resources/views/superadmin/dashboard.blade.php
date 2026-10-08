@extends('layouts.superadmin')

@section('title', 'Superadmin Dashboard — MikPay')
@section('heading', 'Ringkasan Platform')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Tenant</p>
        <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $tenantCount }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tenant Aktif</p>
        <p class="text-3xl font-extrabold text-emerald-600 mt-2">{{ $activeTenantCount }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total User</p>
        <p class="text-3xl font-extrabold text-indigo-600 mt-2">{{ $userCount }}</p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white border border-amber-200 rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Menunggu Dibayar</p>
        <p class="text-3xl font-extrabold text-amber-600 mt-2">{{ $paymentStats['pending'] ?? 0 }}</p>
    </div>
    <div class="bg-white border border-emerald-200 rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Lunas</p>
        <p class="text-3xl font-extrabold text-emerald-600 mt-2">{{ $paymentStats['approved'] ?? 0 }}</p>
    </div>
    <div class="bg-white border border-rose-200 rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-rose-700">Batal / Kedaluwarsa</p>
        <p class="text-3xl font-extrabold text-rose-600 mt-2">{{ $paymentStats['rejected'] ?? 0 }}</p>
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-3">
    <h2 class="text-sm font-bold text-slate-900">Kelola platform</h2>
    <p class="text-xs text-slate-600 leading-relaxed max-w-2xl">
        Kelola tenant, paket harga landing, konten marketing, dan pantau pembayaran langganan SaaS (otomatis via Xendit).
    </p>
    <div class="flex flex-wrap gap-3 pt-2">
        <a href="{{ route('superadmin.payments') }}" class="inline-flex items-center gap-2 text-xs font-bold text-white gradient-bg px-4 py-2.5 rounded-xl shadow-md">
            <i class="fa-solid fa-credit-card"></i> Inbox Pembayaran
        </a>
        <a href="{{ route('superadmin.users') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 border border-slate-300 bg-white px-4 py-2.5 rounded-xl">
            <i class="fa-solid fa-users"></i> Pengguna & Tenant
        </a>
        <a href="{{ route('superadmin.subscription-pricing') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 border border-slate-300 bg-white px-4 py-2.5 rounded-xl">
            <i class="fa-solid fa-tags"></i> Paket Langganan
        </a>
        <a href="{{ route('superadmin.landing') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 border border-slate-300 bg-white px-4 py-2.5 rounded-xl">
            <i class="fa-solid fa-newspaper"></i> Konten Landing
        </a>
        <a href="{{ route('panel.dashboard') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 border border-slate-300 bg-white px-4 py-2.5 rounded-xl">
            <i class="fa-solid fa-display"></i> Panel ISP
        </a>
    </div>
</div>
@endsection
