@extends('layouts.dashboard')

@section('title', 'Tagihan WiFi')
@section('heading', 'Tagihan WiFi')

@section('content')
<style>
.billing-tabs { display: flex; gap: 5px; margin-bottom: 25px; background: #fff; padding: 8px; border-radius: 16px; box-shadow: 0 0 30px rgba(0,0,0,0.05); }
.billing-tab { flex: 1; padding: 15px 20px; border: none; background: transparent; color: #64748b; font-weight: 600; cursor: pointer; border-radius: 12px; text-decoration: none; text-align: center; }
.billing-tab:hover { background: #f1f5f9; color: #4D44B5; }
.billing-tab.active { background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color: #fff; }
.billing-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px; }
.stat-card { background: #fff; border-radius: 16px; padding: 18px; box-shadow: 0 0 30px rgba(0,0,0,0.05); }
.stat-card .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 10px; }
.stat-card .stat-value { font-size: 24px; font-weight: 700; color: #1e293b; }
.stat-card .stat-label { color: #64748b; font-size: 12px; }
.stat-card.overdue .stat-icon { background: #fee2e2; color: #dc2626; }
.stat-card.due-today .stat-icon { background: #fef3c7; color: #f59e0b; }
.stat-card.due-soon .stat-icon { background: #dbeafe; color: #3b82f6; }
.stat-card.paid .stat-icon { background: #dcfce7; color: #16a34a; }
.stat-card.not-set .stat-icon { background: #f1f5f9; color: #64748b; }
.stat-card.arrears-past-stat .stat-icon { background: #ffedd5; color: #c2410c; }
.status-badge.arrears-past { background: #ffedd5; color: #c2410c; max-width: 160px; white-space: normal; line-height: 1.25; text-align: left; }
.status-badge.arrears-current { background: #fef3c7; color: #b45309; }
.status-badge.arrears-ok { background: #ecfdf5; color: #047857; font-size: 11px; }
.tunggakan-hint { font-size: 11px; color: #64748b; margin: -12px 0 18px; padding: 0 4px; }
.billing-table-container { background: #fff; border-radius: 16px; box-shadow: 0 0 30px rgba(0,0,0,0.05); overflow: hidden; }
.billing-table-header { background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color: #fff; padding: 18px 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.billing-table-header-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-left: auto; }
.billing-table-header .btn-header { padding: 10px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: transform 0.15s ease, box-shadow 0.15s ease; }
.billing-table-header .btn-header-excel { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #312e81; box-shadow: 0 4px 14px rgba(15,23,42,0.12); }
.billing-table-header .btn-header-excel:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(15,23,42,0.18); color: #1e1b4b; }
.billing-table-header .btn-header-add { background: #fff; color: #4D44B5; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.billing-table-header .btn-header-add:hover { background: #f8fafc; color: #312e81; }
.billing-table-header h3 { margin: 0; font-size: 18px; font-weight: 600; }
/* Modal Template Excel */
.billing-import-modal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); z-index: 10000; align-items: center; justify-content: center; padding: 20px; }
.billing-import-modal.show { display: flex; }
.billing-import-modal-inner { background: #fff; border-radius: 18px; max-width: 520px; width: 100%; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); }
.billing-import-modal-header { background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color: #fff; padding: 18px 22px; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.billing-import-modal-header h4 { margin: 0; font-size: 17px; font-weight: 700; line-height: 1.35; }
.billing-import-modal-header p { margin: 6px 0 0; font-size: 12px; opacity: 0.92; font-weight: 500; line-height: 1.45; }
.billing-import-modal-close { width: 40px; height: 40px; border: none; border-radius: 12px; background: rgba(255,255,255,0.15); color: #fff; font-size: 22px; line-height: 1; cursor: pointer; flex-shrink: 0; }
.billing-import-modal-close:hover { background: rgba(255,255,255,0.28); }
.billing-import-modal-body { padding: 22px; overflow-y: auto; -webkit-overflow-scrolling: touch; }
.billing-import-lead { font-size: 13px; color: #475569; line-height: 1.55; margin: 0 0 18px; padding: 12px 14px; background: #f1f5f9; border-radius: 12px; border-left: 4px solid #6366f1; }
.billing-import-section { margin-bottom: 18px; padding: 16px; background: #fafafa; border: 1px solid #e2e8f0; border-radius: 14px; }
.billing-import-section h5 { margin: 0 0 8px; font-size: 14px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.billing-import-section p { margin: 0 0 12px; font-size: 12px; color: #64748b; line-height: 1.5; }
.billing-import-section .btn-download { display: inline-flex; align-items: center; gap: 8px; padding: 11px 18px; border-radius: 11px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #fff; font-weight: 700; font-size: 13px; text-decoration: none; box-shadow: 0 4px 14px rgba(16,185,129,0.35); }
.billing-import-section .btn-download:hover { filter: brightness(1.05); color: #fff; }
.billing-import-form label.file-label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 8px; }
.billing-import-form input[type="file"] { width: 100%; font-size: 13px; padding: 10px 12px; border: 2px dashed #cbd5e1; border-radius: 12px; background: #fff; cursor: pointer; box-sizing: border-box; }
.billing-import-form .btn-upload { margin-top: 12px; width: 100%; padding: 12px; border: none; border-radius: 12px; background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color: #fff; font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
.billing-import-form .btn-upload:hover { filter: brightness(1.06); }
.billing-import-footer-note { margin-top: 8px; text-align: center; font-size: 12px; color: #64748b; }
.billing-import-footer-note a { color: #4D44B5; font-weight: 600; text-decoration: none; }
.billing-import-footer-note a:hover { text-decoration: underline; }
.billing-table { width: 100%; border-collapse: collapse; }
.billing-table th { background: #f8fafc; padding: 12px 15px; text-align: left; font-weight: 600; color: #64748b; font-size: 13px; }
.billing-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; }
.billing-table tr:hover { background: #fafafa; }
.status-badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-badge.overdue { background: #fee2e2; color: #dc2626; }
.status-badge.due-today { background: #fef3c7; color: #f59e0b; }
.status-badge.due-soon { background: #dbeafe; color: #3b82f6; }
.status-badge.waiting { background: #e0e7ff; color: #6366f1; }
.status-badge.paid { background: #dcfce7; color: #16a34a; }
.status-badge.partial { background: #fef3c7; color: #b45309; }
.status-badge.not-set { background: #f1f5f9; color: #64748b; }
.btn-action { width: 34px; height: 34px; border: none; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; margin-right: 5px; text-decoration: none; font-size: 14px; }
.btn-edit { background: #e0e7ff; color: #4D44B5; }
.btn-edit:hover { background: #4D44B5; color: #fff; }
.btn-payment { background: #dcfce7; color: #16a34a; }
.btn-payment:hover { background: #22c55e; color: #fff; }
.btn-delete { background: #fee2e2; color: #dc2626; }
.btn-delete:hover { background: #dc2626; color: #fff; }
/* Modal Catat Pembayaran (seperti template) */
.payment-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 20px; }
.payment-modal.show { display: flex; }
.payment-modal-content { background: #fff; border-radius: 16px; max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
.payment-modal-header { background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color: #fff; padding: 20px 25px; border-radius: 16px 16px 0 0; }
.payment-modal-header h4 { margin: 0; font-size: 18px; }
.payment-modal-body { padding: 25px; }
.payment-modal .form-group { margin-bottom: 20px; }
.payment-modal .form-group label { display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
.payment-modal .form-group input, .payment-modal .form-group select { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; box-sizing: border-box; }
.payment-modal .form-group input:focus, .payment-modal .form-group select:focus { border-color: #4D44B5; outline: none; }
.payment-modal-footer { padding: 15px 25px; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; justify-content: flex-end; }
.payment-modal .btn-primary { padding: 12px 24px; border: none; background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color: #fff; border-radius: 10px; cursor: pointer; font-weight: 600; }
.payment-modal .btn-secondary { padding: 12px 24px; border: 2px solid #e2e8f0; background: #fff; color: #64748b; border-radius: 10px; cursor: pointer; font-weight: 600; }

/* Modal Atur Billing Pelanggan */
.customer-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 20px; }
.customer-modal.show { display: flex; }
.customer-modal-content { background: #fff; border-radius: 16px; max-width: 620px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
.customer-modal-header { background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color: #fff; padding: 20px 25px; border-radius: 16px 16px 0 0; }
.customer-modal-header h4 { margin: 0; font-size: 18px; }
.customer-modal-body { padding: 25px; }
.customer-modal .form-group { margin-bottom: 16px; }
.customer-modal .form-group label { display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
.customer-modal .form-group input,
.customer-modal .form-group textarea { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; box-sizing: border-box; }
.customer-modal .form-group textarea { min-height: 72px; resize: vertical; }
.customer-modal .form-group input:focus,
.customer-modal .form-group textarea:focus { border-color: #4D44B5; outline: none; }
.customer-modal .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 520px) { .customer-modal .form-row-2 { grid-template-columns: 1fr; } }
.customer-modal-footer { padding: 15px 25px; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; justify-content: flex-end; }
.customer-modal .btn-primary { padding: 12px 24px; border: none; background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color: #fff; border-radius: 10px; cursor: pointer; font-weight: 600; }
.customer-modal .btn-secondary { padding: 12px 24px; border: 2px solid #e2e8f0; background: #fff; color: #64748b; border-radius: 10px; cursor: pointer; font-weight: 600; }
</style>

<div class="billing-tabs">
    <a href="{{ route('ppp.billing', ['view' => 'customers']) }}" class="billing-tab {{ ($view ?? '') === 'customers' ? 'active' : '' }}"><i class="fa fa-users"></i> Pelanggan</a>
    <a href="{{ route('ppp.billing', ['view' => 'report']) }}" class="billing-tab {{ ($view ?? '') === 'report' ? 'active' : '' }}"><i class="fa fa-bar-chart"></i> Laporan</a>
    <a href="{{ route('ppp.billing', ['view' => 'invoices']) }}" class="billing-tab {{ ($view ?? '') === 'invoices' ? 'active' : '' }}"><i class="fa fa-file-text"></i> Struk</a>
</div>

@if(($view ?? 'customers') === 'customers')
<!-- Tab Pelanggan -->
<div class="billing-stats">
    <div class="stat-card overdue"><div class="stat-icon"><i class="fa fa-exclamation-circle"></i></div><div class="stat-value">{{ $countOverdue ?? 0 }}</div><div class="stat-label">Terlambat</div></div>
    <div class="stat-card due-today"><div class="stat-icon"><i class="fa fa-clock-o"></i></div><div class="stat-value">{{ $countDueToday ?? 0 }}</div><div class="stat-label">Jatuh Tempo Hari Ini</div></div>
    <div class="stat-card due-soon"><div class="stat-icon"><i class="fa fa-bell"></i></div><div class="stat-value">{{ $countDueSoon ?? 0 }}</div><div class="stat-label">Segera Jatuh Tempo</div></div>
    <div class="stat-card paid"><div class="stat-icon"><i class="fa fa-check-circle"></i></div><div class="stat-value">{{ $countPaid ?? 0 }}</div><div class="stat-label">Sudah Lunas (bln ini)</div></div>
    <div class="stat-card arrears-past-stat"><div class="stat-icon"><i class="fa fa-history"></i></div><div class="stat-value">{{ $countArrearsPast ?? 0 }}</div><div class="stat-label">Tunggakan bulan lalu+</div></div>
    <div class="stat-card not-set"><div class="stat-icon"><i class="fa fa-question-circle"></i></div><div class="stat-value">{{ $countNotSet ?? 0 }}</div><div class="stat-label">Belum Diatur</div></div>
</div>
<p class="tunggakan-hint"><i class="fa fa-info-circle"></i> <strong>Tunggakan</strong> dihitung per bulan (YYYY-MM) sejak <strong>tanggal mulai pencatatan tagihan</strong> (bukan &quot;mulai berlangganan&quot; pelanggan). Atur di <strong><a href="{{ route('sessions') }}">Admin Settings</a></strong> → kartu <em>Admin Account</em> → <em>Pengaturan tagihan (tunggakan)</em>, atau <code>BILLING_TRACKING_START_DATE</code> di <code>.env</code>. @if(empty($billingTrackingConfigured))<strong style="color:#c2410c;">Belum di-set: saat ini hanya bulan berjalan yang dihitung.</strong>@endif</p>
@if($errors->any() && !$errors->has('file'))
<div class="alert alert-danger" style="margin-bottom:16px;border-radius:12px;"><i class="fa fa-exclamation-circle"></i> {{ $errors->first() }}</div>
@endif

{{-- Modal: Template Excel (import / export) --}}
<div id="billingImportModal" class="billing-import-modal" aria-hidden="true" role="dialog" aria-labelledby="billingImportModalTitle">
    <div class="billing-import-modal-inner">
        <div class="billing-import-modal-header">
            <div>
                <h4 id="billingImportModalTitle"><i class="fa fa-file-excel-o"></i> Template Excel — Import &amp; Export</h4>
                <p>Unduh daftar pelanggan PPP berstatus <strong>Belum Diatur</strong>, lengkapi di Excel, lalu unggah kembali. Format file: <strong>.xlsx</strong>.</p>
            </div>
            <button type="button" class="billing-import-modal-close" id="billingImportModalClose" aria-label="Tutup">&times;</button>
        </div>
        <div class="billing-import-modal-body">
            <p class="billing-import-lead"><i class="fa fa-info-circle"></i> Kolom <strong>Username PPP</strong> dan <strong>Tanggal jatuh tempo (1–31)</strong> wajib diisi saat mengunggah. Username harus sama dengan PPP Secret di router.</p>

            <div class="billing-import-section">
                <h5><i class="fa fa-download" style="color:#059669;"></i> Langkah 1 — Unduh template</h5>
                <p>File berisi lembar <strong>Data Pelanggan</strong> (isi sesuai PPP Secret Anda) dan lembar <strong>Contoh pengisian</strong> dengan 1 baris contoh format. Buka dengan Excel atau LibreOffice Calc.</p>
                <a href="{{ route('ppp.billing.template.export') }}" class="btn-download"><i class="fa fa-file-excel-o"></i> Unduh MIKPAY-template-tagihan-wifi.xlsx</a>
            </div>

            <div class="billing-import-section">
                <h5><i class="fa fa-upload" style="color:#4D44B5;"></i> Langkah 2 — Unggah berkas</h5>
                <p>Setelah mengisi dan menyimpan, pilih file lalu klik Unggah.</p>
                <form action="{{ route('ppp.billing.template.import') }}" method="post" enctype="multipart/form-data" class="billing-import-form">
                    @csrf
                    <label class="file-label" for="billingImportFile">Pilih file .xlsx</label>
                    <input type="file" name="file" id="billingImportFile" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                    @error('file')
                        <small style="color:#dc2626;display:block;margin-top:8px;font-weight:600;">{{ $message }}</small>
                    @enderror
                    <button type="submit" class="btn-upload"><i class="fa fa-cloud-upload"></i> Unggah &amp; simpan ke MIKPAY</button>
                </form>
            </div>

            <p class="billing-import-footer-note">Atau tambah / edit satu per satu: <a href="{{ route('ppp.billing.customers.create') }}">Tambah pelanggan manual</a></p>
        </div>
    </div>
</div>

<div class="billing-table-container">
    <div class="billing-table-header">
        <h3 style="margin:0;"><i class="fa fa-users"></i> Daftar Pelanggan WiFi</h3>
        <div class="billing-table-header-actions">
            <button type="button" class="btn-header btn-header-excel" data-open-billing-import title="Unduh template &amp; unggah Excel">
                <i class="fa fa-file-excel-o"></i> Template Excel
            </button>
            <a href="{{ route('ppp.billing.customers.create') }}" class="btn-header btn-header-add"><i class="fa fa-plus"></i> Tambah Pelanggan</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="billing-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pelanggan</th>
                    <th>No. HP</th>
                    <th>Email</th>
                    <th>Zona</th>
                    <th>Tarif / bulan</th>
                    <th>Mulai</th>
                    <th>Jatuh Tempo</th>
                    <th>Janji Bayar<br><small style="font-weight:500;opacity:0.85;">(periode {{ $billingPeriodLabel ?? '' }})</small></th>
                    <th>Periode</th>
                    <th>Tunggakan</th>
                    <th>Total Tunggakan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers ?? [] as $i => $c)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        @if(!empty($c->online))<span class="text-success" title="Online">●</span> @endif
                        <span class="customer-name">{{ $c->customer_name }}</span>
                        @if(!empty($c->display_name))<br><small style="color:#64748b;">{{ $c->display_name }}</small>@endif
                        @if(!empty($c->profile))<br><small style="color:#94a3b8;" title="Profil MikroTik"><i class="fa fa-sitemap"></i> {{ $c->profile }}</small>@endif
                        @if(!empty($c->address))<br><small style="color:#64748b;max-width:220px;display:inline-block;">{{ \Illuminate\Support\Str::limit($c->address, 48) }}</small>@endif
                    </td>
                    <td>{{ $c->phone ?: '—' }}</td>
                    <td><small>{{ $c->email ? $c->email : '—' }}</small></td>
                    <td><small>{{ $c->zone ?: '—' }}</small></td>
                    <td><small style="font-weight:600;color:#1e293b;">@if(!empty($c->monthly_fee) && (int)$c->monthly_fee > 0)Rp {{ number_format((int)$c->monthly_fee, 0, ',', '.') }}@else<span style="color:#94a3b8;font-weight:400;">Belum diatur</span>@endif</small></td>
                    <td><small>@if($c->started_at){{ \Carbon\Carbon::parse($c->started_at)->format('d M Y') }}@else—@endif</small></td>
                    <td>
                        @if(($c->due_day ?? 0) > 0)
                        <strong>Tgl {{ $c->due_day }}</strong>
                        @if(!empty($c->due_date))<br><small style="color:#64748b;">{{ \Carbon\Carbon::parse($c->due_date)->format('d M Y') }}</small>@endif
                        @else
                        <span style="color:#94a3b8;">Belum diatur</span>
                        @endif
                    </td>
                    <td>
                        @if(!empty($c->promised_pay_date))
                            <span class="status-badge {{ !empty($c->is_promised) ? 'due-soon' : 'not-set' }}">
                                {{ \Carbon\Carbon::parse($c->promised_pay_date)->format('d M Y') }}
                            </span>
                            @if(!empty($c->promised_pay_note))
                                <br><small style="color:#64748b;">{{ \Illuminate\Support\Str::limit($c->promised_pay_note, 26) }}</small>
                            @endif
                        @else
                            <small style="color:#94a3b8;">-</small>
                        @endif
                    </td>
                    <td>
                        @if(!empty($c->current_period))
                        {{ \Carbon\Carbon::parse($c->current_period . '-01')->format('M Y') }}
                        @if($c->days_to_due !== null)
                        @if($c->days_to_due < 0)<br><small class="text-danger">Lewat {{ abs($c->days_to_due) }} hari</small>
                        @elseif($c->days_to_due == 0)<br><small class="text-danger">Hari ini!</small>
                        @else<br><small>{{ $c->days_to_due }} hari lagi</small>@endif
                        @endif
                        @else - @endif
                    </td>
                    <td style="max-width:180px;">
                        <span class="status-badge {{ $c->arrears_badge_class ?? 'arrears-ok' }}" title="{{ $c->arrears_tooltip ?: 'Periode tanpa pembayaran tercatat' }}">{{ $c->arrears_label ?? '—' }}</span>
                    </td>
                    <td>
                        @if((int)($c->outstanding_total ?? 0) > 0)
                            <strong style="color:#b45309;">Rp {{ number_format((int)($c->outstanding_total ?? 0), 0, ',', '.') }}</strong>
                        @else
                            <span style="color:#16a34a;">Rp 0</span>
                        @endif
                    </td>
                    <td><span class="status-badge {{ $c->status_class ?? 'not-set' }}">{{ $c->status_label ?? 'Belum Diatur' }}</span></td>
                    <td>
                        @php
                            $addrLine = !empty($c->address) ? preg_replace('/\s+/', ' ', trim($c->address)) : '';
                        @endphp
                        <button
                            type="button"
                            class="btn-action btn-edit"
                            title="Atur Billing"
                            data-open-customer
                            data-customer="{{ $c->customer_name }}"
                            data-due-day="{{ (int)($c->due_day ?? 10) }}"
                            data-monthly-fee="{{ (int)($c->monthly_fee ?? 0) }}"
                            data-display-name="{{ $c->display_name ?? '' }}"
                            data-phone="{{ $c->phone ?? '' }}"
                            data-notes="{{ $c->notes ?? '' }}"
                            data-address="{{ e($addrLine) }}"
                            data-zone="{{ $c->zone ?? '' }}"
                            data-package-label="{{ $c->package_label ?? '' }}"
                            data-email="{{ $c->email ?? '' }}"
                            data-started-at="{{ $c->started_at ? \Carbon\Carbon::parse($c->started_at)->format('Y-m-d') : '' }}"
                        ><i class="fa fa-pencil"></i></button>
                        <button
                            type="button"
                            class="btn-action"
                            style="background:#ede9fe;color:#6d28d9;"
                            title="Janji bayar (hanya periode {{ $billingPeriod ?? date('Y-m') }})"
                            data-open-period-promise
                            data-customer="{{ $c->customer_name }}"
                            data-promised-date="{{ !empty($c->promised_pay_date) ? \Carbon\Carbon::parse($c->promised_pay_date)->format('Y-m-d') : '' }}"
                            data-promised-note="{{ $c->promised_pay_note ?? '' }}"
                        ><i class="fa fa-calendar-check-o"></i></button>
                        <button type="button" class="btn-action btn-payment" title="Catat Pembayaran" data-open-payment data-customer="{{ $c->customer_name }}" data-period="{{ $c->current_period ?? date('Y-m') }}"><i class="fa fa-money"></i></button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="14" class="text-center text-muted py-4">Belum ada data pelanggan. <a href="{{ route('ppp.billing.customers.create') }}">Tambah pelanggan</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@elseif(($view ?? '') === 'report')
<!-- Tab Laporan -->
@php
    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
@endphp
<div class="billing-table-container" style="margin-bottom:18px;">
    <div class="billing-table-header">
        <h3><i class="fa fa-calendar"></i> Rekap Bulanan — Tahun {{ $reportYear ?? date('Y') }}</h3>
    </div>
    <div style="padding:14px 18px;border-bottom:1px solid #eef2f7;background:#f8fafc;">
        <form method="GET" action="{{ route('ppp.billing') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <input type="hidden" name="view" value="report">
            <label for="reportYear" style="margin:0;font-weight:600;color:#475569;">Tahun</label>
            <select id="reportYear" name="year" class="form-control" style="max-width:140px;">
                @foreach(($availableReportYears ?? [date('Y')]) as $yr)
                    <option value="{{ $yr }}" {{ (int)($reportYear ?? date('Y')) === (int)$yr ? 'selected' : '' }}>{{ $yr }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm" style="background:#4D44B5;color:#fff;border:none;padding:9px 14px;border-radius:10px;">
                <i class="fa fa-filter"></i> Tampilkan
            </button>
            <span style="font-size:12px;color:#64748b;">
                Export resmi dipusatkan di menu <strong>Keuangan &gt; Laporan Keuangan</strong>.
            </span>
            <span style="margin-left:auto;color:#64748b;font-size:12px;">
                Total tahun {{ $reportYear ?? date('Y') }}: <strong style="color:#1e293b;">Rp {{ number_format($reportYearTotal ?? 0, 0, ',', '.') }}</strong>
                ({{ number_format($reportYearTransactions ?? 0, 0, ',', '.') }} transaksi)
            </span>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Total</th>
                    <th>Transaksi</th>
                    <th>Rata-rata</th>
                    <th>Tunai</th>
                    <th>Transfer</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($summaryByMonth ?? []) as $m)
                <tr>
                    <td>{{ $months[(int) $m->month] ?? $m->period }}</td>
                    <td><strong>Rp {{ number_format((int) $m->total_amount, 0, ',', '.') }}</strong></td>
                    <td>{{ number_format((int) $m->total_transactions, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format((int) $m->avg_amount, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format((int) $m->total_cash, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format((int) $m->total_transfer, 0, ',', '.') }}</td>
                    <td>
                        <a href="{{ route('ppp.billing', ['view' => 'report', 'year' => $m->year, 'month' => $m->month]) }}"
                           class="btn-action btn-edit"
                           title="Lihat detail transaksi bulan ini">
                            <i class="fa fa-search"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">Belum ada data pembayaran untuk tahun ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h3><i class="fa fa-bar-chart"></i> Laporan Pembayaran — {{ $months[$reportMonth ?? 1] ?? '' }} {{ $reportYear ?? date('Y') }}</h3>
        <span>Total: Rp {{ number_format($reportTotal ?? 0, 0, ',', '.') }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>#</th><th>Pelanggan</th><th>Jumlah</th><th>Tanggal</th><th>Metode</th></tr></thead>
                <tbody>
                    @forelse($reportPayments ?? [] as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $p->customer_name ?? $p->display_name }}</td>
                        <td>Rp {{ number_format($p->amount ?? 0, 0, ',', '.') }}</td>
                        <td>{{ $p->payment_date?->format('d M Y H:i') }}</td>
                        <td>{{ $p->payment_method ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">Tidak ada pembayaran di periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<!-- Tab Struk / Pembayaran -->
<div class="billing-table-container">
    <div class="billing-table-header">
        <h3><i class="fa fa-file-text"></i> Riwayat Pembayaran</h3>
        <button type="button" class="btn btn-sm" style="background:#fff;color:#4D44B5;" data-open-payment><i class="fa fa-plus"></i> Catat Pembayaran</button>
    </div>
    <div class="table-responsive">
        <table class="billing-table">
            <thead>
                <tr><th>#</th><th>No. Struk</th><th>Pelanggan</th><th>Jumlah</th><th>Sisa</th><th>Periode</th><th>Tanggal</th><th>Metode</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($payments ?? [] as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td style="font-weight:700;color:#4D44B5;">{{ $p->invoice_id ?: ('INV-' . str_pad((string) $p->id, 6, '0', STR_PAD_LEFT)) }}</td>
                    <td>{{ $p->customer_name ?? $p->display_name ?? '-' }}</td>
                    <td>Rp {{ number_format($p->amount ?? 0, 0, ',', '.') }}</td>
                    <td>
                        @if((int)($p->remaining_amount ?? 0) > 0)
                            <span style="color:#b45309;font-weight:600;">Rp {{ number_format((int)($p->remaining_amount ?? 0), 0, ',', '.') }}</span>
                        @else
                            <span style="color:#16a34a;">Rp 0</span>
                        @endif
                    </td>
                    <td>{{ $p->period ?? '-' }}</td>
                    <td>{{ $p->payment_date?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $p->payment_method ?? '-' }}</td>
                    <td>
                        @php
                            $paymentStatus = (string) ($p->status ?? '');
                            $statusClass = $paymentStatus === 'paid' ? 'paid' : ($paymentStatus === 'partial' ? 'partial' : 'not-set');
                            $statusLabel = $paymentStatus === 'paid' ? 'Lunas' : ($paymentStatus === 'partial' ? 'Kurang Bayar' : ($paymentStatus ?: '-'));
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td style="max-width:240px;white-space:normal;color:#475569;">
                        @if(!empty($p->notes))
                            {{ \Illuminate\Support\Str::limit($p->notes, 60) }}
                        @else
                            <span style="color:#94a3b8;">-</span>
                        @endif
                    </td>
                    <td>
                        <a class="btn-action btn-edit" title="Cetak Struk" href="{{ route('ppp.billing.invoice.print', ['payment' => $p->id]) }}" target="_blank" rel="noopener"><i class="fa fa-print"></i></a>
                        <form action="{{ route('ppp.billing.payments.destroy', ['payment' => $p->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus riwayat pembayaran {{ $p->invoice_id ?: ('INV-' . str_pad((string) $p->id, 6, '0', STR_PAD_LEFT)) }}? Data kas otomatis dari pembayaran ini juga akan terhapus.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action btn-delete" title="Hapus Riwayat"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" class="text-center text-muted py-4">Belum ada data pembayaran. <button type="button" class="btn btn-link p-0" data-open-payment>Catat pembayaran</button></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Modal Catat Pembayaran (seperti template: popup + respons JSON) -->
<div class="payment-modal" id="paymentModal" aria-hidden="true">
    <div class="payment-modal-content" onclick="event.stopPropagation()">
        <div class="payment-modal-header">
            <h4><i class="fa fa-plus-circle"></i> Catat Pembayaran</h4>
        </div>
        <form id="paymentModalForm">
            @csrf
            <div class="payment-modal-body">
                <div class="form-group">
                    <label>Pelanggan (Username PPPoE) <span class="text-danger">*</span></label>
                    <select name="customer_name" id="paymentCustomer" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        @foreach($paymentCustomerList ?? [] as $c)
                        <option value="{{ $c->customer_name }}">{{ $c->customer_name }}@if(!empty($c->display_name)) ({{ $c->display_name }})@endif</option>
                        @endforeach
                    </select>
                    <span class="text-danger small" id="paymentErrorCustomer"></span>
                </div>
                <div class="form-group">
                    <label>Periode (YYYY-MM) <span class="text-danger">*</span></label>
                    <input type="text" name="period" id="paymentPeriod" placeholder="2026-03" value="{{ date('Y-m') }}" required pattern="\d{4}-\d{2}" title="Format: 2026-03">
                    <span class="text-danger small" id="paymentErrorPeriod"></span>
                </div>
                <div class="form-group">
                    <label>Tanggal Bayar <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" id="paymentDate" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label>Jumlah (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="paymentAmount" min="1" placeholder="150000" required>
                    <span class="text-danger small" id="paymentErrorAmount"></span>
                </div>
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="payment_method" id="paymentMethod">
                        <option value="Transfer">Transfer</option>
                        <option value="Tunai">Tunai</option>
                        <option value="QRIS">QRIS</option>
                        <option value="E-Wallet">E-Wallet</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <input type="text" name="notes" id="paymentNotes" placeholder="Catatan tambahan...">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:500;">
                        <input type="checkbox" name="catat_ke_kas" value="1" id="paymentCatatKeKas" checked>
                        <span><i class="fa fa-money" style="color:#10b981;"></i> Catat ke Kas Masuk (otomatis)</span>
                    </label>
                    <small style="color:#64748b;display:block;margin-top:4px;margin-left:28px;">Transaksi akan tercatat di Keuangan → Kas Masuk/Keluar</small>
                </div>
            </div>
            <div class="payment-modal-footer">
                <button type="button" class="btn-secondary" id="paymentModalClose">Batal</button>
                <button type="submit" class="btn-primary" id="paymentModalSubmit"><i class="fa fa-save"></i> Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Atur Billing Pelanggan -->
<div class="customer-modal" id="customerModal" aria-hidden="true">
    <div class="customer-modal-content" onclick="event.stopPropagation()">
        <div class="customer-modal-header">
            <h4><i class="fa fa-sliders"></i> Atur Billing Pelanggan</h4>
        </div>
        <form id="customerModalForm">
            @csrf
            <div class="customer-modal-body">
                <div class="form-group">
                    <label>Customer (nama PPP / username) <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" id="customerName" readonly required>
                    <span class="text-danger small" id="customerErrorName"></span>
                </div>
                <div class="form-group">
                    <label>Tanggal Jatuh Tempo (1-31) <span class="text-danger">*</span></label>
                    <input type="number" name="due_day" id="customerDueDay" min="0" max="31" required>
                    <span class="text-danger small" id="customerErrorDueDay"></span>
                </div>
                <div class="form-group">
                    <label>Tarif Bulanan (Rp)</label>
                    <input type="number" name="monthly_fee" id="customerMonthlyFee" min="0" placeholder="150000">
                </div>
                <div class="form-group">
                    <label>Nama Tampilan</label>
                    <input type="text" name="display_name" id="customerDisplayName" placeholder="Nama untuk invoice/WA">
                </div>
                <div class="form-group">
                    <label>No. HP</label>
                    <input type="text" name="phone" id="customerPhone" placeholder="Untuk reminder WhatsApp">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="customerEmail" placeholder="email@contoh.com" autocomplete="off">
                    <span class="text-danger small" id="customerErrorEmail"></span>
                </div>
                <div class="form-row-2">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Zona / wilayah</label>
                        <input type="text" name="zone" id="customerZone" placeholder="RT/RW, cluster, gang...">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Nama paket layanan</label>
                        <input type="text" name="package_label" id="customerPackageLabel" placeholder="mis. 10 Mbps, Premium">
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat singkat</label>
                    <textarea name="address" id="customerAddress" placeholder="Alamat pemasangan / catatan lokasi..."></textarea>
                </div>
                <div class="form-group">
                    <label>Mulai berlangganan</label>
                    <input type="date" name="started_at" id="customerStartedAt">
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <input type="text" name="notes" id="customerNotes" placeholder="Catatan pelanggan...">
                </div>
            </div>
            <div class="customer-modal-footer">
                <button type="button" class="btn-secondary" id="customerModalClose">Batal</button>
                <button type="submit" class="btn-primary" id="customerModalSubmit"><i class="fa fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Janji Bayar (satu periode tagihan saja) -->
<div class="customer-modal" id="periodPromiseModal" aria-hidden="true">
    <div class="customer-modal-content" onclick="event.stopPropagation()">
        <div class="customer-modal-header">
            <h4><i class="fa fa-calendar-check-o"></i> Janji Bayar — {{ $billingPeriodLabel ?? '' }}</h4>
        </div>
        <form id="periodPromiseForm">
            @csrf
            <input type="hidden" name="period" value="{{ $billingPeriod ?? date('Y-m') }}">
            <input type="hidden" name="customer_name" id="promiseCustomerName">
            <input type="hidden" name="clear_promise" id="promiseClearField" value="0">
            <div class="customer-modal-body">
                <p style="margin:0 0 14px;font-size:13px;color:#64748b;line-height:1.5;">Berlaku hanya untuk tagihan periode <strong>{{ $billingPeriod ?? date('Y-m') }}</strong>. Bulan depan otomatis kembali ke jatuh tempo biasa.</p>
                <div class="form-group">
                    <label>Pelanggan</label>
                    <input type="text" id="promiseCustomerLabel" class="form-control" readonly style="background:#f8fafc;">
                </div>
                <div class="form-group">
                    <label>Tanggal janji bayar <span class="text-danger">*</span></label>
                    <input type="date" name="promised_pay_date" id="promisePayDate" class="form-control">
                    <span class="text-danger small" id="promiseErrorDate"></span>
                </div>
                <div class="form-group">
                    <label>Catatan (opsional)</label>
                    <input type="text" name="promised_pay_note" id="promisePayNote" class="form-control" maxlength="255" placeholder="mis. transfer sore">
                </div>
            </div>
            <div class="customer-modal-footer" style="flex-wrap:wrap;gap:8px;">
                <button type="button" class="btn-secondary" id="periodPromiseModalClose">Batal</button>
                <button type="button" class="btn btn-outline-danger" id="promiseDeleteBtn" style="margin-right:auto;"><i class="fa fa-times"></i> Hapus janji</button>
                <button type="submit" class="btn-primary" id="promiseModalSubmit"><i class="fa fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var modal = document.getElementById('paymentModal');
    var form = document.getElementById('paymentModalForm');
    var submitBtn = document.getElementById('paymentModalSubmit');
    var storeUrl = '{{ route("ppp.billing.payments.store") }}';
    var csrf = document.querySelector('#paymentModalForm input[name="_token"]').value;

    function openPaymentModal(customer, period) {
        if (customer) {
            var sel = document.getElementById('paymentCustomer');
            if (sel) sel.value = customer;
        }
        if (period) {
            var inp = document.getElementById('paymentPeriod');
            if (inp) inp.value = period;
        }
        document.getElementById('paymentDate').value = '{{ date("Y-m-d") }}';
        document.getElementById('paymentAmount').value = '';
        document.getElementById('paymentNotes').value = '';
        modal.classList.add('show');
    }

    function closePaymentModal() {
        modal.classList.remove('show');
    }

    document.querySelectorAll('[data-open-payment]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openPaymentModal(this.getAttribute('data-customer') || '', this.getAttribute('data-period') || '{{ date("Y-m") }}');
        });
    });

    document.getElementById('paymentModalClose').addEventListener('click', closePaymentModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closePaymentModal();
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitBtn.disabled = true;
        document.querySelectorAll('#paymentModalForm .text-danger.small').forEach(function(el) { el.textContent = ''; });

        var fd = new FormData(form);
        fd.set('catat_ke_kas', document.getElementById('paymentCatatKeKas').checked ? '1' : '0');
        fetch(storeUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) {
            return r.json().then(function(d) { return { ok: r.ok, status: r.status, data: d }; }).catch(function() { return { ok: false, status: r.status, data: {} }; });
        })
        .then(function(res) {
            if (res.ok && res.data && res.data.success) {
                closePaymentModal();
                alert(res.data.message || 'Pembayaran berhasil dicatat.');
                window.location.reload();
            } else {
                var d = res.data || {};
                if (d.errors) {
                    document.getElementById('paymentErrorCustomer').textContent = (d.errors.customer_name && d.errors.customer_name[0]) || '';
                    document.getElementById('paymentErrorPeriod').textContent = (d.errors.period && d.errors.period[0]) || '';
                    document.getElementById('paymentErrorAmount').textContent = (d.errors.amount && d.errors.amount[0]) || '';
                } else {
                    alert(d.message || 'Gagal menyimpan.');
                }
            }
        })
        .catch(function() {
            alert('Gagal menyimpan. Coba lagi.');
        })
        .finally(function() {
            submitBtn.disabled = false;
        });
    });

    // Modal Atur Billing Pelanggan
    var cModal = document.getElementById('customerModal');
    var cForm = document.getElementById('customerModalForm');
    var cSubmitBtn = document.getElementById('customerModalSubmit');
    var cStoreUrl = '{{ route("ppp.billing.customers.store") }}';

    function openCustomerModal(data) {
        document.querySelectorAll('#customerModalForm .text-danger.small').forEach(function(el) { el.textContent = ''; });
        document.getElementById('customerName').value = data.customer || '';
        document.getElementById('customerDueDay').value = data.dueDay != null ? data.dueDay : 10;
        document.getElementById('customerMonthlyFee').value = data.monthlyFee != null ? data.monthlyFee : 0;
        document.getElementById('customerDisplayName').value = data.displayName || '';
        document.getElementById('customerPhone').value = data.phone || '';
        document.getElementById('customerNotes').value = data.notes || '';
        document.getElementById('customerEmail').value = data.email || '';
        document.getElementById('customerZone').value = data.zone || '';
        document.getElementById('customerPackageLabel').value = data.packageLabel || '';
        document.getElementById('customerAddress').value = data.address || '';
        document.getElementById('customerStartedAt').value = data.startedAt || '';
        cModal.classList.add('show');
    }

    function closeCustomerModal() {
        cModal.classList.remove('show');
    }

    document.querySelectorAll('[data-open-customer]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openCustomerModal({
                customer: this.getAttribute('data-customer') || '',
                dueDay: this.getAttribute('data-due-day') || 10,
                monthlyFee: this.getAttribute('data-monthly-fee') || 0,
                displayName: this.getAttribute('data-display-name') || '',
                phone: this.getAttribute('data-phone') || '',
                notes: this.getAttribute('data-notes') || '',
                email: this.getAttribute('data-email') || '',
                zone: this.getAttribute('data-zone') || '',
                packageLabel: this.getAttribute('data-package-label') || '',
                address: this.getAttribute('data-address') || '',
                startedAt: this.getAttribute('data-started-at') || '',
            });
        });
    });

    var pModal = document.getElementById('periodPromiseModal');
    var pForm = document.getElementById('periodPromiseForm');
    var pSubmit = document.getElementById('promiseModalSubmit');
    var promiseUrl = '{{ route("ppp.billing.period-promise.store") }}';

    function openPeriodPromiseModal(customer, dateVal, noteVal) {
        document.getElementById('promiseErrorDate').textContent = '';
        document.getElementById('promiseClearField').value = '0';
        document.getElementById('promiseCustomerName').value = customer || '';
        document.getElementById('promiseCustomerLabel').value = customer || '';
        document.getElementById('promisePayDate').value = dateVal || '';
        document.getElementById('promisePayNote').value = noteVal || '';
        if (pModal) pModal.classList.add('show');
    }
    function closePeriodPromiseModal() {
        if (pModal) pModal.classList.remove('show');
    }
    document.querySelectorAll('[data-open-period-promise]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openPeriodPromiseModal(
                this.getAttribute('data-customer') || '',
                this.getAttribute('data-promised-date') || '',
                this.getAttribute('data-promised-note') || ''
            );
        });
    });
    var pClose = document.getElementById('periodPromiseModalClose');
    if (pClose) pClose.addEventListener('click', closePeriodPromiseModal);
    if (pModal) pModal.addEventListener('click', function(e) { if (e.target === pModal) closePeriodPromiseModal(); });
    function sendPeriodPromise(fd) {
        pSubmit.disabled = true;
        document.getElementById('promiseErrorDate').textContent = '';
        fetch(promiseUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }).catch(function() { return { ok: false, data: {} }; }); })
        .then(function(res) {
            if (res.ok && res.data && res.data.success) {
                closePeriodPromiseModal();
                alert(res.data.message || 'Tersimpan.');
                window.location.reload();
                return;
            }
            var d = res.data || {};
            if (d.errors && d.errors.promised_pay_date) {
                document.getElementById('promiseErrorDate').textContent = d.errors.promised_pay_date[0] || '';
            }
            alert(d.message || 'Gagal menyimpan.');
        })
        .catch(function() { alert('Gagal menyimpan.'); })
        .finally(function() { pSubmit.disabled = false; document.getElementById('promiseClearField').value = '0'; });
    }
    var pDel = document.getElementById('promiseDeleteBtn');
    if (pDel) pDel.addEventListener('click', function() {
        if (!confirm('Hapus janji bayar untuk periode ini?')) return;
        var fd = new FormData(pForm);
        fd.set('clear_promise', '1');
        sendPeriodPromise(fd);
    });
    if (pForm) pForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(pForm);
        sendPeriodPromise(fd);
    });

    document.getElementById('customerModalClose').addEventListener('click', closeCustomerModal);
    cModal.addEventListener('click', function(e) {
        if (e.target === cModal) closeCustomerModal();
    });

    cForm.addEventListener('submit', function(e) {
        e.preventDefault();
        cSubmitBtn.disabled = true;
        document.querySelectorAll('#customerModalForm .text-danger.small').forEach(function(el) { el.textContent = ''; });

        var fd = new FormData(cForm);
        fetch(cStoreUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) {
            return r.json().then(function(d) { return { ok: r.ok, status: r.status, data: d }; }).catch(function() { return { ok: false, status: r.status, data: {} }; });
        })
        .then(function(res) {
            if (res.ok && res.data && res.data.success) {
                closeCustomerModal();
                alert(res.data.message || 'Data pelanggan berhasil disimpan.');
                window.location.reload();
                return;
            }
            var d = res.data || {};
            if (d.errors) {
                document.getElementById('customerErrorName').textContent = (d.errors.customer_name && d.errors.customer_name[0]) || '';
                document.getElementById('customerErrorDueDay').textContent = (d.errors.due_day && d.errors.due_day[0]) || '';
                var ee = document.getElementById('customerErrorEmail');
                if (ee) ee.textContent = (d.errors.email && d.errors.email[0]) || '';
            } else {
                alert(d.message || 'Gagal menyimpan.');
            }
        })
        .catch(function() {
            alert('Gagal menyimpan. Coba lagi.');
        })
        .finally(function() {
            cSubmitBtn.disabled = false;
        });
    });

    // Modal Template Excel
    var impModal = document.getElementById('billingImportModal');
    var impClose = document.getElementById('billingImportModalClose');
    function openBillingImportModal() {
        if (!impModal) return;
        impModal.classList.add('show');
        impModal.setAttribute('aria-hidden', 'false');
    }
    function closeBillingImportModal() {
        if (!impModal) return;
        impModal.classList.remove('show');
        impModal.setAttribute('aria-hidden', 'true');
    }
    document.querySelectorAll('[data-open-billing-import]').forEach(function(btn) {
        btn.addEventListener('click', openBillingImportModal);
    });
    if (impClose) impClose.addEventListener('click', closeBillingImportModal);
    if (impModal) {
        impModal.addEventListener('click', function(e) {
            if (e.target === impModal) closeBillingImportModal();
        });
    }
    @if($errors->has('file'))
    openBillingImportModal();
    @endif

})();
</script>
@endsection
