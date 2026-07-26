@extends('layouts.public-pay')

@section('title', 'Bayar Tagihan')

@section('content')
@php
    $payable = $link->isPayable();
    $periodItems = $periodItems ?? [];
@endphp

<div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl shadow-violet-900/5 overflow-hidden">
    <div class="px-5 py-5 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-violet-50/40">
        <p class="text-[10px] font-bold text-violet-600 uppercase tracking-wider">Tagihan untuk</p>
        <h1 class="text-xl font-black text-slate-900 mt-1 tracking-tight">{{ $link->display_name ?: 'Pelanggan' }}</h1>
        @if ($phoneHint)
            <p class="text-[11px] text-slate-500 mt-1">HP terdaftar: {{ $phoneHint }}</p>
        @endif
    </div>

    <div class="px-5 py-5 space-y-4">
        @if ($statusQuery === 'success')
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-3 py-2.5 text-[11px] text-emerald-800 font-semibold">
                Pembayaran sedang diproses. Status akan diperbarui setelah konfirmasi Xendit.
            </div>
        @elseif ($statusQuery === 'failed')
            <div class="rounded-2xl bg-amber-50 border border-amber-200 px-3 py-2.5 text-[11px] text-amber-800 font-semibold">
                Pembayaran belum selesai. Anda bisa mencoba lagi.
            </div>
        @endif

        @if (empty($periodItems) && ! $link->isExpired())
            <div class="text-center py-6 space-y-2">
                <div class="mx-auto h-14 w-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <p class="text-sm font-bold text-slate-900">Sudah lunas</p>
                <p class="text-[11px] text-slate-500">Terima kasih. Layanan akan diaktifkan kembali otomatis jika tidak ada tunggakan lain.</p>
            </div>
        @elseif ($link->isExpired())
            <div class="text-center py-6 space-y-2">
                <div class="mx-auto h-14 w-14 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-link-slash"></i>
                </div>
                <p class="text-sm font-bold text-slate-900">Link tidak berlaku</p>
                <p class="text-[11px] text-slate-500">Link kadaluarsa atau dibatalkan. Minta link baru ke admin ISP.</p>
            </div>
        @else
            @if (! empty($isolirInfo))
                @php
                    $days = $isolirInfo['days'];
                    $tone = ($days !== null && $days > 2)
                        ? 'bg-amber-50 border-amber-200 text-amber-900'
                        : 'bg-orange-50 border-orange-200 text-orange-900';
                @endphp
                <div class="rounded-2xl border px-3.5 py-3 {{ $tone }}">
                    <p class="text-[11px] font-bold">
                        <i class="fa-solid fa-clock mr-1 text-orange-500"></i>
                        {{ $isolirInfo['label'] ?: 'Hitung mundur penonaktifan' }}
                    </p>
                    <p class="text-[10px] mt-0.5 opacity-80">
                        Jatuh tempo tgl {{ $isolirInfo['due_day'] }}
                        · masa tenggang {{ $isolirInfo['grace_days'] }} hari
                        @if (! empty($isolirInfo['date']))
                            · dinonaktifkan otomatis mulai {{ \Carbon\Carbon::parse($isolirInfo['date'])->translatedFormat('d M Y') }}
                        @endif
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ url('/bayar/'.$link->token.'/checkout') }}" id="payCheckoutForm" class="space-y-4">
                @csrf
                <div>
                    <div class="flex items-end justify-between gap-2 mb-2">
                        <div>
                            <p class="text-[11px] font-bold text-slate-700">Pilih periode bayar</p>
                            <p class="text-[10px] text-slate-400">Bayar dari bulan tertua dulu. Tidak bisa loncat bulan.</p>
                        </div>
                        @if ($link->expires_at)
                            <p class="text-[9px] text-slate-400 text-right shrink-0">Link s/d<br>{{ $link->expires_at->format('d M Y') }}</p>
                        @endif
                    </div>

                    <div class="space-y-2" id="periodList">
                        @foreach ($periodItems as $i => $item)
                            <label class="period-row flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3.5 py-3 cursor-pointer transition hover:border-violet-300 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50 has-[:checked]:shadow-sm">
                                <input
                                    type="checkbox"
                                    name="periods[]"
                                    value="{{ $item['period'] }}"
                                    data-index="{{ $i }}"
                                    data-amount="{{ $item['amount'] }}"
                                    class="period-check h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                    @checked($i === 0)
                                >
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-900">{{ $item['label'] }}</p>
                                    @if ($i === 0)
                                        <p class="text-[10px] text-violet-600 font-semibold">Bulan tertua — wajib dibayar dulu</p>
                                    @endif
                                </div>
                                <p class="text-sm font-black text-slate-900 tabular-nums shrink-0">
                                    Rp {{ number_format($item['amount'], 0, ',', '.') }}
                                </p>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white px-4 py-4 flex items-end justify-between gap-3 shadow-lg shadow-violet-500/20">
                    <div>
                        <p class="text-[10px] font-bold text-violet-100 uppercase tracking-wide">Total dibayar</p>
                        <p class="text-2xl font-black tracking-tight mt-0.5" id="payTotalLabel">Rp 0</p>
                        <p class="text-[10px] text-violet-100/80 mt-1" id="payPeriodSummary"></p>
                    </div>
                    <img src="{{ asset(config('mikpay.brand.logo', 'logo-mikpay.png')) }}?v=2" alt="" class="h-10 w-10 object-contain shrink-0 drop-shadow">
                </div>

                @if ($xenditReady)
                    <button type="submit" id="paySubmitBtn" class="w-full bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold py-3.5 rounded-2xl shadow-lg shadow-violet-600/25 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-arrow-right mr-1.5"></i>
                        <span id="paySubmitText">Lanjut bayar</span>
                    </button>
                    <p class="text-[10px] text-center text-slate-400">QRIS · Transfer VA — tampilan MikPay</p>
                @else
                    <div class="rounded-2xl bg-amber-50 border border-amber-200 px-3 py-2 text-[11px] text-amber-800">
                        Pembayaran online belum dikonfigurasi. Hubungi admin ISP Anda.
                    </div>
                @endif
            </form>
        @endif
    </div>
