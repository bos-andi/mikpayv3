@extends('legal.layout')

@php $company = config('mikpay.company'); @endphp

@section('title', 'Kebijakan Privasi — MikPay')
@section('meta_description', 'Kebijakan privasi MikPay: data apa yang kami kumpulkan, bagaimana digunakan, dan hak Anda atas data pribadi.')
@section('legal_title', 'Kebijakan Privasi')
@section('legal_subtitle', 'Kami menghormati privasi Anda. Kebijakan ini menjelaskan data apa yang kami kumpulkan, bagaimana kami menggunakan dan melindunginya, serta hak Anda sesuai UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi.')

@section('legal_body')
<h2>1. Ruang Lingkup</h2>
<p>
    Kebijakan ini berlaku untuk seluruh layanan MikPay di domain mikpay.link beserta subdomainnya, yang dikelola oleh
    <strong>{{ $company['name'] }}</strong>. Istilah yang digunakan mengikuti definisi pada
    <a href="{{ route('legal.terms') }}">Syarat & Ketentuan</a>.
</p>

<h2>2. Data yang Kami Kumpulkan</h2>
<h3>a. Data akun Pengguna (Tenant)</h3>
<ul>
    <li>Nama, alamat email, nomor WhatsApp, nama usaha, dan subdomain.</li>
    <li>Kata sandi akun (disimpan dalam bentuk <em>hash</em>, tidak dapat dibaca oleh siapa pun termasuk Kami).</li>
    <li>Data rekening bank/e-wallet untuk penarikan dana (nama bank, nomor rekening, nama pemilik).</li>
    <li>Riwayat pembayaran langganan dan penarikan dana.</li>
</ul>
<h3>b. Data operasional yang dimasukkan Tenant</h3>
<ul>
    <li>Data Pelanggan Akhir: nama, nomor telepon, alamat/koordinat lokasi, username PPPoE/hotspot, paket, dan riwayat tagihan.</li>
    <li>Kredensial perangkat (router MikroTik, OLT) dan kunci API payment gateway milik Tenant — disimpan <strong>terenkripsi</strong>.</li>
</ul>
<p>
    Untuk data Pelanggan Akhir, Tenant bertindak sebagai <strong>Pengendali Data</strong>, sedangkan MikPay bertindak sebagai
    <strong>Prosesor Data</strong> yang mengolah data tersebut semata-mata atas instruksi Tenant untuk menjalankan Layanan.
</p>
<h3>c. Data teknis</h3>
<ul>
    <li>Alamat IP, jenis peramban/perangkat, waktu akses, dan log aktivitas untuk keamanan dan pemecahan masalah.</li>
    <li>Cookie sesi yang diperlukan agar Anda tetap masuk (login) dan untuk perlindungan formulir (CSRF).</li>
</ul>

<h2>3. Tujuan Penggunaan Data</h2>
<ul>
    <li>Menyediakan, mengoperasikan, dan memelihara Layanan, termasuk sinkronisasi dengan router MikroTik.</li>
    <li>Memproses pembayaran langganan, pembayaran tagihan Pelanggan Akhir, dan penarikan dana Tenant.</li>
    <li>Mengirim notifikasi layanan: email selamat datang, pengingat masa aktif, reset kata sandi, serta pengingat tagihan WhatsApp yang diaktifkan Tenant.</li>
    <li>Menjaga keamanan akun, mencegah penipuan dan penyalahgunaan (termasuk verifikasi anti-bot).</li>
    <li>Memenuhi kewajiban hukum dan permintaan resmi dari otoritas yang berwenang.</li>
</ul>
<p>Kami <strong>tidak menjual</strong> data pribadi Anda atau data Pelanggan Akhir kepada pihak mana pun.</p>

