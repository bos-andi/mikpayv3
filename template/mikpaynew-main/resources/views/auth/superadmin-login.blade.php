<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Super Admin — MIKPAY</title>
    <link rel="icon" href="{{ asset('mikpay/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('mikpay/font-awesome/css/font-awesome.min.css') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0a0a0f;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .sa-login-bg {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(220, 38, 38, 0.18), transparent),
                radial-gradient(ellipse 60% 40% at 100% 50%, rgba(15, 23, 42, 0.9), transparent),
                linear-gradient(180deg, #0a0a0f 0%, #111118 100%);
            pointer-events: none;
            z-index: 0;
        }
        .sa-login-grid {
            position: fixed;
            inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }
        .sa-login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(248, 113, 113, 0.25);
            border-radius: 16px;
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(16px);
            box-shadow: 0 0 0 1px rgba(0,0,0,0.5), 0 24px 48px rgba(0,0,0,0.45), 0 0 80px rgba(220, 38, 38, 0.08);
            overflow: hidden;
        }
        .sa-login-strip {
            background: linear-gradient(90deg, #991b1b, #dc2626, #991b1b);
            padding: 10px 16px;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.2em;
            color: #fecaca;
            text-transform: uppercase;
        }
        .sa-login-body { padding: 32px 28px 28px; }
        .sa-login-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 16px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(220,38,38,0.35), rgba(127,29,29,0.5));
            border: 1px solid rgba(248,113,113,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #fca5a5;
        }
        .sa-login-title {
            text-align: center;
            font-size: 22px;
            font-weight: 800;
            margin: 0 0 4px;
            color: #f8fafc;
        }
        .sa-login-sub {
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
            margin: 0 0 28px;
        }
        .sa-field { margin-bottom: 16px; }
        .sa-field label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 6px;
        }
        .sa-input-wrap { position: relative; }
        .sa-input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            border-radius: 10px;
            border: 1px solid rgba(71, 85, 105, 0.8);
            background: rgba(15, 23, 42, 0.6);
            color: #f1f5f9;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .sa-input:focus {
            border-color: rgba(220, 38, 38, 0.55);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
        }
        .sa-input::placeholder { color: #475569; }
        .sa-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 16px;
        }
        .sa-btn {
            width: 100%;
            margin-top: 8px;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            background: linear-gradient(135deg, #b91c1c, #dc2626);
            color: #fff;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .sa-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.35);
        }
        .sa-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(51, 65, 85, 0.5);
            text-align: center;
        }
        .sa-footer a {
            color: #94a3b8;
            font-size: 13px;
            text-decoration: none;
        }
        .sa-footer a:hover { color: #cbd5e1; text-decoration: underline; }
        .sa-error {
            background: rgba(127, 29, 29, 0.35);
            border: 1px solid rgba(248, 113, 113, 0.35);
            color: #fecaca;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
    <div class="sa-login-bg"></div>
    <div class="sa-login-grid"></div>

    <div class="sa-login-card">
        <div class="sa-login-strip">Restricted area — Super Administrator</div>
        <div class="sa-login-body">
            <div class="sa-login-icon"><i class="fa fa-shield"></i></div>
            <h1 class="sa-login-title">MIKPAY Super Admin</h1>
            <p class="sa-login-sub">Masuk hanya untuk akun dengan hak Super Admin.</p>

            @if($errors->any())
                <div class="sa-error">
                    @foreach($errors->all() as $e)
                        <div>{{ $e }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('superadmin.login.perform') }}" autocomplete="off">
                @csrf
                <div class="sa-field">
                    <label for="sa_user">Username / Email</label>
                    <div class="sa-input-wrap">
                        <i class="fa fa-user sa-input-icon"></i>
                        <input id="sa_user" class="sa-input" type="text" name="user" value="{{ old('user') }}" placeholder="Username atau email" required autofocus>
                    </div>
                </div>
                <div class="sa-field">
                    <label for="sa_pass">Password</label>
                    <div class="sa-input-wrap">
                        <i class="fa fa-lock sa-input-icon"></i>
                        <input id="sa_pass" class="sa-input" type="password" name="pass" placeholder="Password" required>
                    </div>
                </div>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#94a3b8;cursor:pointer;margin-top:4px;">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    Ingat saya di perangkat ini
                </label>
                <button type="submit" class="sa-btn"><i class="fa fa-sign-in" style="margin-right:8px;"></i> Masuk</button>
            </form>

            <div class="sa-footer">
                <a href="{{ route('login') }}"><i class="fa fa-arrow-left"></i> Kembali ke login pengguna</a>
            </div>
        </div>
    </div>
</body>
</html>
