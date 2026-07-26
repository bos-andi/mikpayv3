@extends('layouts.dashboard')

@section('title', 'Struk ' . ($payment->invoice_id ?? $payment->id))
@section('heading', 'Struk Pembayaran')

@section('content')
@php
    $businessName = $identity ?? 'MIKPAY';
    $invoiceId = $payment->invoice_id ?? ('INV-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT));
    $displayName = $payment->display_name ?: $payment->customer_name;
    $issueDate = $payment->payment_date?->format('d M Y H:i') ?: now()->format('d M Y H:i');
    $issueDateOnly = $payment->payment_date?->format('d/m/y') ?: now()->format('d/m/y');
    $issueTimeOnly = $payment->payment_date?->format('H:i') ?: now()->format('H:i');
    $periodLabel = \Carbon\Carbon::parse(($payment->period ?? date('Y-m')) . '-01')->translatedFormat('M Y');
    $amountInt = (int) ($payment->amount ?? 0);
    $labelWidth = 12;
    $makeLine = function (string $label, string $value) use ($labelWidth): string {
        return str_pad($label, $labelWidth, ' ', STR_PAD_RIGHT) . ' : ' . $value;
    };
    $sep = str_repeat('-', 30);
    $strukText = implode("\n", array_filter([
        strtoupper($businessName),
        'Billing layanan internet / PPPoE',
        '',
        'STRUK PEMBAYARAN',
        $sep,
        $makeLine('TANGGAL', (string) $issueDateOnly),
        $makeLine('WAKTU', (string) $issueTimeOnly),
        $makeLine('NO. STRUK', (string) $invoiceId),
        $makeLine('PELANGGAN', (string) ($payment->customer_name ?? '-')),
        $makeLine('NAMA', (string) $displayName),
        $makeLine('NO HP', (string) ($payment->phone ?: '-')),
        $makeLine('PERIODE', (string) $periodLabel),
        $makeLine('JUMLAH', 'Rp ' . number_format($amountInt, 0, ',', '.')),
        '',
        $makeLine('STATUS', strtoupper($payment->status ?? 'PAID')),
        $makeLine('METODE', (string) ($payment->payment_method ?: '-')),
        '',
        $sep,
        'Dokumen ini bukti pembayaran yang sah.',
    ]));
@endphp

<style id="struk-style">
    /* Portrait receipt */
    .invoice-page{
        width: 100%;
        max-width: 320px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #000;
        box-shadow: none;
        font-family: "Courier New", Courier, monospace;
        font-size: 12px;
        line-height: 1.25;
    }
    .inv-head{
        padding: 18px 18px;
        border-bottom: 1px solid #000;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .inv-brand h1{
        margin: 0;
        font-size: 20px;
        letter-spacing: .4px;
        color: #000;
    }
    .inv-brand p{ margin: 0; color: #000; font-size: 12px; }
    .inv-meta{ text-align: left; }
    .inv-meta .title{ font-size: 16px; font-weight: 700; color:#000; letter-spacing: .6px; }
    .inv-meta .inv-no{ margin-top: 4px; font-size: 12px; color:#000; }
    .inv-meta .date{ margin-top: 2px; font-size: 11px; color:#000; }

    .inv-body{ padding: 16px 18px; }
    .box{ border: 1px solid #000; padding: 12px 12px; }
    .grid-two{ display: grid; grid-template-columns: 1fr; gap: 10px; margin-bottom: 12px; }
    .label{ font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color:#000; margin-bottom: 4px; }
    .value{ font-size: 13px; color:#000; font-weight: 600; }

    .table{ width: 100%; border-collapse: collapse; margin-top: 12px; }
    .table th{
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .4px;
        background: #fff;
        color:#000;
        padding: 10px 8px;
        border: 1px solid #000;
        text-align: left;
    }
    .table td{
        padding: 11px 8px;
        border: 1px solid #000;
        font-size: 12px;
        color:#000;
    }
    .table .num{ text-align: right; }

    .summary{ margin-top: 14px; display: block; }
    .summary-box{ width: 100%; border: 1px solid #000; }
    .summary-row{ display:flex; justify-content:space-between; padding: 10px 12px; font-size: 12px; border-bottom: 1px solid #000; }
    .summary-row:last-child{ border-bottom:none; background:#000; color:#fff; font-weight:700; font-size: 14px; }

    .status{ display:inline-block; padding: 4px 8px; border: 1px solid #000; color:#000; background:#fff; font-size: 10px; font-weight: 700; }
    .notes{ margin-top: 12px; padding: 10px 12px; border: 1px dashed #000; background:#fff; color:#000; font-size: 11px; }
    .inv-foot{ padding: 12px 18px; border-top: 1px solid #000; font-size: 11px; color:#000; display:flex; justify-content:space-between; gap:10px; }

    .actions{ display:flex; gap:10px; justify-content:flex-end; margin-top: 12px; }
    .btnx{ padding: 10px 14px; border: none; font-weight: 600; cursor: pointer; }
    .btnx-primary{ background:#000; color:#fff; }
    .btnx-ghost{ background:#fff; border:1px solid #000; color:#000; }
    /* --- Struk MBanking-style (hitam putih, baris tumpuk) --- */
    .rc-center{text-align:center}
    .rc-brand{font-weight:700; font-size:18px; letter-spacing:1px; margin-top:4px}
    .rc-sub{font-size:11px; margin-top:2px; margin-bottom:10px}
    .rc-sep{white-space:pre; font-weight:700; margin:10px 0; text-align:center}
    .rc-line{margin:4px 0; padding:0; text-align:left}
    .rc-row{display:flex; justify-content:space-between; gap:10px; margin:4px 0; padding:0 2px}
    .rc-label{font-size:10px; text-transform:uppercase; letter-spacing:.2px; font-weight:700}
    .rc-value{font-size:12px; font-weight:700}
    .rc-field{display:flex; align-items:flex-start; gap:8px;}
    .rc-field-left{flex:0 0 98px; display:flex; align-items:flex-start; justify-content:flex-end; gap:4px; }
    .rc-colon{font-weight:700; font-size:12px; line-height:1.1}
    .rc-value-regular{font-weight:700; font-size:12px; text-align:left;}
    .rc-right{text-align:right}
    .rc-section{margin-top:10px; padding-top:6px; border-top:1px dashed #000}
    .rc-total-row{display:flex; justify-content:space-between; gap:10px; padding:8px 2px 0 2px; border-top:2px solid #000; margin-top:10px}
    .rc-notes{margin-top:8px; border:1px dashed #000; padding:8px 8px; font-size:11px}
    .rc-footer{margin-top:12px; text-align:center; font-size:11px}
    .rc-pre{
        white-space: pre;
        margin:0;
        padding:14px 14px 6px 14px;
        text-align:left;
        font-family: "Courier New", Courier, monospace;
        font-size: 12px;
        line-height: 1.25;
    }
    @media print{
        @page { size: auto portrait; margin: 0; }
        .no-print{display:none!important}
        html, body{background:#fff !important; margin:0 !important; padding:0 !important;}
        /* Sembunyikan sidebar/topbar saat print agar struk memenuhi halaman */
        #navbar, #sidenav{display:none !important;}
        #main{max-width: none !important; padding:0 !important; margin:0 !important;}
        .main-container{max-width: none !important; padding:0 !important; margin:0 !important;}
        .invoice-page{
            box-shadow:none !important;
            border:1px solid #000 !important;
            max-width:none !important;
            width:100% !important;
        }
    }
</style>

<div class="invoice-page" id="struk-print-area">
    <pre class="rc-pre">{{ $strukText }}</pre>
    <div class="actions no-print">
        <button class="btnx btnx-primary" type="button" onclick="window.print()">
            <i class="fa fa-print"></i> Cetak
        </button>
    </div>
</div>

@endsection

