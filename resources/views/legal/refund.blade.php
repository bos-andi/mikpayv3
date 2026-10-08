@extends('legal.layout')

@php
    $company = config('mikpay.company');
    $trialDays = (int) config('mikpay.trial_days', 4);
@endphp

@section('title', 'Kebijakan Pengembalian Dana — MikPay')
@section('meta_description', 'Kebijakan pengembalian dana (refund) untuk langganan MikPay dan pembayaran tagihan melalui MikPay.')
@section('legal_title', 'Kebijakan Pengembalian Dana')
@section('legal_subtitle', 'Kebijakan ini menjelaskan kapan pengembalian dana (refund) dapat diberikan, cara mengajukannya, dan berapa lama prosesnya.')

@section('legal_body')
<h2>1. Coba Gratis Sebelum Membayar</h2>
<p>
    Setiap akun baru mendapatkan masa coba gratis <strong>{{ $trialDays }} hari</strong> dengan seluruh fitur, tanpa perlu
    membayar di muka. Kami menyarankan Anda memanfaatkan masa coba untuk memastikan MikPay sesuai kebutuhan sebelum membeli paket.
</p>

<h2>2. Langganan MikPay</h2>
<h3>a. Refund dapat diberikan apabila:</h3>
<ul>
    <li><strong>Pembayaran ganda</strong> — Anda terdebit lebih dari sekali untuk transaksi yang sama.</li>
    <li><strong>Pembayaran berhasil tetapi tidak tercatat</strong> — dana sudah terpotong namun saldo atau masa aktif tidak bertambah dan tidak dapat Kami perbaiki dalam 2 x 24 jam.</li>
    <li><strong>Gangguan layanan berat</strong> — Layanan tidak dapat diakses sama sekali lebih dari 3 (tiga) hari berturut-turut karena kesalahan di pihak Kami. Refund diberikan secara proporsional untuk sisa masa aktif.</li>
    <li><strong>Saldo akun yang belum terpakai</strong> — saldo hasil isi ulang (deposit) yang belum digunakan untuk membeli paket dapat diajukan pengembaliannya, dipotong biaya transfer bank yang berlaku.</li>
</ul>
<h3>b. Refund tidak dapat diberikan untuk:</h3>
<ul>
    <li>Paket langganan yang sudah aktif dan dapat digunakan normal, termasuk apabila Pengguna berhenti menggunakan Layanan sebelum masa aktif habis.</li>
    <li>Gangguan yang disebabkan oleh perangkat, koneksi internet, atau konfigurasi router milik Pengguna.</li>
    <li>Akun yang ditangguhkan atau diakhiri karena melanggar <a href="{{ route('legal.terms') }}">Syarat & Ketentuan</a>.</li>
</ul>

<h2>3. Pembayaran Tagihan Internet oleh Pelanggan Akhir</h2>
<p>
    MikPay menyediakan sarana pembayaran online bagi pelanggan dari penyedia internet (ISP/RT RW Net) yang menggunakan MikPay.
    Layanan internet, harga, dan kebijakan pelanggan sepenuhnya ditentukan oleh masing-masing penyedia internet tersebut.
</p>
<ul>
    <li>Permintaan refund atas tagihan internet (misalnya karena layanan internet tidak aktif) diajukan kepada <strong>penyedia internet Anda</strong>, yang namanya tercantum pada halaman tagihan.</li>
    <li>Apabila terjadi <strong>pembayaran ganda atau kesalahan sistem</strong> pada proses pembayaran di MikPay, Pelanggan Akhir dapat menghubungi Kami langsung. Setelah diverifikasi, kelebihan pembayaran akan dikembalikan atau dikreditkan ke tagihan periode berikutnya sesuai pilihan pelanggan.</li>
    <li>Refund yang disetujui penyedia internet untuk transaksi yang diproses melalui akun pembayaran MikPay akan Kami bantu kembalikan ke pelanggan dan dikurangkan dari saldo penyedia internet terkait.</li>
</ul>

<h2>4. Cara Mengajukan Refund</h2>
<ol>
    <li>Hubungi Kami melalui email <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a> atau WhatsApp <a href="https://wa.me/{{ config('mikpay.whatsapp.number') }}" target="_blank" rel="noopener">{{ $company['phone'] }}</a> dengan subjek "Refund".</li>
    <li>Sertakan: nama dan subdomain akun (atau nomor tagihan bagi Pelanggan Akhir), ID transaksi/pembayaran, tanggal, nominal, bukti pembayaran, dan alasan pengajuan.</li>
    <li>Pengajuan paling lambat <strong>14 (empat belas) hari</strong> sejak tanggal transaksi.</li>
</ol>

<h2>5. Proses & Waktu Pengembalian</h2>
<table>
    <thead>
        <tr><th>Tahap</th><th>Waktu</th></tr>
    </thead>
    <tbody>
        <tr><td>Konfirmasi penerimaan pengajuan</td><td>Maksimal 1 x 24 jam hari kerja</td></tr>
        <tr><td>Verifikasi & keputusan</td><td>Maksimal 3 hari kerja</td></tr>
        <tr><td>Pengembalian dana setelah disetujui</td><td>Maksimal 7–14 hari kerja</td></tr>
    </tbody>
</table>
<p>
    Dana dikembalikan melalui metode pembayaran asal apabila didukung oleh payment gateway, atau melalui transfer ke
    rekening bank/e-wallet atas nama pemohon. Biaya administrasi payment gateway yang sudah terpotong pada transaksi awal
    dapat tidak ikut dikembalikan.
</p>

<h2>6. Kontak</h2>
<p>
    Untuk pertanyaan seputar kebijakan ini, silakan hubungi {{ $company['name'] }} melalui
    <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a>. Kami berkomitmen menangani setiap pengajuan secara adil dan transparan.
</p>
@endsection
