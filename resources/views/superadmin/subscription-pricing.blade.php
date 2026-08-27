@extends('layouts.superadmin')

@section('title', 'Paket Langganan — MikPay')
@section('heading', 'Paket Langganan')

@section('content')
<form method="POST" action="{{ route('superadmin.subscription-pricing.update') }}" class="space-y-5">
    @csrf

    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Headline blok harga (landing)</h2>
            <p class="text-xs text-slate-500 mt-1">Teks di atas kartu paket pada beranda.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Eyebrow</label>
                <input type="text" name="pricing_eyebrow" value="{{ old('pricing_eyebrow', $subscriptionHeadlines['pricing_eyebrow'] ?? '') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">WhatsApp verifikasi pembayaran</label>
                <input type="text" name="subscription_verification_whatsapp" value="{{ $subscriptionVerificationWhatsapp }}" placeholder="08xxx / 628xxx" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Judul</label>
                <input type="text" name="pricing_title" value="{{ old('pricing_title', $subscriptionHeadlines['pricing_title'] ?? '') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Subjudul</label>
                <input type="text" name="pricing_subtitle" value="{{ old('pricing_subtitle', $subscriptionHeadlines['pricing_subtitle'] ?? '') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        @foreach (['starter', 'pro', 'enterprise'] as $key)
            @php $pkg = $subscriptionPackages[$key] ?? []; @endphp
            <div class="bg-white border {{ !empty($pkg['featured']) ? 'border-indigo-300' : 'border-slate-200' }} rounded-2xl p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ strtoupper($key) }}</h3>
                    <label class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600">
                        <input type="checkbox" name="packages[{{ $key }}][featured]" value="1" @checked(old("packages.$key.featured", $pkg['featured'] ?? false)) class="rounded border-slate-300">
                        Featured
                    </label>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama</label>
                    <input type="text" name="packages[{{ $key }}][name]" required value="{{ old("packages.$key.name", $pkg['name'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Badge</label>
                        <input type="text" name="packages[{{ $key }}][badge]" value="{{ old("packages.$key.badge", $pkg['badge'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Ribbon</label>
                        <input type="text" name="packages[{{ $key }}][featured_ribbon]" value="{{ old("packages.$key.featured_ribbon", $pkg['featured_ribbon'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Harga (Rp)</label>
                        <input type="number" min="0" name="packages[{{ $key }}][price]" required value="{{ old("packages.$key.price", $pkg['price'] ?? 0) }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Harga coret</label>
                        <input type="number" min="0" name="packages[{{ $key }}][original_price]" value="{{ old("packages.$key.original_price", $pkg['original_price'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Durasi (hari)</label>
                        <input type="number" min="1" name="packages[{{ $key }}][duration]" required value="{{ old("packages.$key.duration", $pkg['duration'] ?? 30) }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Label durasi</label>
                        <input type="text" name="packages[{{ $key }}][duration_label]" value="{{ old("packages.$key.duration_label", $pkg['duration_label'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Subtitle</label>
                    <input type="text" name="packages[{{ $key }}][subtitle]" value="{{ old("packages.$key.subtitle", $pkg['subtitle'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Max pelanggan</label>
                        <input type="number" min="0" name="packages[{{ $key }}][max_customers]" value="{{ old("packages.$key.max_customers", $pkg['max_customers'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm" placeholder="∞">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Max router</label>
                        <input type="number" min="0" name="packages[{{ $key }}][max_routers]" value="{{ old("packages.$key.max_routers", $pkg['max_routers'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm" placeholder="∞">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Tipe CTA</label>
                    <select name="packages[{{ $key }}][cta_type]" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                        @foreach (['register' => 'Daftar / Trial', 'checkout' => 'Checkout bayar', 'whatsapp' => 'WhatsApp sales'] as $val => $label)
                            <option value="{{ $val }}" @selected(old("packages.$key.cta_type", $pkg['cta_type'] ?? 'register') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Label tombol</label>
                    <input type="text" name="packages[{{ $key }}][button_label]" value="{{ old("packages.$key.button_label", $pkg['button_label'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Badge diskon</label>
                    <input type="text" name="packages[{{ $key }}][discount_badge]" value="{{ old("packages.$key.discount_badge", $pkg['discount_badge'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Pesan WA (jika CTA WhatsApp)</label>
                    <input type="text" name="packages[{{ $key }}][wa_message]" value="{{ old("packages.$key.wa_message", $pkg['wa_message'] ?? '') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Fitur (satu baris satu fitur)</label>
                    <textarea name="packages[{{ $key }}][features_text]" rows="7" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-mono">{{ old("packages.$key.features_text", $featuresText[$key] ?? '') }}</textarea>
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-3">
        <h2 class="text-sm font-bold text-slate-900">Urutan metode pembayaran</h2>
        <p class="text-xs text-slate-500">Dipakai di halaman Langganan panel ISP saat unggah bukti.</p>
        <div id="payOrderList" class="space-y-2">
            @foreach ($paymentOrder as $i => $ch)
                @php
                    $labels = [
                        'bank_transfer' => ['Transfer Bank', 'Rekening dari .env PAYMENT_BANK_*'],
                        'ewallet' => ['E-Wallet', 'Nomor dari .env PAYMENT_EWALLET_*'],
                        'qris' => ['QRIS', 'Gambar & merchant dari config payment_gateway'],
                    ];
                @endphp
                <div class="pay-order-item flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" data-channel="{{ $ch }}">
                    <input type="hidden" name="payment_channel_order[]" value="{{ $ch }}">
                    <span class="text-xs font-bold text-slate-400 w-5">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-800">{{ $labels[$ch][0] ?? $ch }}</p>
                        <p class="text-[10px] text-slate-500">{{ $labels[$ch][1] ?? '' }}</p>
                    </div>
                    <div class="flex gap-1">
                        <button type="button" class="pay-up px-2 py-1 rounded-lg border border-slate-200 bg-white text-xs text-slate-600 hover:bg-slate-100"><i class="fa-solid fa-arrow-up"></i></button>
                        <button type="button" class="pay-down px-2 py-1 rounded-lg border border-slate-200 bg-white text-xs text-slate-600 hover:bg-slate-100"><i class="fa-solid fa-arrow-down"></i></button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <button type="submit" class="inline-flex items-center gap-2 text-xs font-bold text-white gradient-bg px-5 py-2.5 rounded-xl shadow-md">
        <i class="fa-solid fa-floppy-disk"></i> Simpan paket
    </button>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const list = document.getElementById('payOrderList');
    if (!list) return;

    function reindex() {
        [...list.querySelectorAll('.pay-order-item')].forEach((el, i) => {
            const n = el.querySelector('span');
            if (n) n.textContent = String(i + 1);
        });
    }

    list.addEventListener('click', (e) => {
        const up = e.target.closest('.pay-up');
        const down = e.target.closest('.pay-down');
        const item = e.target.closest('.pay-order-item');
        if (!item) return;
        if (up && item.previousElementSibling) {
            list.insertBefore(item, item.previousElementSibling);
            reindex();
        }
        if (down && item.nextElementSibling) {
            list.insertBefore(item.nextElementSibling, item);
            reindex();
        }
    });
})();
</script>
@endpush
