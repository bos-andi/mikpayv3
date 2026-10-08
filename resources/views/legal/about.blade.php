@extends('legal.layout')

@php $company = config('mikpay.company'); @endphp

@section('title', 'Tentang Kami — MikPay')
@section('meta_description', 'MikPay adalah software billing & manajemen jaringan untuk ISP lokal dan RT/RW Net, dikembangkan oleh '.$company['name'].'.')
@section('legal_kicker', 'Perusahaan')
@section('legal_title', 'Tentang Kami')
@section('legal_subtitle', 'MikPay membantu ISP lokal dan RT/RW Net mengelola pelanggan, tagihan, dan jaringan MikroTik dalam satu panel.')

@section('legal_body')
<h2>Siapa Kami</h2>
<p>
    <strong>MikPay</strong> (<a href="{{ route('home') }}">mikpay.link</a>) adalah aplikasi berbasis web (<em>Software as a Service</em>)
    yang dikembangkan dan dioperasikan oleh <strong>{{ $company['name'] }}</strong>, badan usaha berbadan hukum Indonesia
    dengan Nomor Induk Berusaha (NIB) <strong>{{ $company['nib'] }}</strong>, berkedudukan di {{ $company['address'] }}.
</p>
<p>
    Kami membangun MikPay untuk pemilik usaha penyedia internet skala kecil dan menengah — ISP lokal, RT/RW Net, dan
    pengelola WiFi voucher — yang selama ini mencatat pelanggan dan tagihan secara manual. Dengan MikPay, seluruh proses
    operasional dapat dilakukan dari satu panel yang terhubung langsung ke router MikroTik.
</p>

<h2>Layanan Kami</h2>
<ul>
    <li><strong>Manajemen pelanggan</strong> — data pelanggan PPPoE & hotspot, sinkron otomatis dengan router MikroTik.</li>
    <li><strong>Billing & tagihan otomatis</strong> — tagihan bulanan, pengingat WhatsApp, dan isolir otomatis bagi pelanggan yang menunggak.</li>
    <li><strong>Pembayaran online</strong> — pelanggan ISP dapat membayar tagihan melalui QRIS, Virtual Account, dan e-wallet yang diproses oleh mitra payment gateway berizin Bank Indonesia.</li>
    <li><strong>Voucher hotspot</strong> — pembuatan, pencetakan, dan pengelolaan voucher WiFi.</li>
    <li><strong>Remote VPN</strong> — akses jarak jauh ke router MikroTik dan OLT melalui tunnel OpenVPN tanpa IP publik.</li>
    <li><strong>Peta jaringan (GIS)</strong> — pemetaan ODP, port, dan lokasi pelanggan.</li>
    <li><strong>Laporan keuangan</strong> — ringkasan pemasukan, buku kas, laba rugi, dan neraca sederhana.</li>
</ul>

<h2>Paket & Harga</h2>
<p>MikPay digunakan dengan sistem berlangganan. Setiap akun baru mendapatkan masa coba gratis {{ (int) config('mikpay.trial_days', 4) }} hari dengan seluruh fitur.</p>
<table>
    <thead>
        <tr><th>Paket</th><th>Masa aktif</th><th>Harga</th></tr>
    </thead>
    <tbody>
        @foreach (\App\Services\SubscriptionPackageService::all() as $pkg)
            <tr>
                <td>{{ $pkg['name'] }}</td>
                <td>{{ (int) $pkg['duration'] }} hari</td>
                <td>Rp {{ number_format((int) $pkg['price'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<p>Detail fitur setiap paket dapat dilihat di halaman <a href="{{ route('home') }}#harga">Harga</a>.</p>

<h2>Visi</h2>
<p>
    Menjadi platform manajemen ISP yang paling mudah digunakan di Indonesia, sehingga pelaku usaha internet lokal dapat
    fokus memperluas jaringan dan melayani pelanggan, bukan disibukkan oleh pekerjaan administratif.
</p>

<h2>Hubungi Kami</h2>
<ul>
    <li>Email: <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a></li>
    <li>WhatsApp / Telepon: <a href="https://wa.me/{{ config('mikpay.whatsapp.number') }}" target="_blank" rel="noopener">{{ $company['phone'] }}</a></li>
    <li>Alamat: {{ $company['address'] }}</li>
    <li>Jam layanan: Senin – Sabtu, 08.00 – 20.00 WIB</li>
</ul>
@endsection