<h2>4. Berbagi Data dengan Pihak Ketiga</h2>
<p>Kami hanya membagikan data seperlunya kepada mitra yang membantu menjalankan Layanan:</p>
<table>
    <thead>
        <tr><th>Mitra</th><th>Keperluan</th><th>Data yang dibagikan</th></tr>
    </thead>
    <tbody>
        <tr><td>Payment Gateway (Xendit dan/atau penyedia lain yang dipilih Tenant)</td><td>Memproses pembayaran dan transfer penarikan dana</td><td>Nama, nomor telepon/email, nominal transaksi, data rekening tujuan</td></tr>
        <tr><td>Penyedia server & jaringan (pusat data, Cloudflare)</td><td>Hosting, keamanan, dan verifikasi anti-bot</td><td>Data teknis dan data yang tersimpan di server</td></tr>
        <tr><td>Penyedia email</td><td>Mengirim email notifikasi</td><td>Nama dan alamat email</td></tr>
        <tr><td>Penyedia gateway WhatsApp</td><td>Mengirim pengingat tagihan atas nama Tenant</td><td>Nomor telepon dan isi pesan</td></tr>
    </tbody>
</table>
<p>Data juga dapat diungkapkan apabila diwajibkan oleh peraturan perundang-undangan atau perintah pengadilan.</p>

<h2>5. Keamanan Data</h2>
<ul>
    <li>Seluruh koneksi ke MikPay menggunakan enkripsi HTTPS (TLS).</li>
    <li>Kata sandi di-<em>hash</em>; kredensial router dan kunci API dienkripsi di basis data.</li>
    <li>Data setiap Tenant dipisahkan secara logis sehingga tidak dapat diakses oleh Tenant lain.</li>
    <li>Akses internal dibatasi hanya untuk personel yang berwenang.</li>
</ul>
<p>Meski demikian, tidak ada sistem yang sepenuhnya bebas risiko. Apabila terjadi kegagalan pelindungan data pribadi, Kami akan memberitahukan Pengguna yang terdampak sesuai ketentuan peraturan yang berlaku.</p>

<h2>6. Penyimpanan & Penghapusan Data</h2>
<ul>
    <li>Data disimpan selama akun aktif dan selama diperlukan untuk tujuan yang dijelaskan dalam kebijakan ini.</li>
    <li>Jika langganan berakhir, data tetap disimpan hingga 90 (sembilan puluh) hari agar Pengguna dapat melanjutkan langganan tanpa kehilangan data. Setelah itu data dapat dihapus permanen.</li>
    <li>Catatan transaksi keuangan dapat disimpan lebih lama untuk memenuhi kewajiban perpajakan dan akuntansi.</li>
</ul>

<h2>7. Hak Anda</h2>
<p>Sesuai UU Pelindungan Data Pribadi, Anda berhak untuk:</p>
<ul>
    <li>mengakses dan memperoleh salinan data pribadi Anda;</li>
    <li>memperbaiki data yang tidak akurat;</li>
    <li>meminta penghapusan data atau penutupan akun;</li>
    <li>menarik persetujuan pemrosesan data tertentu (misalnya notifikasi pemasaran);</li>
    <li>mengajukan keberatan atas pemrosesan data.</li>
</ul>
<p>
    Pelanggan Akhir yang ingin menggunakan hak-hak tersebut dapat menghubungi penyedia internet (Tenant) masing-masing
    sebagai Pengendali Data, atau menghubungi Kami dan Kami akan meneruskannya kepada Tenant terkait.
</p>

<h2>8. Cookie</h2>
<p>
    MikPay hanya menggunakan cookie yang diperlukan untuk fungsi login, keamanan, dan preferensi tampilan (misalnya mode
    gelap). Kami tidak menggunakan cookie iklan pihak ketiga.
</p>

<h2>9. Perubahan Kebijakan</h2>
<p>Kebijakan ini dapat diperbarui sewaktu-waktu. Tanggal pembaruan terakhir tercantum di bagian atas halaman ini.</p>

<h2>10. Kontak Pelindungan Data</h2>
<p>
    Permintaan terkait data pribadi dapat dikirim ke <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a>
    dengan subjek "Data Pribadi". Kami akan menanggapi paling lambat 3 x 24 jam hari kerja.
</p>
@endsection
