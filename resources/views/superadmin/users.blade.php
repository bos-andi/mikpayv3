@extends('layouts.superadmin')

@section('title', 'Pengguna & Tenant — Superadmin')
@section('heading', 'Pengguna & Tenant')

@section('content')
@php
    $planOptions = ['trial' => 'Trial', 'starter' => 'Starter', 'pro' => 'Pro', 'enterprise' => 'Enterprise'];
    $statusOptions = ['active' => 'Aktif', 'suspended' => 'Suspended', 'trial' => 'Trial', 'inactive' => 'Inactive'];
    $roleLabel = [
        'superadmin' => 'Superadmin',
        'tenant_admin' => 'Admin Tenant',
        'tenant_user' => 'User Tenant',
    ];
@endphp

@if (session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs font-semibold px-4 py-3">
        {{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs font-semibold px-4 py-3">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Tenant</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $tenantCount }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tenant Aktif</p>
        <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $activeTenantCount }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">User (non-superadmin)</p>
        <p class="text-2xl font-extrabold text-indigo-600 mt-1">{{ $userCount }}</p>
    </div>
</div>

<form method="GET" action="{{ route('superadmin.users') }}" class="mb-6 flex flex-col sm:flex-row gap-2">
    <div class="relative flex-1">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari tenant, subdomain, nama, atau email..."
               class="w-full pl-8 pr-3 py-2.5 rounded-xl border border-slate-200 bg-white text-xs focus:outline-none focus:border-indigo-500">
    </div>
    <button type="submit" class="text-xs font-bold px-4 py-2.5 rounded-xl bg-slate-900 text-white">Cari</button>
    @if ($q !== '')
        <a href="{{ route('superadmin.users') }}" class="text-xs font-bold px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-600 text-center">Reset</a>
    @endif
</form>

<div class="space-y-4">
    @forelse ($tenants as $tenant)
        @php
            $panelUrl = 'https://'.$tenant->panelHost();
            $statusClass = match ($tenant->status) {
                'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'suspended' => 'bg-rose-50 text-rose-700 border-rose-200',
                'trial' => 'bg-amber-50 text-amber-700 border-amber-200',
                default => 'bg-slate-50 text-slate-600 border-slate-200',
            };
        @endphp
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-sm font-extrabold text-slate-900">{{ $tenant->name }}</h2>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $statusClass }}">{{ strtoupper($tenant->status) }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">{{ strtoupper($tenant->plan ?: '—') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 font-mono">
                        {{ $tenant->subdomain }}.{{ config('mikpay.tenant_base_domain') }}
                        · {{ $tenant->users_count }} user
                        · {{ $tenant->routers_count }} router
                        · {{ $tenant->tunnel_accounts_count }} tunnel
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a href="{{ $panelUrl }}" target="_blank" rel="noopener" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-500">
                        <i class="fa-solid fa-external-link mr-1"></i> Buka Panel
                    </a>
                    <form method="POST" action="{{ route('superadmin.tenants.toggle', $tenant) }}"
                          onsubmit="return confirm('{{ $tenant->status === 'active' ? 'Suspend tenant ini?' : 'Aktifkan kembali tenant ini?' }}')">
                        @csrf
                        <button type="submit" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg border {{ $tenant->status === 'active' ? 'border-rose-200 text-rose-600 hover:bg-rose-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                            {{ $tenant->status === 'active' ? 'Suspend' : 'Aktifkan' }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="p-4 grid grid-cols-1 xl:grid-cols-2 gap-4">
                <form method="POST" action="{{ route('superadmin.tenants.update', $tenant) }}" class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-3">
                    @csrf
                    @method('PUT')
                    <p class="text-[11px] font-bold text-slate-700">Edit Tenant</p>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required
                               class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Status</label>
                            <select name="status" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs">
                                @foreach ($statusOptions as $val => $label)
                                    <option value="{{ $val }}" @selected(old('status', $tenant->status) === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Paket</label>
                            <select name="plan" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs">
                                @foreach ($planOptions as $val => $label)
                                    <option value="{{ $val }}" @selected(old('plan', $tenant->plan) === $val)>{{ $label }}</option>
                                @endforeach
                                @if ($tenant->plan && ! isset($planOptions[$tenant->plan]))
                                    <option value="{{ $tenant->plan }}" selected>{{ $tenant->plan }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400">Subdomain terkunci: <span class="font-mono font-semibold text-slate-600">{{ $tenant->subdomain }}</span></p>
                    <button type="submit" class="text-[11px] font-bold px-3 py-2 rounded-lg bg-slate-900 text-white">Simpan Tenant</button>
                </form>

                <div class="space-y-3">
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-3 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <p class="text-[11px] font-bold text-slate-700">User Tenant</p>
                            <span class="text-[10px] text-slate-400">{{ $tenant->users->count() }} akun</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($tenant->users as $user)
                                <div class="p-3 space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-slate-900 truncate">{{ $user->name }}</p>
                                            <p class="text-[11px] font-mono text-slate-500 truncate">{{ $user->email }}</p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700">{{ $roleLabel[$user->role] ?? $user->role }}</span>
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                    {{ $user->is_active ? 'AKTIF' : 'NONAKTIF' }}
                                                </span>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('superadmin.users.toggle', $user) }}"
                                              onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan user ini?' : 'Aktifkan user ini?' }}')">
                                            @csrf
                                            <button type="submit" class="text-[10px] font-bold px-2 py-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                    <details class="text-[11px]">
                                        <summary class="cursor-pointer font-semibold text-indigo-600">Edit user</summary>
                                        <form method="POST" action="{{ route('superadmin.users.update', $user) }}" class="mt-2 space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-2.5">
                                            @csrf
                                            @method('PUT')
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Nama</label>
                                                    <input type="text" name="name" value="{{ $user->name }}" required class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Role</label>
                                                    <select name="role" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                                                        <option value="tenant_admin" @selected($user->role === 'tenant_admin')>Admin Tenant</option>
                                                        <option value="tenant_user" @selected($user->role === 'tenant_user')>User Tenant</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Email</label>
                                                <input type="email" name="email" value="{{ $user->email }}" required class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Password baru</label>
                                                    <input type="password" name="password" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs" placeholder="Kosongkan jika tidak diganti">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Ulangi password</label>
                                                    <input type="password" name="password_confirmation" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                                                </div>
                                            </div>
                                            <label class="inline-flex items-center gap-2 text-[10px] font-semibold text-slate-600">
                                                <input type="checkbox" name="is_active" value="1" @checked($user->is_active) class="rounded border-slate-300">
                                                Akun aktif
                                            </label>
                                            <button type="submit" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-indigo-600 text-white">Simpan User</button>
                                        </form>
                                    </details>
                                </div>
                            @empty
                                <p class="p-4 text-[11px] text-slate-500">Belum ada user di tenant ini.</p>
                            @endforelse
                        </div>
                    </div>

                    <details class="rounded-xl border border-dashed border-violet-300 bg-violet-50/50 p-3">
                        <summary class="cursor-pointer text-[11px] font-bold text-violet-700">+ Tambah user ke tenant</summary>
                        <form method="POST" action="{{ route('superadmin.tenants.users.store', $tenant) }}" class="mt-3 space-y-2">
                            @csrf
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Nama</label>
                                    <input type="text" name="name" required class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Role</label>
                                    <select name="role" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                                        <option value="tenant_admin">Admin Tenant</option>
                                        <option value="tenant_user">User Tenant</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Email</label>
                                <input type="email" name="email" required class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Password</label>
                                    <input type="password" name="password" required class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Ulangi</label>
                                    <input type="password" name="password_confirmation" required class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs">
                                </div>
                            </div>
                            <button type="submit" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-violet-600 text-white">Buat User</button>
                        </form>
                    </details>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-sm text-slate-500">
            Tidak ada tenant{{ $q !== '' ? ' untuk pencarian ini' : '' }}.
        </div>
    @endforelse
</div>

@if ($superadmins->isNotEmpty())
    <div class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Superadmin</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Akun platform (bukan milik tenant).</p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($superadmins as $admin)
                <div class="px-4 py-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-slate-900">{{ $admin->name }}</p>
                        <p class="text-[11px] font-mono text-slate-500">{{ $admin->email }}</p>
                    </div>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full {{ $admin->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ $admin->is_active ? 'AKTIF' : 'NONAKTIF' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if ($orphanUsers->isNotEmpty())
    <div class="mt-6 bg-amber-50 border border-amber-200 rounded-2xl p-4">
        <h3 class="text-sm font-bold text-amber-900">User tanpa tenant</h3>
        <ul class="mt-2 space-y-1 text-xs text-amber-800">
            @foreach ($orphanUsers as $u)
                <li>{{ $u->name }} · {{ $u->email }} · {{ $u->role }}</li>
            @endforeach
        </ul>
    </div>
@endif
@endsection
