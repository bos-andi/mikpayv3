<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Voucher — {{ $tenant?->name ?? 'MikPay' }}</title>
    @include('partials.favicon')
    <style>
        * { box-sizing: border-box; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 16px; background: #f8fafc; color: #0f172a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 12px; margin-bottom: 16px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
        .card {
            background: #fff; border: 1px dashed #94a3b8; border-radius: 12px; padding: 12px;
            text-align: center; break-inside: avoid;
        }
        .brand { font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #4f46e5; }
        .code { font-family: ui-monospace, monospace; font-size: 18px; font-weight: 800; margin: 8px 0; letter-spacing: .06em; }
        .meta { font-size: 11px; color: #475569; }
        .price { font-size: 13px; font-weight: 700; margin-top: 6px; }
        @media print {
            body { background: #fff; margin: 8px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:12px;">
        <button onclick="window.print()" style="padding:8px 14px;border-radius:10px;background:#4f46e5;color:#fff;border:0;font-weight:700;cursor:pointer;">Cetak</button>
        <a href="{{ panel_route('vouchers') }}?section=users" style="margin-left:8px;font-size:12px;">← Kembali</a>
    </div>

    <h1>Voucher Hotspot — {{ $tenant?->name ?? 'MikPay' }}</h1>
    <p class="muted">{{ $router->displayName() }} · {{ count($cards) }} voucher · {{ now()->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</p>

    <div class="grid">
        @forelse ($cards as $c)
            <div class="card">
                <div class="brand">{{ $tenant?->name ?? 'MikPay' }} Hotspot</div>
                <div class="code">{{ $c['username'] }}</div>
                @if ($c['password'] !== $c['username'])
                    <div class="meta">Pass: <strong>{{ $c['password'] }}</strong></div>
                @endif
                <div class="meta">{{ $c['profile'] }}@if($c['validity']) · {{ $c['validity'] }}@endif</div>
                @if ($c['price'] > 0)
                    <div class="price">Rp {{ number_format($c['price'], 0, ',', '.') }}</div>
                @endif
            </div>
        @empty
            <p class="muted">Tidak ada voucher untuk dicetak.</p>
        @endforelse
    </div>

    <script>window.addEventListener('load', () => { if ({{ count($cards) }} > 0) setTimeout(() => window.print(), 300); });</script>
</body>
</html>
