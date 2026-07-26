@extends('layouts.dashboard')

@section('title', 'Pembayaran Langganan')
@section('heading', 'Pembayaran')

@section('content')
<style>
    .co-wrap { padding: 8px 0; max-width: 1100px; }
    .co-pay-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        align-items: start;
    }
    @media (min-width: 992px) {
        .co-pay-grid {
            grid-template-columns: minmax(300px, 1fr) minmax(320px, 1fr);
        }
    }
    .co-pay-grid > .co-panel-light { margin-bottom: 0; }
    .co-summary {
        background: linear-gradient(135deg, rgba(77, 68, 181, 0.95) 0%, rgba(99, 102, 241, 0.95) 100%);
        border-radius: 18px;
        padding: 20px 22px;
        color: #fff;
        margin-bottom: 18px;
    }
    .co-summary h2 { margin: 0 0 6px; font-size: 18px; }
    .co-summary .co-price { font-size: 26px; font-weight: 900; color: #fdba74; margin-top: 8px; }
    .co-panel-light {
        background: #fff;
        color: #0f172a;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: 0 8px 28px rgba(15, 23, 42, 0.08);
        border: 1px solid #e2e8f0;
    }
    .co-panel-light h3 { margin: 0 0 12px; font-size: 15px; color: #334155; }
    .co-channel-row { padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .co-channel-row:last-child { border-bottom: 0; }
    .co-actions-top { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
    .co-actions-top a, .co-actions-top button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 13px;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    .co-btn-back { background: #f1f5f9; color: #475569; }
    .co-btn-back:hover { color: #1e293b; }
    .co-form label { display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px; }
    .co-form .form-control { border-radius: 10px; }
    .co-submit {
        width: 100%;
        margin-top: 16px;
        padding: 14px;
        border-radius: 12px;
        border: none;
        font-weight: 800;
        font-size: 15px;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: #fff;
        cursor: pointer;
    }
    .co-submit:disabled { opacity: 0.55; cursor: not-allowed; }
    .co-hint { font-size: 12px; color: #64748b; margin-top: 6px; }
</style>

<div class="co-wrap">
    <div class="co-actions-top">
        <a href="{{ route('langganan') }}" class="co-btn-back"><i class="fa fa-arrow-left"></i> Kembali pilih paket</a>
        <form method="POST" action="{{ route('langganan.batal-checkout') }}" style="margin:0;" onsubmit="return confirm('Batalkan dan kembali ke daftar paket?');">
            @csrf
            <button type="submit" class="co-btn-back" style="background:#fee2e2;color:#991b1b;"><i class="fa fa-times"></i> Batal</button>
        </form>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" style="border-radius:12px;margin-bottom:14px;">
            <i class="fa fa-exclamation-circle"></i> {!! implode('<br>', $errors->all()) !!}
        </div>
    @endif

    <div class="co-summary">
        <h2><i class="fa fa-shopping-cart" style="margin-right:8px;"></i>{{ $selectedPackage['name'] ?? 'Paket' }}</h2>
        <div style="opacity:0.9;font-size:13px;">
            {{ ($selectedPackage['duration'] ?? 0) >= 365 ? 'Akses penuh periode paket' : ($selectedPackage['duration'] ?? 0) . ' hari akses penuh' }}
        </div>
        <div class="co-price">Rp {{ number_format((int)($selectedPackage['price'] ?? 0), 0, ',', '.') }}</div>
    </div>

    <div class="co-pay-grid">
        <div class="co-panel-light">
            <h3><i class="fa fa-university"></i> Tujuan pembayaran</h3>
            @foreach($paymentChannels as $ch)
                <div class="co-channel-row">
                    <strong>{{ $ch['name'] }}</strong><br>
                    @if(!empty($ch['qr_image']))
                        <span class="text-muted">{{ $ch['merchant_name'] ?? '' }} — NMID: {{ $ch['nmid'] ?? '—' }}@if(!empty($ch['terminal_id'])) · Terminal: {{ $ch['terminal_id'] }}@endif</span>
                        <div style="margin-top:12px;text-align:center;">
                            <img src="{{ asset($ch['qr_image']) }}" alt="QRIS {{ $ch['merchant_name'] ?? '' }}" style="max-width:min(280px,100%);height:auto;border-radius:12px;border:1px solid #e2e8f0;background:#fff;padding:8px;">
                        </div>
                        <div class="co-hint" style="margin-top:10px;">Scan dengan aplikasi bank / e-wallet yang mendukung QRIS, bayar sesuai nominal paket, lalu unggah bukti lewat formulir konfirmasi.</div>
                    @elseif(isset($ch['bank_name']))
                        <span class="text-muted">{{ $ch['bank_name'] }} — {{ $ch['account_number'] }} (a.n. {{ $ch['account_name'] }})</span>
                    @else
                        <span class="text-muted">{{ $ch['wallet_name'] ?? '' }} — {{ $ch['wallet_number'] ?? '' }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="co-panel-light co-form">
            <h3><i class="fa fa-upload"></i> Konfirmasi transfer &amp; bukti</h3>
            <p style="font-size:13px;color:#64748b;margin:0 0 16px;line-height:1.5;">
                Transfer sesuai nominal di atas, lalu pilih channel yang Anda pakai dan unggah bukti (foto/scan/PDF).
            </p>
            <form method="POST" action="{{ route('langganan.request') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="package" value="{{ $selectedPackageKey }}">

                <div class="form-group" style="margin-bottom:14px;">
                    <label for="payment_channel">Channel pembayaran <span class="text-danger">*</span></label>
                    <select class="form-control" id="payment_channel" name="payment_channel" required>
                        <option value="">— Pilih —</option>
                        @foreach($paymentChannels as $channelKey => $channel)
                            <option value="{{ $channelKey }}">{{ $channel['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label for="customer_note">Catatan (opsional)</label>
                    <input type="text" class="form-control" id="customer_note" name="customer_note" placeholder="Nama pengirim / no. referensi">
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label for="proof_file">Bukti transfer <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="proof_file" name="proof_file" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                    <div class="co-hint">JPG, PNG, WebP, atau PDF — maks. 5 MB.</div>
                </div>

                <button type="submit" class="co-submit">
                    <i class="fa fa-paper-plane" style="margin-right:8px;"></i>Kirim ke admin untuk verifikasi
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
