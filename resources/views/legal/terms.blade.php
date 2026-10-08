@extends('legal.layout')

@php
    $company = config('mikpay.company');
    $trialDays = (int) config('mikpay.trial_days', 4);
@endphp

@section('title', 'Syarat & Ketentuan — MikPay')
@section('meta_description', 'Syarat dan ketentuan penggunaan layanan MikPay (mikpay.link).')
@section('legal_title', 'Syarat & Ketentuan')
@section('legal_subtitle', 'Ketentuan ini mengatur penggunaan layanan MikPay. Dengan mendaftar atau menggunakan MikPay, Anda dianggap telah membaca, memahami, dan menyetujui seluruh isi ketentuan ini.')

@section('legal_body')
<h2>1. Definisi</h2>
<ul>
    <li><strong>"Kami" / "MikPay"</strong> adalah {{ $company['name'] }} (NIB {{ $company['nib'] }}) selaku pengembang dan pengelola layanan di domain mikpay.link beserta subdomainnya.</li>
    <li><strong>"Pengguna" / "Tenant"</strong> adalah orang perorangan atau badan usaha (misalnya ISP lokal atau RT/RW Net) yang mendaftar dan menggunakan MikPay.</li>
    <li><strong>"Pelanggan Akhir"</strong> adalah pelanggan internet milik Tenant yang datanya dikelola atau yang melakukan pembayaran tagihan melalui MikPay.</li>
    <li><strong>"Layanan"</strong> adalah seluruh fitur MikPay, termasuk panel manajemen, billing, pembayaran online, voucher hotspot, remote VPN, peta GIS, dan laporan keuangan.</li>
    <li><strong>"Payment Gateway"</strong> adalah penyedia jasa pembayaran berizin Bank Indonesia yang bekerja sama dengan Kami untuk memproses pembayaran online.</li>
</ul>

<h2>2. Pendaftaran & Akun</h2>
<ol>
    <li>Pengguna wajib memberikan data yang benar, lengkap, dan terbaru saat pendaftaran, termasuk nama, email, dan nomor telepon yang aktif.</li>
    <li>Pengguna bertanggung jawab menjaga kerahasiaan kata sandi dan seluruh aktivitas yang terjadi pada akunnya, termasuk aktivitas pengguna tambahan yang ia undang.</li>
    <li>Satu akun Tenant memperoleh satu subdomain (misalnya <em>namaisp.mikpay.link</em>). Subdomain yang menyesatkan, melanggar merek pihak lain, atau mengandung unsur SARA dapat Kami tolak atau ubah.</li>
    <li>Pengguna wajib segera memberi tahu Kami apabila mengetahui adanya penggunaan akun tanpa izin.</li>
</ol>

<h2>3. Masa Coba, Paket & Pembayaran Langganan</h2>
<ol>
    <li>Akun baru mendapatkan masa coba gratis selama <strong>{{ $trialDays }} hari</strong> tanpa kewajiban membayar.</li>
    <li>Setelah masa coba berakhir, Layanan dapat terus digunakan dengan membeli paket berlangganan sesuai harga yang tercantum di halaman <a href="{{ route('home') }}#harga">Harga</a>. Seluruh harga dalam Rupiah.</li>
    <li>Pembayaran langganan dilakukan melalui saldo akun yang diisi menggunakan metode pembayaran yang tersedia (QRIS, Virtual Account, e-wallet, atau transfer bank yang diverifikasi admin).</li>
    <li>Perpanjangan sebelum masa aktif habis akan menambah masa aktif dari tanggal berakhir sebelumnya, sehingga sisa hari tidak hangus.</li>
    <li>Apabila masa aktif berakhir dan belum diperpanjang, akses panel akan dinonaktifkan sementara. Data Pengguna tetap Kami simpan sesuai ketentuan pada Kebijakan Privasi.</li>
    <li>Kami dapat mengubah harga paket dengan pemberitahuan sebelumnya. Perubahan harga tidak berlaku untuk masa aktif yang sudah dibayar.</li>
</ol>

<h2>4. Pembayaran Tagihan Pelanggan Akhir</h2>
<ol>
    <li>Tenant dapat mengaktifkan fitur pembayaran online sehingga Pelanggan Akhir dapat membayar tagihan internet melalui tautan pembayaran MikPay. Transaksi diproses oleh Payment Gateway mitra Kami; MikPay tidak menyimpan data kartu atau kredensial pembayaran Pelanggan Akhir.</li>
    <li>Apabila Tenant menggunakan akun Payment Gateway milik MikPay, dana pembayaran Pelanggan Akhir diterima terlebih dahulu oleh MikPay dan dicatat sebagai saldo Tenant. Tenant dapat mengajukan <strong>penarikan dana</strong> ke rekening bank atau e-wallet atas nama Tenant melalui menu Laporan Keuangan.</li>
    <li>Setiap penarikan dana diverifikasi oleh admin MikPay dan diproses paling lambat 3 (tiga) hari kerja setelah disetujui. Dana yang baru diterima dapat ditahan sementara sesuai masa settlement Payment Gateway. Biaya penarikan (jika ada) ditampilkan sebelum Tenant mengajukan permintaan.</li>
    <li>Kami berhak menunda atau menolak penarikan apabila terdapat indikasi penipuan, data rekening tidak sesuai, sengketa transaksi, atau permintaan dari otoritas yang berwenang.</li>
    <li>Tenant sepenuhnya bertanggung jawab atas layanan internet, harga, dan hubungan hukum dengan Pelanggan Akhirnya, termasuk penanganan keluhan dan pengembalian dana tagihan internet sebagaimana diatur dalam <a href="{{ route('legal.refund') }}">Kebijakan Pengembalian Dana</a>.</li>
