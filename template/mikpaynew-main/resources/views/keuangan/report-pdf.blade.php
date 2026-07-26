<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan {{ $reportYear }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }
        .kop { border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 12px; }
        .kop .kop-image { width: 100%; max-height: 110px; object-fit: contain; margin-bottom: 8px; }
        .kop h1 { margin: 0; font-size: 18px; }
        .kop .meta { margin-top: 4px; font-size: 11px; color: #374151; line-height: 1.45; }
        .title { font-size: 14px; font-weight: 700; margin: 8px 0 4px; }
        .subtitle { font-size: 11px; color: #4b5563; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 7px 8px; }
        th { background: #f3f4f6; text-align: left; font-size: 11px; }
        td.right { text-align: right; }
        .totals { margin-top: 12px; font-size: 12px; line-height: 1.55; }
        .totals strong { font-size: 13px; }
    </style>
</head>
<body>
    <div class="kop">
        @if(!empty($letterheadDataUri))
            <img class="kop-image" src="{{ $letterheadDataUri }}" alt="Kop Surat">
        @else
            <h1>{{ $business->business_name ?: 'MIKPAY' }}</h1>
            <div class="meta">
                @if(!empty($business->business_address)){{ $business->business_address }}<br>@endif
                @if(!empty($business->business_phone))Telp: {{ $business->business_phone }} @endif
                @if(!empty($business->business_email)) | Email: {{ $business->business_email }} @endif
            </div>
        @endif
    </div>

    @php
        $mode = $reportTab ?? 'monthly';
    @endphp
    <div class="title">Laporan Keuangan {{ $mode === 'yearly' ? 'Tahunan' : 'Bulanan' }}</div>
    <div class="subtitle">
        Tahun: {{ $reportYear }}@if($mode === 'monthly') | Bulan: {{ $months[(int)($reportMonth ?? date('n'))] ?? '-' }} @endif<br>
        Dicetak: {{ $printedAt->format('d M Y H:i:s') }}
    </div>

    @if($mode === 'monthly')
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Komponen</th>
                <th style="width: 60%;">Nilai</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Pendapatan PPP</td>
                <td class="right">Rp {{ number_format((int)($monthlySelected->ppp_income ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Kas Masuk Non-PPP</td>
                <td class="right">Rp {{ number_format((int)($monthlySelected->cash_in_non_ppp ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Pemasukan</td>
                <td class="right">Rp {{ number_format((int)($monthlySelected->total_in ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Kas Keluar</td>
                <td class="right">Rp {{ number_format((int)($monthlySelected->cash_out ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Saldo Bulan Ini</strong></td>
                <td class="right"><strong>Rp {{ number_format((int)($monthlySelected->net ?? 0), 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top:10px;font-weight:700;">Tabel Kas Bulanan</div>
    <table>
        <thead>
            <tr>
                <th style="width:8%;">No</th>
                <th style="width:18%;">Tanggal</th>
                <th style="width:32%;">Kategori</th>
                <th style="width:14%;">Masuk</th>
                <th style="width:14%;">Keluar</th>
                <th style="width:14%;">Total</th>
            </tr>
        </thead>
        <tbody>
        @forelse(($monthlyCashflows ?? []) as $idx => $item)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d M Y') : '-' }}</td>
                <td>{{ $item->kategori ?: '-' }}</td>
                <td class="right">@if(($item->tipe ?? '') === 'masuk')Rp {{ number_format((int)$item->nominal, 0, ',', '.') }}@else - @endif</td>
                <td class="right">@if(($item->tipe ?? '') === 'keluar')Rp {{ number_format((int)$item->nominal, 0, ',', '.') }}@else - @endif</td>
                @php $rowTotal = (($item->tipe ?? '') === 'masuk') ? (int)$item->nominal : (0 - (int)$item->nominal); @endphp
                <td class="right">Rp {{ number_format($rowTotal, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#6b7280;">Tidak ada transaksi kas.</td></tr>
        @endforelse
        </tbody>
    </table>
    @else
    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Bulan</th>
                <th style="width: 16%;">Pendapatan PPP</th>
                <th style="width: 16%;">Kas Masuk Non-PPP</th>
                <th style="width: 16%;">Total Pemasukan</th>
                <th style="width: 14%;">Kas Keluar</th>
                <th style="width: 18%;">Saldo Bulan Ini</th>
            </tr>
        </thead>
        <tbody>
            @forelse($monthly as $row)
            <tr>
                <td>{{ $months[(int)$row->month] ?? $row->month }}</td>
                <td class="right">Rp {{ number_format((int)$row->ppp_income, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((int)$row->cash_in_non_ppp, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((int)$row->total_in, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((int)$row->cash_out, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((int)$row->net, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;color:#6b7280;">Belum ada data transaksi pada tahun ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <div class="totals">
        @if($mode === 'yearly')
            Pendapatan PPP: <strong>Rp {{ number_format((int)($totalPppIncome ?? 0), 0, ',', '.') }}</strong><br>
            Kas Masuk Non-PPP: <strong>Rp {{ number_format((int)($totalCashInNonPpp ?? 0), 0, ',', '.') }}</strong><br>
            Total Pemasukan: <strong>Rp {{ number_format((int)($grandTotalIn ?? 0), 0, ',', '.') }}</strong><br>
            Total Pengeluaran: <strong>Rp {{ number_format((int)($totalCashOut ?? 0), 0, ',', '.') }}</strong><br>
            Saldo Bersih: <strong>Rp {{ number_format((int)($netBalance ?? 0), 0, ',', '.') }}</strong>
        @else
            Saldo Bulan {{ $months[(int)($reportMonth ?? date('n'))] ?? '-' }}: <strong>Rp {{ number_format((int)($monthlySelected->net ?? 0), 0, ',', '.') }}</strong>
        @endif
    </div>
</body>
</html>