</div>

<p class="text-center text-[10px] text-slate-500 mt-5">
    Punya kode pelanggan?
    <a href="{{ url('/bayar') }}" class="text-violet-600 font-bold hover:underline">Cari tagihan</a>
</p>
@endsection

@if (! empty($periodItems) && ($link->isPayable() || $link->status === 'pending'))
@push('scripts')
<script>
(function () {
    const checks = Array.from(document.querySelectorAll('.period-check'));
    const totalEl = document.getElementById('payTotalLabel');
    const summaryEl = document.getElementById('payPeriodSummary');
    const submitText = document.getElementById('paySubmitText');
    const submitBtn = document.getElementById('paySubmitBtn');
    const form = document.getElementById('payCheckoutForm');

    function formatRp(n) {
        return 'Rp ' + Number(n).toLocaleString('id-ID');
    }

    function enforcePrefix(changedIdx, checked) {
        if (checked) {
            for (let i = 0; i <= changedIdx; i++) checks[i].checked = true;
        } else {
            for (let i = changedIdx; i < checks.length; i++) checks[i].checked = false;
            if (checks.length && !checks.some(c => c.checked)) {
                checks[0].checked = true;
            }
        }
    }

    function refresh() {
        let total = 0;
        const labels = [];
        checks.forEach((c) => {
            if (!c.checked) return;
            total += Number(c.dataset.amount || 0);
            const row = c.closest('label');
            const label = row?.querySelector('.text-sm.font-bold')?.textContent?.trim();
            if (label) labels.push(label);
        });
        if (totalEl) totalEl.textContent = formatRp(total);
        if (summaryEl) {
            summaryEl.textContent = labels.length
                ? (labels.length === 1 ? labels[0] : labels[0] + ' – ' + labels[labels.length - 1] + ' (' + labels.length + ' bln)')
                : '';
        }
        if (submitText) {
            submitText.textContent = total > 0 ? ('Lanjut bayar ' + formatRp(total)) : 'Lanjut bayar';
        }
        if (submitBtn) submitBtn.disabled = total <= 0;
    }

    checks.forEach((c) => {
        c.addEventListener('change', () => {
            enforcePrefix(Number(c.dataset.index), c.checked);
            refresh();
        });
    });

    form?.addEventListener('submit', (e) => {
        if (!checks.some(c => c.checked)) {
            e.preventDefault();
            alert('Pilih minimal satu periode.');
        }
    });

    refresh();
})();
</script>
@endpush
@endif
