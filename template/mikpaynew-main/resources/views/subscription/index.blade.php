@extends('layouts.dashboard')

@section('title', 'Langganan')
@section('heading', 'Langganan')
@section('main_container_class', 'mc-subscription-full')

@push('styles')
<style>
@media (min-width: 993px) {
    #main .main-container.mc-subscription-full,
    .main-container.mc-subscription-full {
        max-width: none !important;
        width: 100% !important;
        margin-left: 16px !important;
        margin-right: 16px !important;
        padding-left: 24px !important;
        padding-right: 24px !important;
    }
    body.sidebar-closed #main .main-container.mc-subscription-full,
    body.sidebar-closed .main-container.mc-subscription-full {
        max-width: none !important;
    }
}
</style>
@endpush

@section('content')
<style>
    .sub-page-wrap { padding: 8px 0; }
    .sub-status-card {
        background: linear-gradient(135deg, rgba(77, 68, 181, 0.95) 0%, rgba(99, 102, 241, 0.95) 100%);
        border-radius: 20px;
        padding: 22px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }
    .sub-status-card h3 { margin: 0; font-size: 18px; }
    .sub-status-card p { margin: 6px 0 0; color: rgba(255,255,255,0.88); }
    .sub-status-badge {
        padding: 10px 16px;
        border-radius: 999px;
        font-weight: 800;
        font-size: 13px;
        border: 1px solid rgba(255,255,255,0.2);
        white-space: nowrap;
    }
    .sub-status-badge.active { background: rgba(34,197,94,0.18); border-color: rgba(34,197,94,0.45); color: #bbf7d0; }
    .sub-status-badge.warning { background: rgba(245,158,11,0.18); border-color: rgba(245,158,11,0.45); color: #fef3c7; }
    .sub-status-badge.expired { background: rgba(239,68,68,0.18); border-color: rgba(239,68,68,0.45); color: #fecaca; }

    /* Teks di atas background putih (dashboard-panel-body) — kontras jelas */
    .sub-packages-intro {
        margin: 0 0 14px;
        font-size: 14px;
        line-height: 1.6;
        color: #334155;
    }
    .sub-packages-intro strong { color: #0f172a; }
    .sub-packages-intro .fa { color: #4D44B5; margin-right: 6px; }

    .sub-packages-callout {
        margin: 0 0 22px;
        padding: 14px 16px;
        border-radius: 12px;
        border: 1px solid #c7d2fe;
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        font-size: 13px;
        line-height: 1.55;
        color: #312e81;
    }
    .sub-packages-callout strong { color: #1e1b4b; }
    .sub-packages-callout .fa { color: #4f46e5; margin-right: 8px; }

    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 4px;
    }
    .package-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 22px 20px 20px;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        display: flex;
        flex-direction: column;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .package-card:hover {
        box-shadow: 0 8px 32px rgba(15, 23, 42, 0.1);
    }
    .package-card.is-featured {
        border-color: #fdba74;
        box-shadow: 0 4px 24px rgba(249, 115, 22, 0.12), 0 0 0 1px rgba(249, 115, 22, 0.15);
    }
    .package-card .package-ribbon {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.1em;
        color: #c2410c;
        margin-bottom: 8px;
    }
    .package-card h3 {
        margin: 0 0 10px;
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
    }
    .package-price {
        font-size: 28px;
        font-weight: 900;
        margin: 0 0 4px;
        color: #ea580c;
        line-height: 1.2;
    }
    .package-price .price-was {
        text-decoration: line-through;
        font-size: 15px;
        font-weight: 700;
        color: #94a3b8;
        margin-right: 8px;
    }
    .package-price small {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
    }
    .package-duration {
        margin: 6px 0 14px;
        color: #475569;
        font-size: 13px;
        line-height: 1.45;
    }
    .package-duration .package-subtitle {
        display: block;
        margin-top: 6px;
        color: #64748b;
        font-size: 12px;
    }
    .package-features {
        margin: 0 0 4px;
        padding-left: 18px;
        color: #334155;
        font-size: 12px;
        line-height: 1.65;
        flex: 1;
    }
    .package-features li { margin: 0 0 4px; }
    .package-features li::marker { color: #6366f1; }

    .btn-subscribe {
        width: 100%;
        padding: 13px 16px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 14px;
        border: none;
        cursor: pointer;
        margin-top: auto;
    }
    .btn-subscribe.primary {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: #fff;
        box-shadow: 0 4px 14px rgba(234, 88, 12, 0.35);
    }
    .btn-subscribe.primary:hover:not(:disabled) {
        filter: brightness(1.03);
    }
    .btn-subscribe:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        box-shadow: none;
    }

    .sub-pending-alert {
        background: #fff7ed !important;
        border: 1px solid #fdba74 !important;
        color: #9a3412 !important;
        border-radius: 12px;
        padding: 14px 20px !important;
    }
    .sub-pending-alert .fa { color: #c2410c; }
    .sub-pending-alert__row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-start;
        gap: 14px 18px;
    }
    /* Tanpa flex:1 agar tombol tidak terdorong ke ujung kanan layar */
    .sub-pending-alert__text {
        min-width: min(100%, 220px);
        max-width: 100%;
    }
    .sub-btn-wa {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 10px;
        font-weight: 800;
        font-size: 13px;
        text-decoration: none !important;
        color: #fff !important;
        background: #25d366;
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 2px 8px rgba(37, 211, 102, 0.35);
        white-space: nowrap;
    }
    .sub-btn-wa:hover { filter: brightness(1.05); color: #fff !important; }
    .sub-btn-wa .fa { color: #fff !important; }

    .dashboard-panel-modern .sub-table-code {
        background: #f1f5f9;
        color: #0f172a;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 12px;
        border: 1px solid #e2e8f0;
    }
</style>

<div class="sub-page-wrap">
    @if(session('success'))
        <div class="alert alert-success" style="border-radius:12px;">
            <i class="fa fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" style="border-radius:12px;">
            <i class="fa fa-exclamation-circle"></i>
            {!! implode('<br>', $errors->all()) !!}
        </div>
    @endif

    @if($hasPending)
        <div class="alert alert-warning sub-pending-alert">
            <div class="sub-pending-alert__row">
                <div class="sub-pending-alert__text">
                    <i class="fa fa-clock-o"></i> <strong>Menunggu verifikasi.</strong> Pembayaran Anda sedang menunggu konfirmasi admin.
                    @if(empty($subscriptionVerifyWaUrl))
                        <span style="display:block;margin-top:6px;font-size:12px;font-weight:600;opacity:0.9;">Anda juga bisa menghubungi admin lewat kontak resmi jika sudah disediakan.</span>
                    @endif
                </div>
                @if(!empty($subscriptionVerifyWaUrl))
                    <a href="{{ $subscriptionVerifyWaUrl }}" class="sub-btn-wa" target="_blank" rel="noopener noreferrer">
                        <i class="fa fa-whatsapp"></i> Kirim WhatsApp
                    </a>
                @endif
            </div>
        </div>
    @endif

    @php
        $badgeText = 'Expired';
        $badgeClass = 'expired';
        if($isActive){
            $remaining = $remainingDays ?? 0;
            if($remaining <= 7){
                $badgeText = $remaining . ' Hari Lagi';
                $badgeClass = 'warning';
            } else {
                $badgeText = 'Aktif';
                $badgeClass = 'active';
            }
        }
    @endphp

    <div class="sub-status-card">
        <div>
            <h3><i class="fa fa-crown" style="margin-right:8px;"></i>Status Langganan Anda</h3>
            <p>
                Paket: <strong>{{ ucfirst($subscription->package ?? 'trial') }}</strong>
                @if(!empty($subscription->end_date))
                    | Berlaku hingga: <strong>{{ \Carbon\Carbon::parse($subscription->end_date)->format('d M Y') }}</strong>
                @endif
            </p>
        </div>
        <div class="sub-status-badge {{ $badgeClass }}">
            {{ $badgeText }}
        </div>
    </div>

    <div class="dashboard-panel-modern" style="margin-bottom: 18px;">
        <div class="dashboard-panel-header" style="background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);">
            <i class="fa fa-cube"></i> Pilih Paket
        </div>
        <div class="dashboard-panel-body">
            <p class="sub-packages-intro">
                <i class="fa fa-info-circle"></i>
                Pilih paket di bawah ini. Anda akan diarahkan ke <strong>halaman pembayaran</strong> untuk transfer dan mengunggah bukti. Setelah dikirim, admin akan memverifikasi di panel Super Admin.
            </p>
            <div class="sub-packages-callout" role="note">
                <i class="fa fa-star"></i>
                Semua paket berbayar berikut mencakup <strong>suite fitur premium</strong> yang sama — perbedaan utama hanya pada <strong>durasi</strong> dan <strong>harga</strong> paket.
            </div>
            <div class="packages-grid">
                @foreach($packages as $key => $pkg)
                    <div class="package-card {{ !empty($pkg['featured']) ? 'is-featured' : '' }}">
                        @if(!empty($pkg['featured']) && !empty($pkg['featured_ribbon']))
                            <div class="package-ribbon">{{ $pkg['featured_ribbon'] }}</div>
                        @endif
                        <h3>{{ $pkg['name'] }}</h3>
                        <div class="package-price">
                            @if(!empty($pkg['original_price']) && (int) $pkg['original_price'] > (int) $pkg['price'])
                                <span class="price-was">Rp {{ number_format((int) $pkg['original_price'], 0, ',', '.') }}</span>
                            @endif
                            Rp {{ number_format((int)$pkg['price'], 0, ',', '.') }}
                            <small>/ {{ $pkg['duration'] >= 365 ? 'tahun' : 'periode' }}</small>
                        </div>
                        <div class="package-duration">
                            {{ $pkg['duration'] >= 365 ? 'Akses selamanya' : $pkg['duration'] . ' hari akses penuh' }}
                            @if(!empty($pkg['subtitle']))
                                <span class="package-subtitle">{{ $pkg['subtitle'] }}</span>
                            @endif
                        </div>

                        <ul class="package-features">
                            @foreach($pkg['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>

                        <form method="POST" action="{{ route('langganan.pilih-paket') }}" style="margin-top:14px;">
                            @csrf
                            <input type="hidden" name="package" value="{{ $key }}">
                            <button type="submit" class="btn-subscribe primary" {{ $hasPending ? 'disabled' : '' }}>
                                <i class="fa fa-arrow-right" style="margin-right:8px;"></i>
                                {{ $hasPending ? 'Menunggu Konfirmasi' : 'Lanjut ke pembayaran' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="dashboard-panel-modern" style="margin-bottom: 18px;">
        <div class="dashboard-panel-header">
            <i class="fa fa-history"></i> Riwayat & Status Pembayaran
        </div>
        <div class="dashboard-panel-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Paket</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Payment ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingPayments as $p)
                            <tr>
                                <td>{{ optional($p->created_at)->format('d M Y H:i') }}</td>
                                <td>{{ ucfirst($p->package) }}</td>
                                <td>Rp {{ number_format((int)$p->amount, 0, ',', '.') }}</td>
                                <td>{{ $p->status }}</td>
                                <td><code class="sub-table-code">{{ $p->payment_id }}</code></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">Belum ada permintaan pembayaran.</td>
                            </tr>
                        @endforelse

                        @if($paymentHistory->isNotEmpty())
                            <tr>
                                <td colspan="5" style="background: #f8fafc; font-weight: 800; color: #334155;">Riwayat Pembayaran (approved)</td>
                            </tr>
                            @foreach($paymentHistory as $h)
                                <tr>
                                    <td>{{ optional($h->date)->format('d M Y H:i') }}</td>
                                    <td>{{ ucfirst($h->package) }}</td>
                                    <td>Rp {{ number_format((int)$h->amount, 0, ',', '.') }}</td>
                                    <td>paid</td>
                                    <td><code class="sub-table-code">{{ $h->transaction_id ?? '—' }}</code></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
