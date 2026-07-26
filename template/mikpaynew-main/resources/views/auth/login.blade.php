<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - MIKPAY</title>
    <link rel="icon" href="{{ asset('mikpay/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/font-awesome/css/font-awesome.min.css') }}">
    <style>
        .login-page-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(160deg, #0f0e23 0%, #1a1744 40%, #16213e 100%);
            position: relative;
            overflow: hidden;
            padding: 24px;
        }
        .login-page-wrapper::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 70%;
            height: 140%;
            background: radial-gradient(ellipse, rgba(99, 102, 241, 0.15) 0%, transparent 60%);
            pointer-events: none;
        }
        .login-page-wrapper::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 50%;
            height: 80%;
            background: radial-gradient(ellipse, rgba(249, 115, 22, 0.08) 0%, transparent 60%);
            pointer-events: none;
        }
        .login-card-modern {
            display: flex;
            flex-direction: row;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255,255,255,0.03) inset;
            width: 100%;
            max-width: 520px;
            overflow: hidden;
            position: relative;
            z-index: 10;
        }
        @media (max-width: 600px) {
            .login-card-modern { flex-direction: column; max-width: 400px; }
        }
        .login-brand-panel {
            flex: 0 0 44%;
            padding: 36px 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, rgba(249, 115, 22, 0.12) 0%, rgba(99, 102, 241, 0.08) 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }
        @media (max-width: 600px) {
            .login-brand-panel { padding: 28px 22px 22px; border-right: none; border-bottom: 1px solid rgba(255, 255, 255, 0.06); }
        }
        /* Logo di kiri, teks di kanan — tidak saling menutupi */
        .login-brand-inner {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 16px;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        .login-logo-wrapper {
            width: 64px;
            height: 64px;
            flex-shrink: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.95);
        }
        .login-logo-img {
            display: block;
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            padding: 10px;
            box-sizing: border-box;
        }
        .login-brand-text {
            flex: 1;
            min-width: 0;
            text-align: left;
        }
        .login-brand-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 3px;
            color: #FFFFFF;
            margin: 0;
            line-height: 1.2;
            word-wrap: break-word;
        }
        .login-brand-tagline {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.6);
            margin: 6px 0 0;
            letter-spacing: 1px;
            text-transform: uppercase;
            line-height: 1.45;
        }
        .login-form-panel {
            flex: 1;
            padding: 40px 36px 36px;
        }
        @media (max-width: 600px) {
            .login-form-panel { padding: 32px 28px 28px; }
        }
        .login-form-title {
            font-size: 18px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.95);
            margin: 0 0 24px;
        }
        .login-input-group {
            position: relative;
            margin-bottom: 16px;
        }
        .login-input {
            width: 100%;
            padding: 14px 18px 14px 46px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 15px;
            color: #FFFFFF;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s, background 0.2s;
        }
        .login-input::placeholder { color: rgba(255, 255, 255, 0.4); }
        .login-input:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(249, 115, 22, 0.5);
        }
        .login-input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.45);
            font-size: 16px;
        }
        .login-input:focus + .login-input-icon,
        .login-input-group:focus-within .login-input-icon { color: rgba(249, 115, 22, 0.9); }
        .login-btn-modern {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            color: #FFFFFF;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .login-btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(249, 115, 22, 0.35);
        }
        .login-error-message {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 16px;
            color: #fecaca;
            font-size: 13px;
            text-align: center;
        }
        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }
        .login-footer-text {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
        }
        .login-register-box {
            text-align: center;
            margin-top: 22px;
            padding: 16px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .login-register-box p {
            margin: 0 0 10px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.55);
        }
        .login-register-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            background: rgba(99, 102, 241, 0.25);
            border: 1px solid rgba(129, 140, 248, 0.45);
            transition: background 0.2s, transform 0.2s;
        }
        .login-register-btn:hover {
            background: rgba(99, 102, 241, 0.4);
            transform: translateY(-1px);
        }
        .text-danger { color: #fca5a5; font-size: 12px; margin-top: 4px; display: block; }
    </style>
</head>
<body>
    <div class="login-page-wrapper">
        <div class="login-card-modern">
            <div class="login-brand-panel">
                <div class="login-brand-inner">
                    <div class="login-logo-wrapper">
                        <img src="{{ asset('mikpay/img/favicon.png') }}" alt="" class="login-logo-img" width="64" height="64" onerror="this.parentElement.style.display='none'">
                    </div>
                    <div class="login-brand-text">
                        <h1 class="login-brand-name">MIKPAY</h1>
                        <p class="login-brand-tagline">MikroTik Management System</p>
                    </div>
                </div>
            </div>
            <div class="login-form-panel">
                <h2 class="login-form-title">Masuk ke akun</h2>
                @if(session('error'))
                    <div class="login-error-message" style="margin-bottom:14px;padding:10px 12px;border-radius:10px;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.4);color:#fecaca;font-size:13px;">
                        <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif
                <form method="POST" action="{{ route('login') }}" autocomplete="off">
                    @csrf
                    <div class="login-input-group">
                        <input type="text" name="user" id="user" class="login-input" placeholder="Username" value="{{ old('user') }}" required autofocus>
                        <i class="fa fa-user login-input-icon"></i>
                    </div>
                    @error('user')<span class="text-danger"><i class="fa fa-exclamation-circle"></i> {{ $message }}</span>@enderror
                    <div class="login-input-group">
                        <input type="password" name="pass" class="login-input" placeholder="Password" required>
                        <i class="fa fa-lock login-input-icon"></i>
                    </div>
                    <button type="submit" name="login" class="login-btn-modern"><i class="fa fa-sign-in" style="margin-right: 8px;"></i> Login</button>
                </form>
                <div class="login-register-box">
                    <p>Belum punya akun?</p>
                    <a href="{{ route('register') }}" class="login-register-btn">
                        <i class="fa fa-user-plus"></i> Daftar sekarang
                    </a>
                </div>
                <div class="login-footer">
                    <p class="login-footer-text"><a href="{{ route('index') }}" style="color: rgba(255,255,255,0.45); text-decoration: none;">Beranda</a> · Powered by MIKPAY &copy; {{ date('Y') }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
