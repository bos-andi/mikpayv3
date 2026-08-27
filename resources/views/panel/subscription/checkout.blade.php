@extends('layouts.panel')

@section('title', 'Pembayaran Langganan — MikPay')
@section('page', 'subscription')

@section('content')
<div class="max-w-3xl space-y-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Checkout: {{ $selectedPackage['name'] ?? $selectedPackageKey }}</h2>
                <p class="text-xs text-slate-500 mt-1">Transfer sesuai nominal, lalu unggah bukti pembayaran.</p>
            </div>
            <p class="text-lg font-extrabold text-indigo-600 shrink-0">Rp {{ number_format((int) ($selectedPackage['price'] ?? 0), 0, ',', '.') }}</p>
        </div>

        <div class="space-y-3">
            @foreach ($paymentChannels as $key => $ch)
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4 text-xs">
                    <p class="font-extrabold text-slate-800 dark:text-slate-100 mb-2">{{ $ch['name'] ?? $key }}</p>
                    @if ($key === 'bank_transfer')
                        <p>{{ $ch['bank_name'] }} · {{ $ch['account_number'] }}</p>
                        <p class="text-slate-500">a.n. {{ $ch['account_name'] }}</p>
                    @elseif ($key === 'ewallet')
                        <p>{{ $ch['wallet_name'] }} · {{ $ch['wallet_number'] }}</p>
                    @elseif ($key === 'qris')
                        <p class="text-slate-500 mb-2">{{ $ch['merchant_name'] ?? 'MIKPAY' }}</p>
                        @if (!empty($ch['qr_image']))
                            <img src="{{ asset($ch['qr_image']) }}" alt="QRIS" class="max-w-[220px] rounded-lg border border-slate-200 bg-white">
                        @endif
                    @endif
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ panel_route('subscription.pay') }}" enctype="multipart/form-data" class="space-y-4 pt-2">
            @csrf
            <input type="hidden" name="package" value="{{ $selectedPackageKey }}">

            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">Metode pembayaran</label>
                <select name="payment_channel" required class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm">
                    @foreach ($paymentChannels as $key => $ch)
                        <option value="{{ $key }}">{{ $ch['name'] ?? $key }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">Bukti transfer (jpg/png/pdf, max 5MB)</label>
                <input type="file" name="proof_file" required accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">Catatan (opsional)</label>
                <textarea name="customer_note" rows="2" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm" placeholder="Misal: transfer dari BCA a.n. ..."></textarea>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center gap-2 text-xs font-bold text-white gradient-bg px-5 py-2.5 rounded-xl shadow-md">
                    <i class="fa-solid fa-upload"></i> Kirim bukti
                </button>
                <button type="button" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 border border-slate-300 px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900"
                        onclick="document.getElementById('cancelCheckoutForm').submit()">
                    Batal
                </button>
            </div>
        </form>

        <form id="cancelCheckoutForm" method="POST" action="{{ panel_route('subscription.cancel') }}" class="hidden">
            @csrf
        </form>
    </div>
</div>
@endsection
