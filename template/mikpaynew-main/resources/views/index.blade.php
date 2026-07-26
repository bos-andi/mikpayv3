<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $landingFavicon = file_exists(public_path('gallery/favicon.png'))
            ? asset('gallery/favicon.png')
            : asset('mikpay/img/favicon.png');
        $landingShareImage = file_exists(public_path('gallery/og-share.png'))
            ? asset('gallery/og-share.png')
            : asset('gallery/dashboard-overview.png');
        $landingTitle = 'MikPay - Sistem Pembayaran & Billing MikroTik Terbaik';
        $landingDescription = 'MikPay — billing & pembayaran MikroTik: tagihan PPP, hotspot, voucher, laporan keuangan. Pengingat WhatsApp (Fonnte) terjadwal, Telegram bot per akun, langganan aplikasi dengan QRIS atau transfer manual.';
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $landingTitle }}</title>
    <meta name="description" content="{{ $landingDescription }}">
    <link rel="icon" type="image/png" href="{{ $landingFavicon }}">
    <link rel="apple-touch-icon" href="{{ $landingFavicon }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="{{ $landingTitle }}">
    <meta property="og:description" content="{{ $landingDescription }}">
    <meta property="og:image" content="{{ $landingShareImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $landingTitle }}">
    <meta name="twitter:description" content="{{ $landingDescription }}">
    <meta name="twitter:image" content="{{ $landingShareImage }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #4D44B5;
            --primary-dark: #3d35a0;
            --secondary: #f97316;
            --accent: #6366f1;
            --text-dark: #1e293b;
            --text-gray: #64748b;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --gradient: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
            --gradient-orange: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 1rem 0;
            transition: all 0.3s ease;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--primary);
        }
        
        .logo-wordmark {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .logo img {
            height: 40px;
            width: 40px;
            object-fit: contain;
            display: block;
            flex-shrink: 0;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .btn-register {
            background: var(--gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
            display: inline-block;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(77, 68, 181, 0.3);
        }
        
        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 0.5rem;
            z-index: 1001;
            gap: 5px;
        }
        
        .menu-toggle span {
            width: 25px;
            height: 3px;
            background: var(--text-dark);
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }
        
        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }
        
        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }
        
        /* Mobile Menu */
        @media (max-width: 968px) {
            .menu-toggle {
                display: flex;
            }
            
            .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                width: 280px;
                height: 100vh;
                background: white;
                flex-direction: column;
                align-items: flex-start;
                padding: 5rem 2rem 2rem;
                box-shadow: -5px 0 20px rgba(0, 0, 0, 0.1);
                transition: right 0.3s ease;
                gap: 0;
                z-index: 1000;
                overflow-y: auto;
            }
            
            .nav-links.active {
                right: 0;
            }
            
            .nav-links a {
                width: 100%;
                padding: 1rem 0;
                border-bottom: 1px solid #e2e8f0;
                font-size: 1.125rem;
            }
            
            .nav-links a:last-child {
                border-bottom: none;
                margin-top: 1rem;
            }
            
            .btn-register {
                width: 100%;
                text-align: center;
                padding: 1rem;
            }
            
            .nav-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            
            .nav-overlay.active {
                display: block;
            }
        }
        
        @media (max-width: 640px) {
            .nav-container {
                padding: 0 1rem;
            }
            
            .logo-wordmark {
                font-size: 1.25rem;
            }
            
            .logo img {
                height: 32px;
                width: 32px;
            }
            
            .nav-links {
                width: 100%;
                right: -100%;
            }
        }
        
        /* Hero Section */
        .hero {
            margin-top: 80px;
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            text-align: center;
        }
        
        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.25rem;
            color: var(--text-gray);
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 1rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 10px 30px rgba(77, 68, 181, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(77, 68, 181, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }
        
        /* Section */
        .section {
            padding: 5rem 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }
        
        .section-title p {
            font-size: 1.125rem;
            color: var(--text-gray);
        }
        
        /* Features */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            color: white;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
        }
        
        .feature-card p {
            color: var(--text-gray);
            line-height: 1.7;
        }
        
        /* Gallery */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 3rem;
        }
        
        .gallery-item {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            aspect-ratio: 16/9;
            background: var(--bg-light);
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .gallery-item:hover img {
            transform: scale(1.1);
        }
        
        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 1.5rem;
            color: white;
        }
        
        .gallery-overlay h4 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .gallery-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient);
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        /* Pricing */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }
        
        .pricing-card.featured {
            border: 3px solid var(--secondary);
            transform: scale(1.05);
        }
        
        .pricing-card.featured::before {
            content: attr(data-ribbon);
            position: absolute;
            top: 20px;
            right: -30px;
            background: var(--gradient-orange);
            color: white;
            padding: 5px 40px;
            font-size: 0.75rem;
            font-weight: 700;
            transform: rotate(45deg);
        }
        
        .pricing-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .pricing-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        
        .pricing-price {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .pricing-duration {
            color: var(--text-gray);
            font-size: 0.9rem;
        }
        
        .pricing-original {
            text-decoration: line-through;
            color: var(--text-gray);
            font-size: 1.25rem;
            margin-right: 0.5rem;
        }
        
        .pricing-discount {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .pricing-features {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .pricing-features li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .pricing-features li:last-child {
            border-bottom: none;
        }
        
        .pricing-features li i {
            color: #10b981;
            font-size: 1.125rem;
        }
        
        .pricing-button {
            width: 100%;
            text-align: center;
            padding: 1rem;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        
        .pricing-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(77, 68, 181, 0.3);
        }
        
        .pricing-card.featured .pricing-button {
            background: var(--gradient-orange);
        }
        
        /* Footer */
        .footer {
            background: var(--text-dark);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .footer p {
            opacity: 0.8;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .nav-links {
                gap: 1rem;
            }
            
            .logo img {
                height: 32px;
                width: 32px;
            }
            
            .logo-wordmark {
                font-size: 1.25rem;
            }
            
            .features-grid,
            .gallery-grid,
            .pricing-grid {
                grid-template-columns: 1fr;
            }
            
            .pricing-card.featured {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="logo" style="text-decoration: none;">
                <img src="{{ $landingFavicon }}" alt="" width="40" height="40" decoding="async">
                <span class="logo-wordmark">MikPay</span>
            </a>
            <div class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="nav-overlay" id="navOverlay"></div>
            <div class="nav-links" id="navLinks">
                <a href="#features" onclick="closeMobileMenu()">Fitur</a>
                <a href="#gallery" onclick="closeMobileMenu()">Galeri</a>
                <a href="#pricing" onclick="closeMobileMenu()">Harga</a>
                <a href="#contact" onclick="closeMobileMenu()">Kontak</a>
                <a href="{{ route('login') }}" class="btn-register" onclick="closeMobileMenu()">
                    <i class="fas fa-right-to-bracket"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <h1>Sistem Pembayaran & Billing MikroTik</h1>
            <p>Tagihan PPP &amp; WiFi, voucher, dan laporan keuangan — plus pengingat <strong>WhatsApp (Fonnte)</strong> terjadwal, <strong>Telegram bot</strong> per akun untuk operator, dan <strong>langganan MikPay</strong> dengan QRIS atau transfer + upload bukti.</p>
            <div class="hero-buttons">
                <a href="#pricing" class="btn btn-primary">
                    <i class="fas fa-rocket"></i> Mulai Sekarang
                </a>
                <a href="#features" class="btn btn-secondary">
                    <i class="fas fa-info-circle"></i> Pelajari Lebih Lanjut
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section" style="background: white;">
        <div class="container">
            <div class="section-title">
                <h2>Fitur Lengkap Sistem Pembayaran MikPay</h2>
                <p>Billing MikroTik, notifikasi pelanggan, dan alur langganan aplikasi — diperbarui agar selaras dengan fitur terbaru di dashboard</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Dashboard &amp; ringkasan router</h3>
                    <p>Setelah router terhubung: identitas perangkat, resource, jumlah user hotspot &amp; sesi PPP aktif, serta pemasukan bulanan ringkas dari data MikroTik.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Manajemen Hotspot</h3>
                    <p>Kelola user hotspot, profil, host, IP binding, user aktif, generate/tambah user — lewat API MikroTik dari dalam MikPay.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <h3>PPP Billing & Payment</h3>
                    <p>Sistem billing untuk PPP secrets dengan tracking pembayaran, tunggakan per bulan, dan integrasi pengingat WhatsApp (Fonnte) sesuai jadwal</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3>Sistem Billing &amp; Tagihan WiFi</h3>
                    <p>Data tagihan PPP (jatuh tempo, tunggakan, pembayaran per periode) di database MikPay; pengingat pelanggan via WhatsApp (Fonnte) jika diaktifkan.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3>Voucher Hotspot</h3>
                    <p>Buat profil/user voucher, cetak template voucher, dan laporan penjualan (modul hotspot MikPay + MikroTik).</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Laporan Keuangan</h3>
                    <p>Cashflow, laporan keuangan, dan ekspor PDF dari modul keuangan MikPay.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-whatsapp"></i>
                    </div>
                    <h3>WhatsApp (Fonnte) terjadwal</h3>
                    <p>Token &amp; template per akun: pengingat H-3, hari jatuh tempo (H-0), dan tunggakan. Rentang jam kirim bisa diatur; pengiriman dijalankan scheduler server ke API Fonnte.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Reminder tagihan PPP</h3>
                    <p>Mengikuti status tagihan di modul billing (tunggakan, jatuh tempo, dll.) — terhubung dengan notifikasi WhatsApp dan manajemen pembayaran.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fab fa-telegram"></i>
                    </div>
                    <h3>Telegram Bot per operator</h3>
                    <p>Setiap tenant memasang bot token &amp; chat ID sendiri (tanpa .env): cek tunggakan, isolir/buka/tunda PPP dari chat, plus notifikasi koneksi opsional.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h3>Langganan MikPay fleksibel</h3>
                    <p>Pilih paket, bayar dengan QRIS atau kanal bank/e-wallet (jika dikonfigurasi), unggah bukti — diverifikasi super admin. Trial gratis untuk mulai mencoba.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3>Beberapa router per akun</h3>
                    <p>Daftarkan lebih dari satu MikroTik (session berbeda), pilih router aktif setelah login. Batas jumlah mengikuti paket langganan.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    <h3>Logo &amp; kop surat</h3>
                    <p>Upload logo bisnis dan kop surat untuk dokumen/tagihan lewat pengaturan admin (bukan domain atau tema penuh).</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="section" style="background: var(--bg-light);">
        <div class="container">
            <div class="section-title">
                <h2>Galeri MikPay</h2>
                <p>Lihat tampilan dashboard billing, payment tracking, dan sistem tagihan MikPay</p>
            </div>
            
            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="{{ asset('gallery/dashboard-overview.png') }}" alt="Dashboard Overview - Monitor semua aktivitas dalam satu dashboard" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="gallery-placeholder" style="display: none;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="gallery-overlay">
                        <h4>Dashboard Overview</h4>
                        <p>Monitor semua aktivitas dalam satu dashboard</p>
                    </div>
                </div>
                
                <div class="gallery-item">
                    <img src="{{ asset('gallery/hotspot-payment.png') }}" alt="Hotspot Payment - Kelola pembayaran pengguna hotspot" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="gallery-placeholder" style="display: none;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="gallery-overlay">
                        <h4>Hotspot Payment</h4>
                        <p>Kelola pembayaran pengguna hotspot</p>
                    </div>
                </div>
                
                <div class="gallery-item">
                    <img src="{{ asset('gallery/payment-billing-system.png') }}" alt="Payment & Billing System - Sistem tagihan dan pembayaran otomatis" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="gallery-placeholder" style="display: none;">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="gallery-overlay">
                        <h4>Payment & Billing System</h4>
                        <p>Sistem tagihan dan pembayaran otomatis</p>
                    </div>
                </div>
                
                <div class="gallery-item">
                    <img src="{{ asset('gallery/invoice-payment.png') }}" alt="Struk pembayaran WiFi - cetak struk pembayaran di MikPay" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="gallery-placeholder" style="display: none;">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="gallery-overlay">
                        <h4>Invoice Payment</h4>
                        <p> Cetak Struk Pembayaran WiFi di System MikPay</p>
                    </div>
                </div>
                
                <div class="gallery-item">
                    <img src="{{ asset('gallery/payment-reports.png') }}" alt="Payment Reports - Laporan pembayaran dan keuangan lengkap" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="gallery-placeholder" style="display: none;">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="gallery-overlay">
                        <h4>Payment Reports</h4>
                        <p>Laporan pembayaran dan keuangan lengkap</p>
                    </div>
                </div>
                
                <div class="gallery-item">
                    <img src="{{ asset('gallery/ppp-billing.png') }}" alt="PPP Billing - Sistem billing dan pembayaran PPP" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="gallery-placeholder" style="display: none;">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div class="gallery-overlay">
                        <h4>PPP Billing</h4>
                        <p>Sistem billing dan pembayaran PPP</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trial Promotion Section -->
    <section class="section" style="background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); padding: 4rem 2rem;">
        <div class="container">
            <div style="text-align: center; color: white; max-width: 800px; margin: 0 auto;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">
                    <i class="fas fa-gift"></i>
                </div>
                <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; color: white;">
                    Masih Ragu? Coba Gratis Sekarang!
                </h2>
                <p style="font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.95; line-height: 1.8;">
                    Nikmati semua fitur premium MikPay secara <strong>GRATIS</strong> selama periode trial. 
                    Tidak perlu kartu kredit, tidak ada komitmen jangka panjang. 
                    Coba sekarang dan rasakan kemudahan mengelola billing & payment MikroTik Anda! Setelah trial, perpanjang langganan lewat menu Langganan (QRIS atau transfer).
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 2rem;">
                    <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                        <div style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">5 Hari</div>
                        <div style="opacity: 0.9;">Trial Gratis</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                        <div style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">100%</div>
                        <div style="opacity: 0.9;">Fitur Premium</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                        <div style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">0 Rupiah</div>
                        <div style="opacity: 0.9;">Tanpa Biaya</div>
                    </div>
                </div>
                <a href="{{ route('register') }}" class="btn btn-primary" style="background: white; color: var(--primary); font-size: 1.125rem; padding: 1.25rem 3rem; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <i class="fas fa-rocket"></i> Mulai Trial Gratis Sekarang
                </a>
                <p style="margin-top: 1.5rem; opacity: 0.8; font-size: 0.9rem;">
                    <i class="fas fa-check-circle"></i> Tidak perlu kartu kredit &bull; 
                    <i class="fas fa-check-circle"></i> Batal kapan saja &bull; 
                    <i class="fas fa-check-circle"></i> Upgrade kapan pun Anda siap
                </p>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    @php
        $headlines = $headlines ?? \App\Services\SubscriptionPackageService::landingHeadlines();
        $pricingPackages = $pricingPackages ?? \App\Services\SubscriptionPackageService::all();
    @endphp
    <section id="pricing" class="section" style="background: white;">
        <div class="container">
            <div class="section-title">
                <h2>{{ $headlines['pricing_title'] }}</h2>
                <p>{{ $headlines['pricing_subtitle'] }}</p>
            </div>
            <p style="text-align:center; max-width:760px; margin: 0 auto 28px; font-size: 0.95rem; color: var(--text-gray); line-height: 1.65;">
                <span style="display:inline-flex; align-items:center; gap:10px; padding: 10px 20px; border-radius: 999px; background: linear-gradient(135deg, rgba(77,68,181,0.08), rgba(99,102,241,0.14)); border: 1px solid rgba(77,68,181,0.22); color: var(--primary); font-weight: 700;">
                    <i class="fas fa-gem"></i> {{ $headlines['premium_tagline'] }}
                </span>
            </p>
            <p style="text-align:center; max-width:640px; margin: -12px auto 28px; font-size: 0.875rem; color: var(--text-gray); line-height: 1.6;">
                <i class="fas fa-info-circle" style="color: var(--primary);"></i>
                Pembayaran langganan: <strong>QRIS</strong> atau transfer manual + unggah bukti (sesuai pengaturan server). Fitur WhatsApp &amp; Telegram dikonfigurasi per akun di pengaturan admin.
            </p>

            <div class="pricing-grid">
                @foreach($pricingPackages as $pkg)
                <div class="pricing-card {{ !empty($pkg['featured']) ? 'featured' : '' }}" @if(!empty($pkg['featured'])) data-ribbon="{{ $pkg['featured_ribbon'] ?? 'POPULER' }}" @endif>
                    <div class="pricing-header">
                        <div class="pricing-name">{{ $pkg['name'] }}</div>
                        <div class="pricing-price">
                            @if(!empty($pkg['original_price']))
                            <span class="pricing-original">Rp {{ number_format((int) $pkg['original_price'], 0, ',', '.') }}</span>
                            @endif
                            Rp {{ number_format((int) $pkg['price'], 0, ',', '.') }}
                        </div>
                        @if(!empty($pkg['duration_label']))
                        <div class="pricing-duration">{{ $pkg['duration_label'] }}</div>
                        @endif
                        @if(!empty($pkg['subtitle']))
                        <div class="pricing-duration">{{ $pkg['subtitle'] }}</div>
                        @endif
                        @if(!empty($pkg['discount_badge']))
                        <div class="pricing-discount">{{ $pkg['discount_badge'] }}</div>
                        @endif
                    </div>

                    <ul class="pricing-features">
                        @foreach($pkg['features'] as $feature)
                        <li><i class="fas fa-check"></i> {{ $feature }}</li>
                        @endforeach
                        @if(!empty($pkg['highlight_footer']))
                        <li style="border-top: 1px dashed #e2e8f0; margin-top: 4px; padding-top: 14px;"><i class="fas fa-star" style="color: var(--secondary);"></i> <strong>{{ $pkg['highlight_footer'] }}</strong></li>
                        @endif
                    </ul>

                    <a href="{{ route('register') }}" class="pricing-button">{{ $pkg['button_label'] ?? 'Pilih Paket' }}</a>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <h3 style="margin-bottom: 1rem; font-size: 1.5rem;">MikPay</h3>
            <p>Sistem Pembayaran & Billing MikroTik</p>
            <p style="margin-top: 1rem; opacity: 0.7;">
                &copy; {{ date('Y') }} MikPay. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Smooth Scroll & Mobile Menu -->
    <script>
        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        const navOverlay = document.getElementById('navOverlay');
        
        function toggleMobileMenu() {
            menuToggle.classList.toggle('active');
            navLinks.classList.toggle('active');
            navOverlay.classList.toggle('active');
            document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
        }
        
        function closeMobileMenu() {
            menuToggle.classList.remove('active');
            navLinks.classList.remove('active');
            navOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        menuToggle.addEventListener('click', toggleMobileMenu);
        navOverlay.addEventListener('click', closeMobileMenu);
        
        // Close menu on window resize (if resized to desktop)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 968) {
                closeMobileMenu();
            }
        });
        
        // Smooth Scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    const offset = 80; // Navbar height
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Close mobile menu after click
                    closeMobileMenu();
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.15)';
            } else {
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            }
        });
    </script>
</body>
</html>
