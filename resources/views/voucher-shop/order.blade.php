@extends('voucher-shop.layout')

@section('title', 'Pesanan '.$order->reference.' — '.$settings['title'])

@section('content')
@php
    $rp = fn ($v) => 'Rp'.number_format((int) $v, 0, ',', '.');
    $status = $order->isPaymentExpired() ? 'expired' : $order->status;
    $shopUrl = route('tenant.voucher-shop.index', ['tenant' => $shopTenant->subdomain]);
    $statusUrl = route('tenant.voucher-shop.status', ['tenant' => $shopTenant->subdomain, 'token' => $order->token]);
    $loginUrl = null;
    if ($status === 'fulfilled' && $settings['login_url'] !== '') {
        $loginUrl = $settings['login_url'].(str_contains($settings['login_url'], '?') ? '&' : '?')
            .http_build_query(['username' => $order->voucher_code, 'password' => $order->voucher_password ?: $order->voucher_code]);
    }
    $waHelp = $settings['contact_wa'] !== ''
        ? 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/\D/', '', $settings['contact_wa'])).'?text='.rawurlencode('Halo, saya sudah bayar voucher WiFi (Ref '.$order->reference.') tapi kodenya belum muncul.')
        : null;
@endphp

@if ($status === 'pending')
    <div class="vs-card">
        <div class="vs-qr-amount">
            <small>Total bayar · {{ $order->item_label }}</small>
            <strong>{{ $rp($order->total) }}</strong>
        </div>
        <div class="vs-qr">
            <div class="vs-qr-brand">QRIS</div>
            <div id="vsQr" role="img" aria-label="Kode QRIS pembayaran"></div>
            <div style="font-size:11px;color:var(--muted)">a.n. MikPay · {{ $order->reference }}</div>
        </div>
        <div class="vs-timer">⏱ Bayar dalam <b id="vsTimer">--:--</b></div>
        <div class="vs-wait"><span class="vs-dot"></span> Menunggu pembayaran… halaman ini otomatis berubah setelah bayar</div>

        <div class="vs-btns two">
            <button type="button" class="vs-btn vs-btn-ghost" id="vsDownload">⬇ Simpan QR</button>
            <a href="{{ $shopUrl }}" class="vs-btn vs-btn-ghost">Ganti paket</a>
        </div>

        <ol class="vs-howto">
            <li>Buka GoPay, OVO, DANA, ShopeePay, LinkAja atau m-banking.</li>
            <li>Pilih <b>Scan / Bayar QRIS</b>, arahkan ke kode di atas.<br><span style="color:var(--muted)">Bayar dari HP yang sama? Tekan "Simpan QR", lalu unggah gambarnya di menu scan.</span></li>
            <li>Pastikan nominal <b>{{ $rp($order->total) }}</b>, lalu konfirmasi.</li>
            <li>Kode voucher langsung muncul di halaman ini.</li>
        </ol>

        <div class="vs-meta">
            <div><span>Harga voucher</span><b>{{ $rp($order->price) }}</b></div>
            <div><span>Biaya layanan</span><b>{{ $rp($order->fee) }}</b></div>
        </div>
    </div>
@elseif ($status === 'paid')
    <div class="vs-card vs-success">
        <div class="vs-spin" aria-hidden="true"></div>
        <h3>Pembayaran diterima ✓</h3>
        <p>Sedang membuat voucher Anda di router… biasanya hanya beberapa detik.</p>
        <div class="vs-wait"><span class="vs-dot"></span> Jangan tutup halaman ini</div>
    </div>
@elseif ($status === 'fulfilled')
    <div class="vs-card vs-success">
        <div class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></div>
        <h3>Voucher siap dipakai!</h3>
        <p>{{ $order->item_label }} · {{ $settings['title'] }}</p>

        <div class="vs-code">
            <small>Kode voucher</small>
            <strong id="vsCode">{{ $order->voucher_code }}</strong>
            @if ($order->voucher_password && $order->voucher_password !== $order->voucher_code)
                <small style="margin-top:10px">Password</small>
                <strong style="font-size:22px">{{ $order->voucher_password }}</strong>
            @endif
        </div>

        <div class="vs-btns {{ $loginUrl ? 'two' : '' }}">
            <button type="button" class="vs-btn vs-btn-ghost" id="vsCopy" data-code="{{ $order->voucher_code }}">📋 Salin kode</button>
            @if ($loginUrl)
                <a href="{{ $loginUrl }}" class="vs-btn vs-btn-ok">Login sekarang →</a>
            @endif
        </div>

        <ol class="vs-howto" style="text-align:left">
            <li>Sambungkan HP ke jaringan <b>{{ $settings['title'] }}</b>.</li>
            <li>Halaman login muncul otomatis (atau buka browser).</li>
            <li>Masukkan kode <b>{{ $order->voucher_code }}</b>, lalu tekan Login.</li>
        </ol>

        @if ($order->wa_sent_at)
            <div class="vs-alert info" style="margin:12px 0 0;text-align:left">Salinan kode sudah dikirim ke WhatsApp Anda.</div>
        @endif
        <div class="vs-alert warn" style="margin:12px 0 0;text-align:left">Simpan halaman ini atau screenshot kodenya. Link ini bisa dibuka lagi kapan saja.</div>

        <div class="vs-meta" style="text-align:left">
            <div><span>Referensi</span><b>{{ $order->reference }}</b></div>
            <div><span>Dibayar</span><b>{{ $order->paid_at ? format_wib($order->paid_at, 'd M Y H:i') : '-' }}</b></div>
        </div>
        <div class="vs-btns"><a href="{{ $shopUrl }}" class="vs-btn vs-btn-ghost">Beli voucher lagi</a></div>
    </div>
