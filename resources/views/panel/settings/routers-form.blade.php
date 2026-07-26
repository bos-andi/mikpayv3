@extends('layouts.panel')

@section('title', ($router ? 'Edit' : 'Tambah').' Router — MikPay')
@section('page', 'settings')

@section('content')
@php
    $isEdit = (bool) $router;
    $hostValue = old('host');
    if ($hostValue === null && $router) {
        $hostValue = $router->hostDisplay();
    }
    $input = 'w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3.5 py-2.5 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-indigo-500 transition';
@endphp

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
            {{ $isEdit ? 'Edit Router MikroTik' : 'Tambah Router MikroTik' }}
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            @if ($isEdit)
                Session <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $router->name }}</span> · simpan kredensial API lalu Test Koneksi.
            @else
                Tambahkan koneksi API RouterOS untuk tenant ini.
            @endif
        </p>
    </div>
    <a href="{{ panel_route('settings') }}?tab=mikrotik" class="text-xs font-bold text-slate-500 hover:text-indigo-600 transition">
        ← Kembali ke pengaturan
    </a>
</div>

@if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-500/10 dark:border-emerald-500/30 px-4 py-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 dark:border-rose-500/30 px-4 py-3 text-xs font-semibold text-rose-700 dark:text-rose-300">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 dark:border-rose-500/30 px-4 py-3 text-xs text-rose-700 dark:text-rose-300 font-medium space-y-1">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@if ($isEdit && $router->last_check_message)
    <div class="rounded-xl border px-4 py-3 text-xs font-medium {{ $router->last_check_ok ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:border-amber-500/30 dark:text-amber-300' }}">
        <span class="font-bold">{{ $router->last_check_ok ? 'API OK' : 'Status terakhir' }}:</span>
        {{ $router->last_check_message }}
        @if ($router->last_checked_at)
            <span class="opacity-70"> · {{ $router->last_checked_at->diffForHumans() }}</span>
        @endif
    </div>
@endif

<form
    method="POST"
    action="{{ $isEdit ? panel_route('settings.routers.update', $router) : panel_route('settings.routers.store') }}"
    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-6 w-full"
    autocomplete="off"
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[11px]">
                <i class="fa-solid fa-bookmark"></i>
            </span>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Session</h3>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Nama Session</label>
            <input type="text" name="name" value="{{ old('name', $router->name ?? '') }}" required
                   pattern="[a-zA-Z0-9_-]+" title="Huruf, angka, underscore, strip saja"
                   class="{{ $input }}"
                   placeholder="Contoh: ANNETWIFI">
            <p class="text-[10px] text-slate-500 mt-1.5">Tanpa spasi. Jangan pakai nama “mikpay”.</p>
        </div>
    </div>

    <div class="border-t border-slate-100 dark:border-slate-800"></div>

    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[11px]">
                <i class="fa-solid fa-server"></i>
            </span>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">MikroTik API</h3>
                <p class="text-[10px] text-slate-500">Host dan port digabung: <span class="font-mono">host:port</span></p>
            </div>
        </div>
        @php
            $suggestedApiHost = null;
            try {
                $acc = \App\Models\TunnelAccount::query()
                    ->with(['portMaps', 'node'])
                    ->where('tenant_id', current_tenant()?->id)
                    ->whereIn('status', ['active', 'pending'])
                    ->latest('id')
                    ->first();
                $suggestedApiHost = $acc?->portMaps->firstWhere('label', 'API')?->endpoint();
            } catch (\Throwable) {
                $suggestedApiHost = null;
            }
        @endphp
        @if ($suggestedApiHost)
            <div class="rounded-xl border border-violet-200 dark:border-violet-500/30 bg-violet-50/70 dark:bg-violet-500/10 px-3 py-2.5 text-[11px] text-violet-950 dark:text-violet-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <p>
                    Remote via MikPay Tunnel:
                    <span class="font-mono font-bold">{{ $suggestedApiHost }}</span>
                    <span class="text-violet-700/70 dark:text-violet-200/70">(setelah script OpenVPN running)</span>
                </p>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" onclick="document.querySelector('input[name=host]').value=@js($suggestedApiHost)" class="text-[10px] font-bold px-2.5 py-1 rounded-lg bg-violet-600 text-white hover:bg-violet-500">Pakai ini</button>
                    <a href="{{ panel_route('remote') }}#ovpn-setup" class="text-[10px] font-bold text-violet-700 dark:text-violet-300 hover:underline">Setup script →</a>
                </div>
            </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="md:col-span-3 lg:col-span-1">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">IP:Port</label>
                <input type="text" name="host" value="{{ $hostValue }}" required
                       class="{{ $input }} font-mono"
                       placeholder="{{ $suggestedApiHost ?: 'an-1.mikpay.link:1001' }}">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Username</label>
                <input type="text" name="username" value="{{ old('username', $router->username ?? '') }}" required
                       class="{{ $input }}" autocomplete="off">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Password</label>
                <input type="password" name="password" {{ $isEdit ? '' : 'required' }}
                       class="{{ $input }}"
                       placeholder="{{ $isEdit ? 'Kosongkan jika tidak diubah' : '' }}"
                       autocomplete="new-password">
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 dark:border-slate-800"></div>

    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[11px]">
                <i class="fa-solid fa-circle-info"></i>
            </span>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Data Router</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
            <div class="sm:col-span-2 lg:col-span-1 xl:col-span-2">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Nama Router</label>
                <input type="text" name="router_name" value="{{ old('router_name', $router->router_name ?? '') }}"
                       class="{{ $input }}" placeholder="Nama tampil di dashboard">
            </div>
            <div class="xl:col-span-1">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Domain</label>
                <input type="text" name="domain" value="{{ old('domain', $router->domain ?? '') }}"
                       class="{{ $input }}" placeholder="domain.com">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Mata Uang</label>
                <input type="text" name="currency" value="{{ old('currency', $router->currency ?? 'Rp') }}" maxlength="4"
                       class="{{ $input }}">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Expiry (hari)</label>
                <input type="number" name="expiry_days" value="{{ old('expiry_days', $router->expiry_days ?? 10) }}" min="1" max="365"
                       class="{{ $input }}">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Status</label>
                <select name="status" class="{{ $input }}">
                    <option value="enable" {{ old('status', $router->status ?? 'enable') === 'enable' ? 'selected' : '' }}>Enable</option>
                    <option value="disable" {{ old('status', $router->status ?? '') === 'disable' ? 'selected' : '' }}>Disable</option>
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-3 xl:col-span-6 flex items-center pt-1">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                           {{ old('is_default', $router->is_default ?? false) ? 'checked' : '' }}>
                    Jadikan router default (dipakai otomatis saat login)
                </label>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-indigo-600/20 transition">
            <i class="fa-solid fa-save"></i> Simpan
        </button>
        <a href="{{ panel_route('settings') }}?tab=mikrotik" class="inline-flex items-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
            Batal
        </a>
    </div>
</form>

@if ($isEdit)
    <div class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row sm:items-center gap-3">
        <form method="POST" action="{{ panel_route('settings.routers.test', $router) }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-emerald-600/20 transition">
                <i class="fa-solid fa-plug"></i> Test Koneksi
            </button>
        </form>
        <form method="POST" action="{{ panel_route('settings.routers.select', $router) }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-indigo-600/20 transition">
                <i class="fa-solid fa-check-double"></i> Jadikan Aktif
            </button>
        </form>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
            Router aktif menentukan sumber data pelanggan & isolir. Simpan dulu jika IP:Port / password diubah sebelum Test.
        </p>
    </div>
@endif
@endsection
