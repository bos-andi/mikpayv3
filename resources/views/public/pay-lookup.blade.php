@extends('layouts.public-pay')

@section('title', 'Cari Tagihan')

@section('content')
<div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl shadow-violet-900/5 p-5 space-y-4">
    <div class="flex items-start gap-3">
        <img src="{{ asset(config('mikpay.brand.logo', 'logo-mikpay.png')) }}?v=2" alt="MikPay" class="h-12 w-12 object-contain shrink-0">
        <div>
            <h1 class="text-base font-extrabold text-slate-900">Cari tagihan</h1>
            <p class="text-[11px] text-slate-500 mt-1">Masukkan kode pelanggan + 4 digit akhir nomor HP (tanpa login).</p>
        </div>
    </div>

    <form method="POST" action="{{ url('/bayar') }}" class="space-y-3">
        @csrf
        <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Kode pelanggan</label>
            <input type="text" name="public_code" value="{{ old('public_code') }}" required maxlength="16"
                   class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-mono uppercase tracking-wider focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20"
                   placeholder="Contoh: AG7K2M">
            @error('public_code')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">4 digit akhir HP</label>
            <input type="text" name="phone_last4" value="{{ old('phone_last4') }}" required maxlength="4" pattern="\d{4}" inputmode="numeric"
                   class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-mono tracking-widest focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20"
                   placeholder="1234">
            @error('phone_last4')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="w-full bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold py-3 rounded-2xl shadow-lg shadow-violet-600/20 transition">
            Lanjut bayar
        </button>
    </form>
</div>

@if ($tenant?->subdomain)
<a href="{{ tenant_panel_url($tenant, '/lapor') }}" class="mt-4 flex items-center gap-3 bg-white border border-orange-200 rounded-3xl px-4 py-3 shadow-sm hover:border-orange-300 transition">
    <span class="h-9 w-9 rounded-2xl bg-gradient-to-br from-orange-500 to-rose-500 text-white flex items-center justify-center shrink-0"><i class="fa-solid fa-headset text-sm"></i></span>
    <span class="min-w-0 flex-1">
        <span class="block text-sm font-bold text-slate-900">Internet bermasalah?</span>
        <span class="block text-[11px] text-slate-500">Lapor gangguan ke {{ $tenant->name ?? 'ISP Anda' }}</span>
    </span>
    <i class="fa-solid fa-chevron-right text-xs text-slate-400"></i>
</a>
@endif
@endsection
