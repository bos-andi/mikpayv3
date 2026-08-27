<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan — {{ $periodLabel }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 16px; margin: 0 0 2px; text-align: center; }
        h2 { font-size: 12px; margin: 16px 0 6px; }
        .sub { text-align: center; color: #64748b; font-size: 10px; margin-bottom: 14px; }
        .muted { color: #64748b; font-size: 9px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 5px 8px; }
        th { background: #f8fafc; font-size: 9px; text-transform: uppercase; color: #64748b; text-align: left; }
        .right { text-align: right; }
        .head { background: #e2e8f0; font-weight: bold; }
        .subhead { font-weight: bold; }
        .total { background: #f1f5f9; font-weight: bold; }
        .grand { background: #dbeafe; font-weight: bold; }
        .indent { padding-left: 18px; }
        .indent2 { padding-left: 28px; }
        .pos { color: #059669; }
        .neg { color: #e11d48; }
    </style>
</head>
<body>
    @php $fmt = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'); @endphp

    <h1>NERACA</h1>
    <div class="sub">
        {{ $tenant?->name ?? 'MikPay' }} · {{ $router->displayName() }} · {{ $periodLabel }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Uraian</th>
                <th class="right" style="width:140px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr class="head"><td colspan="2">AKTIVA (ASET)</td></tr>
            <tr class="subhead"><td colspan="2">Aktiva Lancar</td></tr>
            <tr><td class="indent">Kas dan Bank</td><td class="right">{{ $fmt($neraca->kas) }}</td></tr>
            <tr><td class="indent">Piutang</td><td class="right">{{ $fmt($neraca->piutang) }}</td></tr>
            <tr><td class="indent">Persediaan</td><td class="right">{{ $fmt($neraca->persediaan ?? 0) }}</td></tr>
            <tr><td class="indent">Beban Dibayar di Muka</td><td class="right">{{ $fmt($neraca->beban_dibayar_dimuka ?? 0) }}</td></tr>
            <tr class="total"><td class="indent">Total Aktiva Lancar</td><td class="right">{{ $fmt($neraca->total_aktiva_lancar ?? 0) }}</td></tr>

            <tr class="subhead"><td colspan="2">Aktiva Tetap</td></tr>
            <tr><td class="indent">Tanah</td><td class="right">{{ $fmt($neraca->tanah ?? 0) }}</td></tr>
            <tr><td class="indent">Bangunan</td><td class="right">{{ $fmt($neraca->bangunan ?? 0) }}</td></tr>
            <tr><td class="indent2">Akumulasi Penyusutan Bangunan</td><td class="right">({{ $fmt($neraca->akum_penyusutan_bangunan ?? 0) }})</td></tr>
            <tr><td class="indent">Inventaris / Peralatan</td><td class="right">{{ $fmt($neraca->inventaris ?? 0) }}</td></tr>
            <tr><td class="indent2">Akumulasi Penyusutan Inventaris</td><td class="right">({{ $fmt($neraca->akum_penyusutan_inventaris ?? 0) }})</td></tr>
            <tr><td class="indent">Kendaraan</td><td class="right">{{ $fmt($neraca->kendaraan ?? 0) }}</td></tr>
            <tr><td class="indent2">Akumulasi Penyusutan Kendaraan</td><td class="right">({{ $fmt($neraca->akum_penyusutan_kendaraan ?? 0) }})</td></tr>
            <tr><td class="indent">Aset OLT / Server</td><td class="right">{{ $fmt($neraca->aset_olt ?? 0) }}</td></tr>
            <tr><td class="indent2">Akumulasi Penyusutan OLT</td><td class="right">({{ $fmt($neraca->akum_penyusutan_olt ?? 0) }})</td></tr>
            <tr><td class="indent">Aset ODC / ODP</td><td class="right">{{ $fmt($neraca->aset_odc_odp ?? 0) }}</td></tr>
            <tr><td class="indent2">Akumulasi Penyusutan ODC/ODP</td><td class="right">({{ $fmt($neraca->akum_penyusutan_odc ?? 0) }})</td></tr>
            <tr><td class="indent">Aset Kabel / Fiber</td><td class="right">{{ $fmt($neraca->aset_kabel ?? 0) }}</td></tr>
            <tr><td class="indent2">Akumulasi Penyusutan Kabel</td><td class="right">({{ $fmt($neraca->akum_penyusutan_kabel ?? 0) }})</td></tr>
            <tr class="total"><td class="indent">Total Aktiva Tetap</td><td class="right">{{ $fmt($neraca->total_aktiva_tetap ?? 0) }}</td></tr>
            <tr class="grand"><td>TOTAL AKTIVA</td><td class="right">{{ $fmt($neraca->total_aktiva ?? $neraca->total_aset) }}</td></tr>

            <tr class="head"><td colspan="2">KEWAJIBAN (LIABILITAS)</td></tr>
            <tr class="subhead"><td colspan="2">Kewajiban Lancar</td></tr>
            <tr><td class="indent">Utang Usaha</td><td class="right">{{ $fmt($neraca->utang_usaha ?? $neraca->hutang) }}</td></tr>
            <tr><td class="indent">Utang Gaji</td><td class="right">{{ $fmt($neraca->utang_gaji ?? 0) }}</td></tr>
            <tr><td class="indent">Utang Pajak</td><td class="right">{{ $fmt($neraca->utang_pajak ?? 0) }}</td></tr>
            <tr><td class="indent">Beban yang Masih Harus Dibayar</td><td class="right">{{ $fmt($neraca->beban_masih_harus_dibayar ?? 0) }}</td></tr>
            <tr class="total"><td class="indent">Total Kewajiban Lancar</td><td class="right">{{ $fmt($neraca->total_kewajiban_lancar ?? 0) }}</td></tr>
            <tr class="subhead"><td colspan="2">Kewajiban Jangka Panjang</td></tr>
            <tr><td class="indent">Utang Bank Jangka Panjang</td><td class="right">{{ $fmt($neraca->utang_bank_jp ?? 0) }}</td></tr>
            <tr class="total"><td class="indent">Total Kewajiban Jangka Panjang</td><td class="right">{{ $fmt($neraca->total_kewajiban_jp ?? 0) }}</td></tr>
            <tr class="total"><td>Total Kewajiban</td><td class="right">{{ $fmt($neraca->total_kewajiban ?? 0) }}</td></tr>

            <tr class="head"><td colspan="2">EKUITAS</td></tr>
            <tr><td class="indent">Modal</td><td class="right">{{ $fmt($neraca->modal) }}</td></tr>
            <tr><td class="indent">Laba Ditahan</td><td class="right">{{ $fmt($neraca->laba_ditahan) }}</td></tr>
            <tr><td class="indent">Prive / Penarikan Modal</td><td class="right">({{ $fmt($neraca->prive ?? 0) }})</td></tr>
            <tr class="total"><td class="indent">Total Ekuitas</td><td class="right">{{ $fmt($neraca->total_ekuitas ?? 0) }}</td></tr>
            <tr class="grand"><td>TOTAL KEWAJIBAN + EKUITAS</td><td class="right">{{ $fmt($neraca->total_passiva) }}</td></tr>
        </tbody>
    </table>

    @php $lr = $labaRugi; @endphp
    <div style="page-break-before: always;"></div>
    <h1>LAPORAN LABA RUGI</h1>
    <div class="sub">
        {{ $tenant?->name ?? 'MikPay' }} · {{ $router->displayName() }} · {{ $periodLabel }} · Basis kas
    </div>
    <table>
        <thead>
            <tr>
                <th>Uraian</th>
                <th class="right" style="width:140px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr class="head"><td colspan="2">PENDAPATAN USAHA</td></tr>
            @foreach ($lr->pendapatan_usaha as $line)
                <tr>
                    <td class="indent">{{ $line->label }}</td>
                    <td class="right">{{ $fmt($line->amount) }}</td>
                </tr>
            @endforeach
            <tr class="total"><td>Total Pendapatan Usaha</td><td class="right pos">{{ $fmt($lr->total_pendapatan) }}</td></tr>

            <tr class="head"><td colspan="2">BEBAN OPERASIONAL JARINGAN</td></tr>
            @foreach ($lr->beban_jaringan as $line)
                <tr>
                    <td class="indent">{{ $line->label }}</td>
                    <td class="right">{{ $fmt($line->amount) }}</td>
                </tr>
            @endforeach
            <tr class="total"><td class="indent">Total Beban Operasional Jaringan</td><td class="right neg">{{ $fmt($lr->total_beban_jaringan) }}</td></tr>
            <tr class="total"><td>Laba Kotor Usaha</td><td class="right">{{ $fmt($lr->laba_kotor) }}</td></tr>

            <tr class="head"><td colspan="2">BEBAN OPERASIONAL UMUM</td></tr>
            @foreach ($lr->beban_umum as $line)
                <tr>
                    <td class="indent">{{ $line->label }}</td>
                    <td class="right">{{ $fmt($line->amount) }}</td>
                </tr>
            @endforeach
            <tr class="total"><td class="indent">Total Beban Operasional Umum</td><td class="right neg">{{ $fmt($lr->total_beban_umum) }}</td></tr>
            <tr class="total"><td>Total Beban</td><td class="right neg">{{ $fmt($lr->total_beban) }}</td></tr>
            <tr class="grand">
                <td>{{ $lr->laba_bersih >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}</td>
                <td class="right">{{ $fmt($lr->laba_bersih) }}</td>
            </tr>
        </tbody>
    </table>

    <p class="muted">Dicetak {{ now()->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB · MikPay · Semi-akuntansi</p>
</body>
</html>
