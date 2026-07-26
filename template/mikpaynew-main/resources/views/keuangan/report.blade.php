@extends('layouts.dashboard')

@section('title', 'Laporan Keuangan')
@section('heading', 'Laporan Keuangan')

@section('content')
@php
    $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $monthsLong = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
@endphp
<style>
.finance-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px; margin-bottom:16px; }
.finance-card { background:#fff; border-radius:14px; padding:14px 16px; box-shadow:0 0 24px rgba(0,0,0,.05); }
.finance-card .lbl { color:#64748b; font-size:12px; margin-bottom:4px; }
.finance-card .val { color:#1e293b; font-size:22px; font-weight:700; }
.report-tabs { display:flex; gap:8px; margin-bottom:12px; }
.report-tabs a { padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:600; color:#475569; background:#fff; border:1px solid #e2e8f0; }
.report-tabs a.active { background:#4D44B5; color:#fff; border-color:#4D44B5; }
</style>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h3 style="margin:0;"><i class="fa fa-line-chart"></i> Laporan Keuangan</h3>
    </div>
    <div class="card-body" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <div class="report-tabs">
            <a href="{{ route('keuangan.report', ['tab' => 'monthly', 'year' => $reportYear ?? date('Y'), 'month' => $reportMonth ?? date('n')]) }}" class="{{ ($reportTab ?? 'monthly') === 'monthly' ? 'active' : '' }}">Laporan Bulanan</a>
            <a href="{{ route('keuangan.report', ['tab' => 'yearly', 'year' => $reportYear ?? date('Y')]) }}" class="{{ ($reportTab ?? 'monthly') === 'yearly' ? 'active' : '' }}">Laporan Tahunan</a>
        </div>
    </div>
</div>

@if(($reportTab ?? 'monthly') === 'monthly')
<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h3 style="margin:0;"><i class="fa fa-calendar"></i> Ringkasan Bulanan</h3>
    </div>
    <div class="card-body" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" action="{{ route('keuangan.report') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="hidden" name="tab" value="monthly">
            <label for="reportYearMonth" style="margin:0;font-weight:600;">Tahun</label>
            <select id="reportYearMonth" name="year" class="form-control" style="width:140px;">
                @foreach(($availableYears ?? [date('Y')]) as $year)
                    <option value="{{ $year }}" {{ (int)($reportYear ?? date('Y')) === (int)$year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
            <label for="reportMonth" style="margin:0;font-weight:600;">Bulan</label>
            <select id="reportMonth" name="month" class="form-control" style="width:150px;">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ (int)($reportMonth ?? date('n')) === $m ? 'selected' : '' }}>{{ $monthsLong[$m] }}</option>
                @endfor
            </select>
            <button type="submit" class="btn bg-primary text-white"><i class="fa fa-filter"></i> Tampilkan</button>
            <a href="{{ route('keuangan.report.pdf', ['tab' => 'monthly', 'year' => $reportYear ?? date('Y'), 'month' => $reportMonth ?? date('n')]) }}" class="btn btn-danger"><i class="fa fa-file-pdf-o"></i> Export PDF</a>
        </form>
    </div>
</div>

<div class="finance-stats">
    <div class="finance-card">
        <div class="lbl">Pendapatan PPP</div>
        <div class="val">Rp {{ number_format((int)($monthlySelected->ppp_income ?? 0), 0, ',', '.') }}</div>
    </div>
    <div class="finance-card">
        <div class="lbl">Kas Masuk Non-PPP</div>
        <div class="val">Rp {{ number_format((int)($monthlySelected->cash_in_non_ppp ?? 0), 0, ',', '.') }}</div>
    </div>
    <div class="finance-card">
        <div class="lbl">Total Pemasukan</div>
        <div class="val">Rp {{ number_format((int)($monthlySelected->total_in ?? 0), 0, ',', '.') }}</div>
    </div>
    <div class="finance-card">
        <div class="lbl">Total Pengeluaran</div>
        <div class="val">Rp {{ number_format((int)($monthlySelected->cash_out ?? 0), 0, ',', '.') }}</div>
    </div>
    <div class="finance-card">
        <div class="lbl">Saldo Bersih</div>
        <div class="val" style="color:{{ ((int)($monthlySelected->net ?? 0)) >= 0 ? '#16a34a' : '#dc2626' }};">
            Rp {{ number_format((int)($monthlySelected->net ?? 0), 0, ',', '.') }}
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h3 style="margin:0;font-size:15px;"><i class="fa fa-list-alt"></i> Tabel Kas Bulan {{ $monthsLong[(int)($reportMonth ?? date('n'))] ?? '-' }}</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr><th>No</th><th>Tanggal</th><th>Nama Pelanggan</th><th>Kategori</th><th>Masuk</th><th>Keluar</th><th>Total</th></tr>
                </thead>
                <tbody>
                @forelse(($monthlyCashflows ?? []) as $idx => $c)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $c->tanggal ? \Carbon\Carbon::parse($c->tanggal)->format('d M Y') : '-' }}</td>
                        <td>{{ $c->customer_name_label }}</td>
                        <td>{{ $c->kategori ?: '-' }}</td>
                        <td>
                            @if(($c->tipe ?? '') === 'masuk')
                                Rp {{ number_format((int)$c->nominal, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if(($c->tipe ?? '') === 'keluar')
                                Rp {{ number_format((int)$c->nominal, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @php
                                $rowTotal = (($c->tipe ?? '') === 'masuk') ? (int)$c->nominal : (0 - (int)$c->nominal);
                            @endphp
                            <span style="font-weight:600;color:{{ $rowTotal >= 0 ? '#16a34a' : '#dc2626' }};">
                                Rp {{ number_format($rowTotal, 0, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Belum ada transaksi kas pada bulan ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h3 style="margin:0;"><i class="fa fa-calendar"></i> Ringkasan Tahunan</h3>
    </div>
    <div class="card-body" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" action="{{ route('keuangan.report') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="hidden" name="tab" value="yearly">
            <label for="reportYear" style="margin:0;font-weight:600;">Tahun</label>
            <select id="reportYear" name="year" class="form-control" style="width:140px;">
                @foreach(($availableYears ?? [date('Y')]) as $year)
                    <option value="{{ $year }}" {{ (int)($reportYear ?? date('Y')) === (int)$year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn bg-primary text-white"><i class="fa fa-filter"></i> Tampilkan</button>
            <a href="{{ route('keuangan.report.pdf', ['tab' => 'yearly', 'year' => $reportYear ?? date('Y')]) }}" class="btn btn-danger"><i class="fa fa-file-pdf-o"></i> Export PDF</a>
        </form>
    </div>
</div>

<div class="finance-stats">
    <div class="finance-card">
        <div class="lbl">Pendapatan PPP</div>
        <div class="val">Rp {{ number_format((int)($totalPppIncome ?? 0), 0, ',', '.') }}</div>
    </div>
    <div class="finance-card">
        <div class="lbl">Kas Masuk Non-PPP</div>
        <div class="val">Rp {{ number_format((int)($totalCashInNonPpp ?? 0), 0, ',', '.') }}</div>
    </div>
    <div class="finance-card">
        <div class="lbl">Total Pemasukan</div>
        <div class="val">Rp {{ number_format((int)($grandTotalIn ?? 0), 0, ',', '.') }}</div>
    </div>
    <div class="finance-card">
        <div class="lbl">Total Pengeluaran</div>
        <div class="val">Rp {{ number_format((int)($totalCashOut ?? 0), 0, ',', '.') }}</div>
    </div>
    <div class="finance-card">
        <div class="lbl">Saldo Bersih</div>
        <div class="val" style="color:{{ ((int)($netBalance ?? 0)) >= 0 ? '#16a34a' : '#dc2626' }};">
            Rp {{ number_format((int)($netBalance ?? 0), 0, ',', '.') }}
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 style="margin:0;"><i class="fa fa-calendar"></i> Rekap Bulanan {{ $reportYear ?? date('Y') }}</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th>Pendapatan PPP</th>
                        <th>Kas Masuk Non-PPP</th>
                        <th>Total Pemasukan</th>
                        <th>Kas Keluar</th>
                        <th>Saldo Bulan Ini</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($monthly ?? []) as $row)
                    <tr>
                        <td>{{ $months[(int)$row->month] ?? $row->month }}</td>
                        <td>Rp {{ number_format((int)$row->ppp_income, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((int)$row->cash_in_non_ppp, 0, ',', '.') }}</td>
                        <td><strong>Rp {{ number_format((int)$row->total_in, 0, ',', '.') }}</strong></td>
                        <td>Rp {{ number_format((int)$row->cash_out, 0, ',', '.') }}</td>
                        <td style="font-weight:700;color:{{ ((int)$row->net) >= 0 ? '#16a34a' : '#dc2626' }};">
                            Rp {{ number_format((int)$row->net, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada data transaksi pada tahun ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
