<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar — MIKPAY</title>
    <link rel="icon" href="{{ asset('mikpay/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/font-awesome/css/font-awesome.min.css') }}">
    <style>
        .page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
            background: linear-gradient(160deg, #0f0e23 0%, #1a1744 40%, #16213e 100%); }
        .card {
            width: 100%; max-width: 560px;
            background: rgba(255,255,255,0.06); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 20px;
            padding: 36px 32px; box-shadow: 0 25px 50px rgba(0,0,0,0.35);
        }
        h1 { margin: 0 0 8px; font-size: 22px; color: #fff; text-align: center; font-weight: 800; }
        .sub { margin: 0 0 24px; font-size: 13px; color: rgba(255,255,255,0.6); text-align: center; }
        .field { margin-bottom: 14px; }
        label { display: block; font-size: 12px; color: rgba(255,255,255,0.65); margin-bottom: 6px; font-weight: 600; }
        input, textarea, select {
            width: 100%; padding: 12px 14px; border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.08);
            color: #fff; font-size: 15px; outline: none; box-sizing: border-box;
        }
        input:focus, textarea:focus, select:focus { border-color: rgba(249,115,22,0.55); }
        input::placeholder, textarea::placeholder { color: rgba(255,255,255,0.35); }
        textarea { resize: vertical; min-height: 86px; }
        select option { color: #0f172a; }
        .btn {
            width: 100%; margin-top: 8px; padding: 14px; border: none; border-radius: 12px;
            font-weight: 700; font-size: 15px; cursor: pointer;
            background: linear-gradient(135deg, #f97316, #ea580c); color: #fff;
        }
        .btn:hover { opacity: 0.95; }
        .err { background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.4);
            color: #fecaca; padding: 12px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; }
        .foot { text-align: center; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.08); }
        .foot a { color: rgba(255,255,255,0.55); font-size: 13px; text-decoration: none; }
        .foot a:hover { color: #fff; }
        .hint { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 4px; }
        .check { display: flex; align-items: flex-start; gap: 8px; margin-top: 8px; color: rgba(255,255,255,0.75); font-size: 12px; }
        .check input { width: auto; margin-top: 2px; }
        .terms-link { color: #fdba74; text-decoration: underline; cursor: pointer; }
        .terms-modal-backdrop {
            position: fixed; inset: 0; background: rgba(2, 6, 23, 0.75);
            display: none; align-items: center; justify-content: center; z-index: 1000; padding: 20px;
        }
        .terms-modal-backdrop.show { display: flex; }
        .terms-modal {
            width: 100%; max-width: 760px; background: #0f172a; border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px; box-shadow: 0 20px 50px rgba(0,0,0,0.45); overflow: hidden;
        }
        .terms-header {
            padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,0.08); color: #fff; font-weight: 700; font-size: 15px;
        }
        .terms-content {
            max-height: 55vh; overflow-y: auto; padding: 16px 18px; color: #e2e8f0; font-size: 13px; line-height: 1.65;
            background: rgba(15,23,42,0.75);
        }
        .terms-content h4 { margin: 12px 0 8px; font-size: 13px; color: #fff; }
        .terms-footer {
            padding: 12px 18px; border-top: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; gap: 12px;
        }
        .terms-info { font-size: 12px; color: #93c5fd; }
        .terms-actions { display: flex; gap: 8px; }
        .terms-btn {
            border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.06); color: #e2e8f0;
            padding: 9px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; cursor: pointer;
        }
        .terms-btn.primary { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border: none; }
        .terms-btn[disabled] { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body class="page">
    <div class="card">
        <h1><i class="fa fa-user-plus"></i> Buat akun</h1>
        <p class="sub">Daftar untuk mulai menggunakan MIKPAY</p>

        @if($errors->any())
            <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('register') }}" autocomplete="off">
            @csrf
            <div class="field">
                <label>Nama lengkap</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama Anda" required>
            </div>
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="huruf/angka, tanpa spasi" required pattern="[A-Za-z0-9._-]{4,20}">
                <div class="hint">Dipakai untuk login. Tanpa spasi, contoh: mikpay.user01</div>
            </div>
            <div class="field">
                <label>Nama usaha</label>
                <input type="text" name="business_name" value="{{ old('business_name') }}" placeholder="Contoh: RT/RW Net Maju Jaya" required>
            </div>
            <div class="field">
                <label>Alamat usaha</label>
                <textarea name="business_address" placeholder="Masukkan alamat usaha" required>{{ old('business_address') }}</textarea>
            </div>
            <div class="field">
                <label>Email aktif</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="email@contoh.com" required>
            </div>
            <div class="field">
                <label>No WhatsApp</label>
                <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="08xxxxxxxxxx / +62xxxxxxxxxx" required>
            </div>
            <div class="field">
                <label>Pilih berlangganan</label>
                <select name="subscription_choice" id="subscription_choice" required>
                    <option value="">-- Pilih --</option>
                    <option value="trial" {{ old('subscription_choice') === 'trial' ? 'selected' : '' }}>Trial 5 Hari</option>
                    <option value="paket" {{ old('subscription_choice') === 'paket' ? 'selected' : '' }}>Langsung Paket</option>
                </select>
            </div>
            <div class="field" id="packageField" style="{{ old('subscription_choice') === 'paket' ? '' : 'display:none;' }}">
                <label>Pilih paket</label>
                <select name="package_code" id="package_code">
                    <option value="">-- Pilih paket --</option>
                    @foreach($subscriptionPackages ?? [] as $pkey => $pkg)
                        <option value="{{ $pkey }}" {{ old('package_code') === $pkey ? 'selected' : '' }}>
                            {{ $pkg['name'] }} — Rp {{ number_format((int) ($pkg['price'] ?? 0), 0, ',', '.') }}
                            @if(!empty($pkg['original_price']) && (int) $pkg['original_price'] > (int) ($pkg['price'] ?? 0))
                                (hemat vs Rp {{ number_format((int) $pkg['original_price'], 0, ',', '.') }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="Minimal 8 karakter" required>
            </div>
            <div class="field">
                <label>Ulangi password</label>
                <input type="password" name="password_confirmation" placeholder="Sama dengan password" required>
            </div>
            <label class="check">
                <input type="checkbox" name="agree_terms" id="agree_terms" value="1" {{ old('agree_terms') ? 'checked' : '' }}>
                <span>
                    Saya setuju dengan
                    <a href="#" id="openTermsModal" class="terms-link">syarat layanan dan kebijakan privasi</a>.
                    <br>
                    <small class="hint" id="termsHint">Baca dan scroll sampai bawah untuk mengaktifkan centang.</small>
                </span>
            </label>
            <button type="submit" class="btn">Daftar</button>
        </form>

        <div class="foot">
            <a href="{{ route('login') }}">Sudah punya akun? Masuk</a>
            &nbsp;·&nbsp;
            <a href="{{ route('index') }}">Beranda</a>
        </div>
    </div>

    <div class="terms-modal-backdrop" id="termsModalBackdrop" aria-hidden="true">
        <div class="terms-modal" role="dialog" aria-modal="true" aria-labelledby="termsModalTitle">
            <div class="terms-header" id="termsModalTitle">Syarat Layanan & Kebijakan Privasi</div>
            <div class="terms-content" id="termsContent">
                <h4>1. Persetujuan Penggunaan Layanan</h4>
                <p>Dengan membuat akun MIKPAY, Anda menyetujui penggunaan layanan sesuai ketentuan yang berlaku. Anda bertanggung jawab atas data dan aktivitas akun Anda.</p>
                <h4>2. Data Akun</h4>
                <p>Data yang Anda masukkan saat registrasi, termasuk nama, data usaha, email, dan nomor WhatsApp, digunakan untuk proses layanan, notifikasi, serta dukungan teknis.</p>
                <h4>3. Langganan & Trial</h4>
                <p>Jika memilih trial, akun Anda akan mendapatkan masa trial 5 hari. Jika memilih paket berbayar, status langganan akan aktif setelah proses pembayaran/aktivasi berhasil.</p>
                <h4>4. Keamanan Akun</h4>
                <p>Anda wajib menjaga kerahasiaan username dan password. Segala aktivitas yang terjadi melalui akun Anda menjadi tanggung jawab pemilik akun.</p>
                <h4>5. Penggunaan yang Dilarang</h4>
                <p>Dilarang menggunakan layanan untuk aktivitas ilegal, penipuan, spam, atau tindakan yang merugikan pihak lain dan infrastruktur sistem.</p>
                <h4>6. Privasi Data</h4>
                <p>Kami berkomitmen menjaga kerahasiaan data Anda dan tidak membagikan data pribadi tanpa izin, kecuali diwajibkan oleh peraturan perundang-undangan.</p>
                <h4>7. Perubahan Ketentuan</h4>
                <p>Ketentuan layanan dapat diperbarui sewaktu-waktu. Versi terbaru akan menjadi acuan penggunaan layanan MIKPAY selanjutnya.</p>
                <p><strong>Dengan melanjutkan, Anda menyatakan telah membaca dan memahami seluruh ketentuan di atas.</strong></p>
            </div>
            <div class="terms-footer">
                <span class="terms-info" id="termsScrollInfo">Scroll hingga bagian paling bawah untuk melanjutkan.</span>
                <div class="terms-actions">
                    <button type="button" class="terms-btn" id="closeTermsModal">Tutup</button>
                    <button type="button" class="terms-btn primary" id="acceptTermsBtn" disabled>Saya Sudah Baca</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const subscriptionChoice = document.getElementById('subscription_choice');
        const packageField = document.getElementById('packageField');
        const packageCode = document.getElementById('package_code');
        const openTermsModal = document.getElementById('openTermsModal');
        const closeTermsModal = document.getElementById('closeTermsModal');
        const termsModalBackdrop = document.getElementById('termsModalBackdrop');
        const termsContent = document.getElementById('termsContent');
        const acceptTermsBtn = document.getElementById('acceptTermsBtn');
        const agreeTermsCheckbox = document.getElementById('agree_terms');
        const termsHint = document.getElementById('termsHint');
        const termsScrollInfo = document.getElementById('termsScrollInfo');
        let termsHasBeenAccepted = {{ old('agree_terms') ? 'true' : 'false' }};

        function togglePackageField() {
            const showPackage = subscriptionChoice.value === 'paket';
            packageField.style.display = showPackage ? '' : 'none';
            packageCode.required = showPackage;
            if (!showPackage) {
                packageCode.value = '';
            }
        }

        subscriptionChoice.addEventListener('change', togglePackageField);
        togglePackageField();

        function openModal() {
            termsModalBackdrop.classList.add('show');
            termsModalBackdrop.setAttribute('aria-hidden', 'false');
        }

        function closeModal() {
            termsModalBackdrop.classList.remove('show');
            termsModalBackdrop.setAttribute('aria-hidden', 'true');
        }

        function isScrolledToBottom(el) {
            return el.scrollTop + el.clientHeight >= el.scrollHeight - 4;
        }

        function updateScrollState() {
            if (isScrolledToBottom(termsContent)) {
                acceptTermsBtn.disabled = false;
                termsScrollInfo.textContent = 'Sudah sampai bawah. Anda bisa melanjutkan.';
            }
        }

        function applyTermsAcceptanceState() {
            if (termsHasBeenAccepted) {
                agreeTermsCheckbox.checked = true;
            }
            termsHint.textContent = termsHasBeenAccepted
                ? 'S&K sudah dibaca. Silakan centang/lanjutkan pendaftaran.'
                : 'Baca dan scroll sampai bawah untuk mengaktifkan centang.';
        }

        openTermsModal.addEventListener('click', function (event) {
            event.preventDefault();
            openModal();
        });

        closeTermsModal.addEventListener('click', closeModal);

        termsModalBackdrop.addEventListener('click', function (event) {
            if (event.target === termsModalBackdrop) {
                closeModal();
            }
        });

        termsContent.addEventListener('scroll', updateScrollState);

        acceptTermsBtn.addEventListener('click', function () {
            termsHasBeenAccepted = true;
            applyTermsAcceptanceState();
            agreeTermsCheckbox.checked = true;
            closeModal();
        });

        agreeTermsCheckbox.addEventListener('click', function (event) {
            if (!termsHasBeenAccepted) {
                event.preventDefault();
                openModal();
            }
        });

        if (!termsHasBeenAccepted) {
            acceptTermsBtn.disabled = true;
            termsScrollInfo.textContent = 'Scroll hingga bagian paling bawah untuk melanjutkan.';
        }

        applyTermsAcceptanceState();
    </script>
</body>
</html>
