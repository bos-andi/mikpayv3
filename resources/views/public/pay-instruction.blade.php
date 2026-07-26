@extends('layouts.public-pay')

@section('title', 'Instruksi Pembayaran')

@section('content')
@php
    $method = $checkout['method'] ?? '';
    $amount = (int) ($checkout['amount'] ?? $link->amount);
    $expiresAt = ! empty($checkout['expires_at'])
        ? \Carbon\Carbon::parse($checkout['expires_at'])
        : null;
@endphp

<div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl shadow-violet-900/5 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-violet-50/40">
        <a href="{{ url('/bayar/'.$link->token.'/metode?ganti=1') }}" id="changeMethodLink" class="text-[11px] font-bold text-violet-600 hover:underline">
            ← Ganti metode
        </a>
        <h1 class="text-lg font-black text-slate-900 mt-2 tracking-tight">{{ $checkout['label'] ?? 'Pembayaran' }}</h1>
        <p class="text-[11px] text-slate-500 mt-0.5">
            {{ $link->display_name ?: 'Pelanggan' }}
            @if (! empty($periodLabels))
                · {{ implode(', ', $periodLabels) }}
            @endif
        </p>
    </div>

    <div class="px-5 py-5 space-y-4">
        <div class="rounded-2xl bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white px-4 py-4 text-center shadow-lg shadow-violet-500/20">
            <p class="text-[10px] font-bold text-violet-100 uppercase tracking-wide">Total dibayar</p>
            <p class="text-3xl font-black tracking-tight mt-1">Rp {{ number_format($amount, 0, ',', '.') }}</p>
            @if ($expiresAt)
                <p class="text-[10px] text-violet-100/80 mt-1">Bayar sebelum {{ $expiresAt->format('d M Y H:i') }}</p>
            @endif
        </div>

        @if ($method === 'qris' && ! empty($checkout['qr_string']))
            <div class="text-center space-y-3">
                <p class="text-[11px] font-bold text-slate-600">Scan QRIS dengan aplikasi e-wallet / m-banking</p>
                <div class="inline-block bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">
                    <div id="qrisCanvas" class="mx-auto"></div>
                </div>
                <p class="text-[10px] text-slate-400">Pastikan nominal sesuai sebelum konfirmasi.</p>
            </div>
        @elseif ($method === 'va')
            <div class="space-y-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Bank</p>
                    <p class="text-base font-black text-slate-900">{{ $checkout['bank_code'] ?? '-' }}</p>
                </div>
                <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3">
                    <p class="text-[10px] font-bold text-violet-600 uppercase">Nomor Virtual Account</p>
                    <div class="flex items-center justify-between gap-2 mt-1">
                        <p id="vaNumber" class="text-xl font-black text-slate-900 tracking-wide font-mono">{{ $checkout['account_number'] ?? '-' }}</p>
                        <button type="button" onclick="copyVa()" class="text-[10px] font-bold px-3 py-1.5 rounded-xl bg-violet-600 text-white hover:bg-violet-500 shrink-0">
                            Salin
                        </button>
                    </div>
                    @if (! empty($checkout['account_name']))
                        <p class="text-[10px] text-slate-500 mt-1">a.n. {{ $checkout['account_name'] }}</p>
                    @endif
                </div>
                <ol class="text-[11px] text-slate-600 space-y-1.5 list-decimal pl-4">
                    <li>Buka m-banking / ATM {{ $checkout['bank_code'] ?? '' }}</li>
                    <li>Pilih Transfer / Bayar → Virtual Account</li>
                    <li>Masukkan nomor VA di atas</li>
                    <li>Pastikan nominal <strong>Rp {{ number_format($amount, 0, ',', '.') }}</strong></li>
                    <li>Selesaikan pembayaran — halaman ini akan update otomatis</li>
                </ol>
            </div>
        @endif

        <div id="payWait" class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-[11px] text-amber-900 font-semibold flex items-center gap-2">
            <i class="fa-solid fa-spinner fa-spin text-amber-600"></i>
            Menunggu pembayaran…
        </div>
        <div id="payDone" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-[11px] text-emerald-800 font-semibold">
            Pembayaran terdeteksi. Mengalihkan…
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    const qrString = @js($checkout['qr_string'] ?? '');
    if (qrString && document.getElementById('qrisCanvas')) {
        new QRCode(document.getElementById('qrisCanvas'), {
            text: qrString,
            width: 220,
            height: 220,
            correctLevel: QRCode.CorrectLevel.M,
        });
    }

    window.copyVa = function () {
        const el = document.getElementById('vaNumber');
        const text = el ? el.textContent.trim() : '';
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            (window.mikpayToast || alert)('Nomor VA disalin.');
        }).catch(() => {
            (window.mikpayToast || alert)(text);
        });
    };

    document.getElementById('changeMethodLink')?.addEventListener('click', async function (e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        const ok = await (window.mikpayConfirm || (() => Promise.resolve(confirm('Ganti metode?'))))({
            title: 'Ganti metode bayar?',
            message: 'Instruksi pembayaran saat ini akan diganti. QR / nomor VA yang lama tidak berlaku lagi.',
            confirmText: 'Ya, ganti',
            cancelText: 'Tetap di sini',
        });
        if (ok) window.location.href = href;
    });

    const statusUrl = @js($statusUrl);
    const waitEl = document.getElementById('payWait');
    const doneEl = document.getElementById('payDone');

    async function poll() {
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.paid && data.redirect) {
                if (waitEl) waitEl.classList.add('hidden');
                if (doneEl) doneEl.classList.remove('hidden');
                window.location.href = data.redirect;
                return;
            }
        } catch (_) {}
        setTimeout(poll, 4000);
    }
    setTimeout(poll, 3000);
})();
</script>
@endpush
