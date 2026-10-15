@extends('layouts.public-pay')

@section('title', 'Pilih Metode Bayar')

@section('content')
@php
    $name = $link->display_name ?: 'Pelanggan';
@endphp

<div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl shadow-violet-900/5 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-violet-50/40">
        <a href="{{ url('/bayar/'.$link->token) }}" class="text-[11px] font-bold text-violet-600 hover:underline">
            ← Kembali
        </a>
        <h1 class="text-lg font-black text-slate-900 mt-2 tracking-tight">Pilih metode bayar</h1>
        <p class="text-[11px] text-slate-500 mt-0.5">{{ $name }} · Rp {{ number_format($link->amount, 0, ',', '.') }}</p>
        @if (! empty($periodLabels))
            <p class="text-[10px] text-slate-400 mt-1">{{ implode(' · ', $periodLabels) }}</p>
        @endif
    </div>

    <div class="px-5 py-5 space-y-3">
        @if (! $checkoutReady)
            <div class="rounded-2xl bg-amber-50 border border-amber-200 px-3 py-2 text-[11px] text-amber-800">
                Pembayaran online belum tersedia. Hubungi admin ISP.
            </div>
        @else
            <form method="POST" action="{{ url('/bayar/'.$link->token.'/metode') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="method" value="qris">
                <button type="submit" class="w-full text-left rounded-2xl border border-slate-200 hover:border-violet-400 hover:bg-violet-50/60 transition px-4 py-3.5 flex items-center gap-3">
                    <div class="h-11 w-11 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-qrcode text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-900">QRIS</p>
                        <p class="text-[10px] text-slate-500">Scan dari GoPay, OVO, DANA, ShopeePay, mobile banking, dll.</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
                </button>
            </form>

            <div class="pt-2">
                <p class="text-[11px] font-bold text-slate-600 mb-2">Transfer Virtual Account</p>
                <div class="space-y-2">
                    @foreach ($vaBanks as $bank)
                        <form method="POST" action="{{ url('/bayar/'.$link->token.'/metode') }}">
                            @csrf
                            <input type="hidden" name="method" value="va">
                            <input type="hidden" name="bank" value="{{ $bank }}">
                            <button type="submit" class="w-full text-left rounded-2xl border border-slate-200 hover:border-violet-400 hover:bg-violet-50/60 transition px-4 py-3 flex items-center gap-3">
                                <div class="h-10 w-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 text-[10px] font-black">
                                    {{ $bank }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-900">VA {{ $bank }}</p>
                                    <p class="text-[10px] text-slate-500">Transfer ke nomor Virtual Account</p>
                                </div>
                                <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<p class="text-center text-[10px] text-slate-400 mt-5">Pembayaran diproses aman via {{ $gatewayName }} · tetap di halaman MikPay</p>
@endsection
