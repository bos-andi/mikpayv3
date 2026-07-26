@extends('layouts.superadmin')

@section('title', 'Superadmin Dashboard — MikPay')
@section('heading', 'Ringkasan Platform')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
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

<div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-3">
    <h2 class="text-sm font-bold text-slate-900">Selanjutnya</h2>
    <p class="text-xs text-slate-600 leading-relaxed max-w-2xl">
        Area ini nanti dipakai untuk manajemen pengguna/tenant dan pengaturan konten landing page.
        Menu <strong>Pengguna & Tenant</strong> serta <strong>Konten Landing</strong> masih placeholder.
    </p>
    <div class="flex flex-wrap gap-3 pt-2">
        <a href="{{ route('panel.dashboard') }}" class="inline-flex items-center gap-2 text-xs font-bold text-white gradient-bg px-4 py-2.5 rounded-xl shadow-md">
            <i class="fa-solid fa-display"></i> Coba Panel ISP
        </a>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 border border-slate-300 bg-white px-4 py-2.5 rounded-xl">
            Landing Page
        </a>
    </div>
</div>
@endsection
