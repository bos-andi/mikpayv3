@extends('layouts.panel')

@section('title', 'Atur Data Pelanggan — MikPay')
@section('page', 'customers')

@section('content')
@php
    $input = 'w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-3.5 py-2.5 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-indigo-500 transition';
    $c = $customer;
@endphp

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Atur Data Pelanggan</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Isian sesuai template Excel MikPay · Router
            <span class="font-semibold text-indigo-600">{{ $router->displayName() }}</span>
        </p>
    </div>
    <a href="{{ panel_route('customers') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 transition">← Kembali</a>
</div>

@if (session('error'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-medium space-y-1">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@if (! ($mikrotikOk ?? true))
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-800">
        {{ $mikrotikMessage ?? 'Tidak bisa memuat PPP Profile dari MikroTik.' }}
    </div>
@endif

<form method="POST" action="{{ panel_route('customers.update') }}" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-6 w-full" autocomplete="off">
    @csrf

    <div class="rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 px-4 py-3 text-xs text-indigo-800 dark:text-indigo-200">
        Kolom bertanda <strong>*</strong> wajib. <strong>Paket</strong> diambil dari PPP Profile MikroTik — jika diubah, profile di router ikut berubah (session aktif akan diputus).
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Username PPP (MikroTik) *</label>
            <input type="text" name="customer_name" value="{{ old('customer_name', $customer_name) }}" required readonly
                   class="{{ $input }} font-mono bg-slate-100 dark:bg-slate-800 cursor-not-allowed">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Nama tampilan</label>
            <input type="text" name="display_name" value="{{ old('display_name', $c?->display_name) }}" class="{{ $input }}" placeholder="Nama pelanggan">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">No. HP</label>
            <input type="text" name="phone" value="{{ old('phone', $c?->phone) }}" class="{{ $input }}" placeholder="08xxxxxxxxxx">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Email</label>
            <input type="email" name="email" value="{{ old('email', $c?->email) }}" class="{{ $input }}" placeholder="opsional">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Zona</label>
            <input type="text" name="zone" value="{{ old('zone', $c?->zone) }}" class="{{ $input }}" placeholder="Contoh: Blok A / Desa X">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Paket (PPP Profile MikroTik) *</label>
            @if (! empty($profiles))
                <select name="package_label" required class="{{ $input }}">
                    @foreach ($profiles as $profile)
                        <option value="{{ $profile }}" @selected($selectedPackage === $profile)>{{ $profile }}</option>
                    @endforeach
                </select>
            @else
                <input type="text" name="package_label" value="{{ $selectedPackage }}" required class="{{ $input }}" placeholder="Nama profile MikroTik">
            @endif
            <p class="text-[10px] text-slate-500 mt-1">
                Profile sekarang di MikroTik:
                <span class="font-mono font-semibold">{{ $currentProfile ?: '—' }}</span>
            </p>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Tarif per bulan (Rp)</label>
            <input type="number" name="monthly_fee" value="{{ old('monthly_fee', $c?->monthly_fee ?? 0) }}" min="0" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Tanggal jatuh tempo (1–31) *</label>
            <input type="number" name="due_day" value="{{ old('due_day', $c?->due_day ?? 10) }}" min="0" max="31" required class="{{ $input }}">
            <p class="text-[10px] text-slate-500 mt-1">Isi 0 jika belum diatur.</p>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Mulai layanan (tanggal)</label>
            <input type="date" name="started_at" value="{{ old('started_at', optional($c?->started_at)->format('Y-m-d')) }}" class="{{ $input }}">
            <p class="text-[10px] text-slate-500 mt-1">Kosong = otomatis hari ini. Tagihan hanya dihitung dari bulan ini, bukan bulan sebelumnya.</p>
            @if ($c?->public_code)
                <p class="text-[10px] text-slate-500 mt-1">Kode pelanggan (cadangan link bayar): <span class="font-mono font-bold text-indigo-600">{{ $c->public_code }}</span></p>
            @endif
        </div>
        <div class="md:col-span-2 xl:col-span-3">
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Alamat</label>
            <textarea name="address" rows="2" class="{{ $input }}">{{ old('address', $c?->address) }}</textarea>
        </div>
        <div class="md:col-span-2 xl:col-span-3">
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1.5">Catatan</label>
            <textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes', $c?->notes) }}</textarea>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-indigo-600/20 transition">
            <i class="fa-solid fa-save"></i> Simpan
        </button>
        <a href="{{ panel_route('customers') }}" class="inline-flex items-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200">
            Batal
        </a>
    </div>
</form>
@endsection
