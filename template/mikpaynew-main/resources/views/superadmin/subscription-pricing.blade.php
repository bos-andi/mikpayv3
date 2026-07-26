@extends('layouts.superadmin')

@section('title', 'Paket & Langganan')
@section('heading', 'Paket & fitur premium')

@section('content')
<style>
    .sa-sub-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        padding: 22px 24px;
        margin-bottom: 18px;
    }
    .sa-sub-card h2 {
        margin: 0 0 8px;
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
    }
    .sa-sub-card h2 i { color: #4D44B5; margin-right: 8px; }
    .sa-sub-card .lead {
        margin: 0 0 18px;
        font-size: 13px;
        color: #64748b;
        line-height: 1.55;
    }
    .sa-form-label { display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; }
    .sa-form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        box-sizing: border-box;
    }
    .sa-form-control:focus {
        outline: none;
        border-color: rgba(77, 68, 181, 0.45);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }
    .sa-form-group { margin-bottom: 12px; }
    .sa-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 12px;
    }
    @media (max-width: 960px) {
        .sa-grid-2 { grid-template-columns: 1fr; }
    }
    .sa-pkg-box {
        padding: 16px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #fafafa;
    }
    .sa-pkg-box.featured {
        border-color: rgba(249, 115, 22, 0.45);
        background: rgba(249, 115, 22, 0.04);
    }
    .sa-pkg-box h3 {
        margin: 0 0 12px;
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
    }
    .sa-pkg-box h3 code { font-size: 11px; font-weight: 600; color: #64748b; }
    .sa-btn-save {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 22px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        background: linear-gradient(135deg, #4D44B5, #6366f1);
        color: #fff;
    }
    .sa-btn-save:hover { opacity: 0.95; }
    .sa-hint { font-size: 11px; color: #94a3b8; margin-top: 6px; }
    .sa-pay-divider { margin: 22px 0 18px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
    .sa-pay-order-list { list-style: none; margin: 0; padding: 0; }
    .sa-pay-order-list li {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        padding: 10px 12px; margin-bottom: 8px;
        background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;
    }
    .sa-pay-order-list .sa-pay-ch-label { flex: 1; min-width: 140px; font-size: 13px; font-weight: 600; color: #334155; }
    .sa-pay-order-list .sa-pay-ch-label small { display: block; font-weight: 400; color: #64748b; font-size: 11px; margin-top: 4px; }
    .sa-pay-order-btns { display: flex; gap: 6px; }
    .sa-pay-order-btns button {
        padding: 6px 10px; border-radius: 8px; border: 1px solid #e2e8f0;
        background: #fff; color: #475569; cursor: pointer; font-size: 12px;
    }
    .sa-pay-order-btns button:hover { background: #f1f5f9; }
    .sa-pay-order-btns button:disabled { opacity: 0.35; cursor: not-allowed; }
</style>

<div class="sa-sub-card">
    <h2><i class="fa fa-diamond"></i> Paket &amp; fitur premium</h2>
    <p class="lead">
        Pengaturan ini memengaruhi halaman <strong>Harga</strong> di beranda, halaman <strong>Langganan</strong> user, opsi paket saat <strong>pendaftaran</strong>, dan tampilan <strong>pembayaran langganan</strong> (urutan metode transfer).
        Semua paket berbayar memakai <strong>daftar fitur yang sama</strong> (premium); yang berbeda hanya durasi, harga, dan promo tampilan.
    </p>

    <form method="POST" action="{{ route('superadmin.subscription-pricing.update') }}">
        @csrf
        @php
            $m = $subscriptionPackages['monthly'] ?? [];
            $m5 = $subscriptionPackages['monthly5'] ?? [];
            $h = $subscriptionHeadlines;
        @endphp

        <div class="sa-form-group">
            <label class="sa-form-label">Judul blok harga (beranda)</label>
            <input type="text" name="subscription_pricing_title" class="sa-form-control" value="{{ old('subscription_pricing_title', $h['pricing_title'] ?? '') }}">
        </div>
        <div class="sa-form-group">
            <label class="sa-form-label">Subjudul blok harga</label>
            <input type="text" name="subscription_pricing_subtitle" class="sa-form-control" value="{{ old('subscription_pricing_subtitle', $h['pricing_subtitle'] ?? '') }}">
        </div>
        <div class="sa-form-group">
            <label class="sa-form-label">Tagline premium (di atas kartu harga)</label>
            <input type="text" name="subscription_premium_tagline" class="sa-form-control" value="{{ old('subscription_premium_tagline', $h['premium_tagline'] ?? '') }}">
        </div>
        <div class="sa-form-group">
            <label class="sa-form-label"><i class="fa fa-whatsapp" style="color:#25D366;"></i> WhatsApp verifikasi pembayaran (opsional)</label>
            <input type="text" name="subscription_verification_whatsapp" class="sa-form-control" value="{{ old('subscription_verification_whatsapp', $subscriptionVerificationWhatsapp ?? '') }}" placeholder="08xxxxxxxxxx / 628xxxxxxxxxx" autocomplete="off">
            <div class="sa-hint">Ditampilkan sebagai tombol <strong>Kirim WhatsApp</strong> di halaman Langganan user saat pembayaran menunggu verifikasi. Kosongkan untuk memakai <code>MIKPAY_SUBSCRIPTION_VERIFY_WHATSAPP</code> di <code>.env</code> (jika diisi).</div>
        </div>
        <div class="sa-form-group">
            <label class="sa-form-label">Daftar fitur premium (satu baris satu fitur)</label>
            <textarea name="subscription_premium_features_text" class="sa-form-control" rows="14" style="font-family: ui-monospace, monospace; font-size: 12px;">{{ old('subscription_premium_features_text', $subscriptionFeaturesText) }}</textarea>
            <div class="sa-hint">Kosongkan headline/tagline di atas untuk memakai bawaan sistem.</div>
        </div>

        <div class="sa-grid-2">
            <div class="sa-pkg-box">
                <h3>Paket 1 Bulan <code>monthly</code></h3>
                <div class="sa-form-group">
                    <label class="sa-form-label">Nama</label>
                    <input type="text" name="monthly_name" class="sa-form-control" value="{{ old('monthly_name', $m['name'] ?? '') }}" required>
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Harga (Rp)</label>
                    <input type="number" name="monthly_price" class="sa-form-control" min="0" value="{{ old('monthly_price', $m['price'] ?? 0) }}" required>
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Durasi (hari)</label>
                    <input type="number" name="monthly_duration" class="sa-form-control" min="1" value="{{ old('monthly_duration', $m['duration'] ?? 30) }}" required>
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Teks durasi (mis. per bulan)</label>
                    <input type="text" name="monthly_duration_label" class="sa-form-control" value="{{ old('monthly_duration_label', $m['duration_label'] ?? '') }}" placeholder="per bulan">
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Label tombol</label>
                    <input type="text" name="monthly_button_label" class="sa-form-control" value="{{ old('monthly_button_label', $m['button_label'] ?? 'Pilih Paket') }}">
                </div>
            </div>
            <div class="sa-pkg-box featured">
                <h3>Paket 5 Bulan <code>monthly5</code></h3>
                <div class="sa-form-group">
                    <label class="sa-form-label">Nama</label>
                    <input type="text" name="monthly5_name" class="sa-form-control" value="{{ old('monthly5_name', $m5['name'] ?? '') }}" required>
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Harga (Rp)</label>
                    <input type="number" name="monthly5_price" class="sa-form-control" min="0" value="{{ old('monthly5_price', $m5['price'] ?? 0) }}" required>
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Durasi (hari)</label>
                    <input type="number" name="monthly5_duration" class="sa-form-control" min="1" value="{{ old('monthly5_duration', $m5['duration'] ?? 150) }}" required>
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Harga coret (sebelum diskon)</label>
                    <input type="number" name="monthly5_original_price" class="sa-form-control" min="0" value="{{ old('monthly5_original_price', $m5['original_price'] ?? '') }}" placeholder="125000">
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Subjudul (mis. hemat %)</label>
                    <input type="text" name="monthly5_subtitle" class="sa-form-control" value="{{ old('monthly5_subtitle', $m5['subtitle'] ?? '') }}" placeholder="5 bulan (hemat 20%)">
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Badge diskon</label>
                    <input type="text" name="monthly5_discount_badge" class="sa-form-control" value="{{ old('monthly5_discount_badge', $m5['discount_badge'] ?? '') }}" placeholder="DISKON 20%">
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Teks pita (featured)</label>
                    <input type="text" name="monthly5_featured_ribbon" class="sa-form-control" value="{{ old('monthly5_featured_ribbon', $m5['featured_ribbon'] ?? 'POPULER') }}">
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Baris bawah fitur (highlight)</label>
                    <input type="text" name="monthly5_highlight_footer" class="sa-form-control" value="{{ old('monthly5_highlight_footer', $m5['highlight_footer'] ?? '') }}">
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label">Label tombol</label>
                    <input type="text" name="monthly5_button_label" class="sa-form-control" value="{{ old('monthly5_button_label', $m5['button_label'] ?? '') }}">
                </div>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#334155;">
                    <input type="checkbox" name="monthly5_featured" value="1" {{ old('monthly5_featured', !empty($m5['featured']) ? '1' : '0') === '1' ? 'checked' : '' }}>
                    Tampilkan sebagai kartu &amp; pita populer
                </label>
            </div>
        </div>

        @php
            $paymentChannelLabels = [
                'bank_transfer' => 'Transfer Bank',
                'ewallet' => 'E-Wallet',
                'qris' => 'QRIS',
            ];
            $paymentOrder = \App\Http\Controllers\SuperAdminSubscriptionController::normalizePaymentChannelOrder(
                old('payment_channel_order', $paymentOrder)
            );
        @endphp
        <div class="sa-pay-divider">
            <label class="sa-form-label"><i class="fa fa-sort-amount-asc"></i> Urutan metode pembayaran (halaman langganan user)</label>
            <p class="sa-hint" style="margin-bottom: 12px; line-height: 1.5;">
                Urutan dipakai di <strong>Pembayaran langganan</strong> (tujuan transfer &amp; dropdown channel). Metode yang tidak diisi di <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">.env</code> tidak ditampilkan ke user; <strong>QRIS</strong> tetap tersedia secara default.
            </p>
            <ul id="sa-payment-channel-order-list" class="sa-pay-order-list">
                @foreach($paymentOrder as $idx => $payKey)
                    <li data-key="{{ $payKey }}">
                        <input type="hidden" name="payment_channel_order[]" value="{{ $payKey }}">
                        <span class="sa-pay-ch-label">
                            <i class="fa fa-bars" style="opacity:0.45;margin-right:6px;"></i>{{ $paymentChannelLabels[$payKey] }}
                            <small>
                                @if($payKey === 'bank_transfer') Variabel: PAYMENT_BANK_* @endif
                                @if($payKey === 'ewallet') Variabel: PAYMENT_EWALLET_* @endif
                                @if($payKey === 'qris') PAYMENT_QRIS_* (opsional) @endif
                            </small>
                        </span>
                        <div class="sa-pay-order-btns">
                            <button type="button" data-move="up" title="Naik" aria-label="Naik" {{ $idx === 0 ? 'disabled' : '' }}><i class="fa fa-arrow-up"></i></button>
                            <button type="button" data-move="down" title="Turun" aria-label="Turun" {{ $idx === count($paymentOrder) - 1 ? 'disabled' : '' }}><i class="fa fa-arrow-down"></i></button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <button type="submit" class="sa-btn-save"><i class="fa fa-save"></i> Simpan semua pengaturan</button>
    </form>
</div>
@push('scripts')
<script>
(function() {
    var list = document.getElementById('sa-payment-channel-order-list');
    if (!list) return;
    function refreshButtons() {
        var items = list.querySelectorAll('li');
        items.forEach(function(li, i) {
            var up = li.querySelector('[data-move="up"]');
            var down = li.querySelector('[data-move="down"]');
            if (up) up.disabled = i === 0;
            if (down) down.disabled = i === items.length - 1;
        });
    }
    list.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-move]');
        if (!btn || btn.disabled) return;
        var li = btn.closest('li');
        if (!li) return;
        if (btn.getAttribute('data-move') === 'up') {
            var prev = li.previousElementSibling;
            if (prev) list.insertBefore(li, prev);
        } else {
            var next = li.nextElementSibling;
            if (next) list.insertBefore(next, li);
        }
        refreshButtons();
    });
})();
</script>
@endpush
@endsection
