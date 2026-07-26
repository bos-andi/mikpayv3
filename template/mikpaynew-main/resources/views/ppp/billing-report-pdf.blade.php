<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembayaran Bulanan {{ $reportYear }}</title>
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
        .total-box { margin-top: 10px; font-size: 12px; }
        .total-box strong { font-size: 13px; }
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

    <div class="title">Laporan Rekap Pembayaran Bulanan</div>
    <div class="subtitle">
        Tahun: {{ $reportYear }}<br>
        Dicetak: {{ $printedAt->format('d M Y H:i:s') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 22%;">Bulan</th>
                <th style="width: 18%;">Total</th>
                <th style="width: 12%;">Transaksi</th>
                <th style="width: 16%;">Rata-rata</th>
                <th style="width: 16%;">Tunai</th>
                <th style="width: 16%;">Transfer</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summaryByMonth as $row)
            <tr>
                <td>{{ $months[(int) $row->month] ?? $row->period }}</td>
                <td class="right">Rp {{ number_format((int) $row->total_amount, 0, ',', '.') }}</td>
                <td class="right">{{ number_format((int) $row->total_transactions, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((int) $row->avg_amount, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((int) $row->total_cash, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((int) $row->total_transfer, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;color:#6b7280;">Belum ada data pembayaran pada tahun ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-box">
        Total Tahun {{ $reportYear }}: <strong>Rp {{ number_format((int) $reportYearTotal, 0, ',', '.') }}</strong><br>
        Jumlah Transaksi: <strong>{{ number_format((int) $reportYearTransactions, 0, ',', '.') }}</strong>
    </div>
</body>
</html>
