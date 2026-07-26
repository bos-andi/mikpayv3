@extends('layouts.superadmin')

@section('title', 'Dashboard Super Admin')
@section('heading', 'Ringkasan')

@section('content')
<style>
    .sa-dash-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    .sa-dash-card {
        background: #fff;
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        border: 1px solid #e2e8f0;
    }
    .sa-dash-card .sa-dash-value {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .sa-dash-card .sa-dash-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 6px;
    }
    .sa-shortcuts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 14px;
    }
    .sa-shortcut {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px 22px;
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #fff;
        border-radius: 14px;
        text-decoration: none;
        box-shadow: 0 8px 28px rgba(15, 23, 42, 0.2);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .sa-shortcut:hover {
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.28);
    }
    .sa-shortcut i {
        font-size: 28px;
        opacity: 0.9;
    }
    .sa-shortcut h3 {
        margin: 0 0 4px;
        font-size: 16px;
        font-weight: 700;
    }
    .sa-shortcut p {
        margin: 0;
        font-size: 12px;
        opacity: 0.85;
        line-height: 1.4;
    }
</style>

<div class="sa-dash-grid">
    <div class="sa-dash-card">
        <div class="sa-dash-label">Menunggu verifikasi</div>
        <div class="sa-dash-value" style="color:#d97706;">{{ $stats['pending'] }}</div>
    </div>
    <div class="sa-dash-card">
        <div class="sa-dash-label">Disetujui</div>
        <div class="sa-dash-value" style="color:#16a34a;">{{ $stats['approved'] }}</div>
    </div>
    <div class="sa-dash-card">
        <div class="sa-dash-label">Ditolak</div>
        <div class="sa-dash-value" style="color:#dc2626;">{{ $stats['rejected'] }}</div>
    </div>
    <div class="sa-dash-card">
        <div class="sa-dash-label">Pengguna (non super admin)</div>
        <div class="sa-dash-value">{{ $tenantUserCount }}</div>
    </div>
</div>

<div class="card" style="margin-bottom:0;">
    <div class="card-header"><h4 style="margin:0;"><i class="fa fa-compass"></i> Menu cepat</h4></div>
    <div class="card-body">
        <div class="sa-shortcuts">
            <a href="{{ route('superadmin.payments') }}" class="sa-shortcut">
                <i class="fa fa-credit-card"></i>
                <div>
                    <h3>Pembayaran langganan</h3>
                    <p>Approve / reject bukti transfer, lihat inbox pembayaran user.</p>
                </div>
            </a>
            <a href="{{ route('superadmin.users') }}" class="sa-shortcut">
                <i class="fa fa-users"></i>
                <div>
                    <h3>Pengguna &amp; trial</h3>
                    <p>Daftar tenant, set trial 5 hari, cek status langganan.</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