</ol>

<h2>5. Kewajiban & Larangan Pengguna</h2>
<p>Pengguna dilarang menggunakan Layanan untuk:</p>
<ul>
    <li>kegiatan yang melanggar hukum Republik Indonesia, termasuk perjudian, penipuan, pencucian uang, pendanaan terorisme, atau penjualan barang/jasa terlarang;</li>
    <li>menerima pembayaran yang bukan merupakan tagihan layanan internet atau voucher yang sah milik Tenant;</li>
    <li>mengakses, mengganggu, atau mencoba membobol sistem, akun, atau data milik pengguna lain;</li>
    <li>menyalahgunakan fitur remote VPN untuk serangan jaringan, spam, atau akses tanpa izin ke perangkat pihak lain;</li>
    <li>mengirim pesan WhatsApp/email massal yang bersifat spam atau tidak dikehendaki penerima.</li>
</ul>
<p>Pengguna wajib memastikan memiliki dasar yang sah (misalnya persetujuan Pelanggan Akhir) untuk memasukkan dan mengolah data Pelanggan Akhir di MikPay.</p>

<h2>6. Ketersediaan Layanan</h2>
<ol>
    <li>Kami berupaya menjaga Layanan tersedia 24 jam sehari, namun tidak menjamin Layanan bebas dari gangguan. Pemeliharaan terjadwal akan diinformasikan sebelumnya bila memungkinkan.</li>
    <li>Fungsi yang bergantung pada pihak ketiga — seperti koneksi router MikroTik milik Pengguna, jaringan internet, Payment Gateway, atau penyedia WhatsApp — berada di luar kendali penuh Kami.</li>
</ol>

<h2>7. Hak Kekayaan Intelektual</h2>
<p>
    Seluruh perangkat lunak, desain, logo, dan merek MikPay adalah milik {{ $company['name'] }}. Pengguna diberikan hak
    terbatas, non-eksklusif, dan tidak dapat dialihkan untuk menggunakan Layanan selama masa berlangganan. Data yang
    dimasukkan Pengguna tetap menjadi milik Pengguna.
</p>

<h2>8. Batasan Tanggung Jawab</h2>
<p>
    Sepanjang diizinkan oleh hukum yang berlaku, Kami tidak bertanggung jawab atas kerugian tidak langsung, kehilangan
    keuntungan, atau kerugian akibat kesalahan konfigurasi perangkat, kelalaian Pengguna menjaga akun, maupun gangguan
    layanan pihak ketiga. Total tanggung jawab Kami atas klaim apa pun dibatasi sebesar biaya langganan yang dibayarkan
    Pengguna dalam 3 (tiga) bulan terakhir sebelum kejadian.
</p>

<h2>9. Penangguhan & Pengakhiran</h2>
<ol>
    <li>Pengguna dapat berhenti menggunakan Layanan kapan saja dengan tidak memperpanjang langganan atau mengajukan penghapusan akun.</li>
    <li>Kami dapat menangguhkan atau mengakhiri akun yang melanggar ketentuan ini, dengan atau tanpa pemberitahuan terlebih dahulu tergantung tingkat pelanggaran.</li>
    <li>Saldo penarikan Tenant yang sah tetap akan dibayarkan setelah dikurangi kewajiban yang belum diselesaikan, kecuali terdapat larangan dari otoritas yang berwenang.</li>
</ol>

<h2>10. Perubahan Ketentuan</h2>
<p>
    Kami dapat memperbarui Syarat & Ketentuan ini sewaktu-waktu. Perubahan material akan diinformasikan melalui email
    atau pemberitahuan di panel. Penggunaan Layanan setelah perubahan berlaku berarti Pengguna menyetujui ketentuan yang baru.
</p>

<h2>11. Hukum yang Berlaku & Penyelesaian Sengketa</h2>
<p>
    Ketentuan ini tunduk pada hukum Negara Republik Indonesia. Setiap perselisihan akan diselesaikan terlebih dahulu
    secara musyawarah. Apabila tidak tercapai kesepakatan dalam 30 (tiga puluh) hari, perselisihan diselesaikan melalui
    Pengadilan Negeri Sidoarjo.
</p>

<h2>12. Kontak</h2>
<p>
    Pertanyaan mengenai ketentuan ini dapat disampaikan ke <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a>
    atau WhatsApp <a href="https://wa.me/{{ config('mikpay.whatsapp.number') }}" target="_blank" rel="noopener">{{ $company['phone'] }}</a>.
</p>
@endsection