@elseif ($status === 'failed')
    <div class="vs-card vs-success">
        <div class="ic" style="background:#fffbeb;color:var(--warn)" aria-hidden="true">!</div>
        <h3>Pembayaran diterima, voucher tertunda</h3>
        <p>Router WiFi sedang tidak bisa dihubungi. Pembayaran Anda aman dan voucher akan dibuat begitu router kembali normal. Halaman ini otomatis memperbarui.</p>
        <div class="vs-meta" style="text-align:left">
            <div><span>Referensi</span><b>{{ $order->reference }}</b></div>
            <div><span>Dibayar</span><b>{{ $rp($order->total) }}</b></div>
        </div>
        @if ($waHelp)
            <div class="vs-btns"><a href="{{ $waHelp }}" target="_blank" rel="noopener" class="vs-btn vs-btn-ok">Hubungi pengelola WiFi</a></div>
        @endif
    </div>
@else
    <div class="vs-card">
        <div class="vs-empty">
            <div class="ic" aria-hidden="true">⌛</div>
            <h3>Waktu pembayaran habis</h3>
            <p>QRIS untuk pesanan {{ $order->reference }} sudah tidak berlaku. Silakan buat pesanan baru.</p>
        </div>
        <div class="vs-btns"><a href="{{ $shopUrl }}" class="vs-btn vs-btn-primary">Beli voucher</a></div>
    </div>
@endif
@endsection

@push('scripts')
@if ($status === 'pending')
    <script src="{{ asset('js/vendor/qrcode.min.js') }}"></script>
@endif
<script>
(function () {
    var status = @json($status);
    var statusUrl = @json($statusUrl);

    @if ($status === 'pending')
    var qrText = @json($order->qr_string);
    var box = document.getElementById('vsQr');
    if (window.QRCode && qrText) {
        new QRCode(box, { text: qrText, width: 480, height: 480, colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
        var img = box.querySelector('img');
        if (img) img.alt = 'QRIS';
    } else {
        box.textContent = 'QR gagal dimuat. Muat ulang halaman.';
    }

    var expiresAt = new Date(@json($order->expires_at?->toIso8601String())).getTime();
    var timerEl = document.getElementById('vsTimer');
    (function tick() {
        var left = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
        timerEl.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
        if (left <= 0) { location.reload(); return; }
        setTimeout(tick, 1000);
    })();

    document.getElementById('vsDownload').addEventListener('click', function () {
        var src = box.querySelector('canvas');
        if (!src) return;
        var pad = 48, size = 480, c = document.createElement('canvas');
        c.width = size + pad * 2; c.height = size + pad * 2 + 90;
        var g = c.getContext('2d');
        g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height);
        g.fillStyle = '#111'; g.textAlign = 'center';
        g.font = 'bold 34px system-ui, sans-serif'; g.fillText('QRIS', c.width / 2, 40);
        g.drawImage(src, pad, pad + 10, size, size);
        g.font = 'bold 30px system-ui, sans-serif';
        g.fillText(@json($rp($order->total)), c.width / 2, size + pad + 58);
        g.font = '20px system-ui, sans-serif'; g.fillStyle = '#555';
        g.fillText(@json($order->reference.' · '.$settings['title']), c.width / 2, size + pad + 90);
        var a = document.createElement('a');
        a.href = c.toDataURL('image/png');
        a.download = 'QRIS-' + @json($order->reference) + '.png';
        document.body.appendChild(a); a.click(); a.remove();
    });
    @endif

    var copyBtn = document.getElementById('vsCopy');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var code = copyBtn.dataset.code, done = function () { copyBtn.textContent = '✓ Tersalin'; setTimeout(function () { copyBtn.textContent = '📋 Salin kode'; }, 2000); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(code).then(done);
            } else {
                var t = document.createElement('textarea'); t.value = code; document.body.appendChild(t); t.select();
                try { document.execCommand('copy'); done(); } catch (e) {}
                t.remove();
            }
        });
    }

    if (status === 'pending' || status === 'paid' || status === 'failed') {
        var interval = status === 'failed' ? 10000 : 3000;
        var poll = function () {
            fetch(statusUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) {
                    if (d && d.status && d.status !== status) { location.reload(); return; }
                    setTimeout(poll, interval);
                })
                .catch(function () { setTimeout(poll, interval * 2); });
        };
        setTimeout(poll, interval);
    }
})();
</script>
@endpush
