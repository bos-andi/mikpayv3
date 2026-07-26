@extends('layouts.app')

@section('title', 'Admin Settings')
@section('heading', 'Admin Settings')

@push('styles')
<link rel="stylesheet" href="{{ asset('mikpay/sessions.css') }}">
@endpush

@section('content')
<div class="settings-page-bg"></div>

<div style="padding: 20px; max-width: 1400px; margin: 0 auto;">
    <div class="settings-header">
        <div class="settings-header-icon">
            <i class="fa fa-cogs"></i>
        </div>
        <div class="settings-header-text">
            <h1>Admin Settings</h1>
            <p>Configure your system and manage routers</p>
        </div>
        <div class="settings-header-actions">
            <button class="btn-reload" onclick="location.reload();" title="Reload"><i class="fa fa-refresh"></i></button>
        </div>
    </div>

    @if(session('success'))
    <div style="max-width: 1400px; margin: 0 auto 16px; padding: 0 20px;">
        <div class="alert alert-success" style="margin:0;border-radius:12px;"><i class="fa fa-check-circle"></i> {{ session('success') }}</div>
    </div>
    @endif
    @if(session('error'))
    <div style="max-width: 1400px; margin: 0 auto 16px; padding: 0 20px;">
        <div class="alert alert-danger" style="margin:0;border-radius:12px;"><i class="fa fa-exclamation-circle"></i> {{ session('error') }}</div>
    </div>
    @endif

    <div class="settings-container">
        <div class="settings-left-column">
        <div class="glass-card router-list-card">
            <div class="glass-card-header">
                <div class="glass-card-header-icon">
                    <i class="fa fa-server"></i>
                </div>
                <div class="glass-card-header-text">
                    <h2>Router List</h2>
                    <p>Pilih router untuk masuk ke dashboard, atau kelola konfigurasi router</p>
                </div>
                    <div style="margin-left:auto;">
                        <a href="{{ route('routers.create') }}" class="router-btn btn-open" style="text-decoration:none; text-transform:none;">
                            <i class="fa fa-plus"></i> Add Router
                        </a>
                    </div>
            </div>
            <div class="glass-card-body router-list-body">
                <div class="router-list">
                    @if($routers->isEmpty())
                        <div class="empty-router">
                            <i class="fa fa-server"></i>
                            <p>Belum ada router.<br>Klik <strong>"Tambah Router"</strong> di menu kiri untuk menambahkan.</p>
                            <a href="{{ route('routers.create') }}" class="router-btn btn-open" style="margin-top: 15px; text-decoration: none;"><i class="fa fa-plus"></i> Tambah Router</a>
                        </div>
                    @else
                        @foreach($routers as $r)
                        @php
                            $selectedRouter = $selected_router ?? session('selected_router');
                            $isSelected = $selectedRouter && $selectedRouter === $r->session_name;
                            $hp = method_exists($r, 'getHostParts') ? $r->getHostParts() : ['ip' => '', 'port' => 8728];
                            $expiryAt = null;
                            try {
                                if (!empty($r->expiry_days) && $r->expiry_days > 0 && $r->created_at) {
                                    $expiryAt = $r->created_at->copy()->addDays((int) $r->expiry_days);
                                }
                            } catch (\Throwable $e) {
                                $expiryAt = null;
                            }
                        @endphp
                        <div class="router-card" style="{{ $isSelected ? 'border-color: rgba(34,197,94,0.45); box-shadow: 0 0 0 2px rgba(34,197,94,0.12) inset;' : '' }}">
                            <div class="router-icon">
                                <i class="fa fa-server"></i>
                            </div>
                            <div class="router-info">
                                <h4>{{ $r->router_name ?: $r->session_name }}</h4>
                                <div style="margin-top:8px;">
                                    <div style="display:flex; flex-direction:column; gap:10px; align-items:flex-start;">
                                        <span class="router-session">
                                            <i class="fa fa-tag"></i> {{ $r->session_name }}
                                        </span>

                                        <span class="router-session" title="Status" style="background: {{ $r->status === 'enable' ? 'rgba(34,197,94,0.18)' : 'rgba(239,68,68,0.16)' }};">
                                            <i class="fa {{ $r->status === 'enable' ? 'fa-check-circle' : 'fa-ban' }}"></i>
                                            {{ $r->status === 'enable' ? 'Tersedia' : 'Tidak Aktif' }}
                                        </span>

                                        @if($isSelected)
                                            <span class="router-session" title="Sedang dipakai" style="background: rgba(34,197,94,0.18); border-color: rgba(34,197,94,0.35);">
                                                <i class="fa fa-bolt"></i> Active
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="router-actions" style="flex-direction:column; flex-wrap:nowrap; align-items:center;">
                                <form method="POST" action="{{ route('sessions.connect') }}" style="display:block;">
                                    @csrf
                                    <input type="hidden" name="session" value="{{ $r->session_name }}">
                                    <button type="submit" class="router-btn btn-open" style="min-width:110px; justify-content:center;" {{ $r->status !== 'enable' ? 'disabled' : '' }}><i class="fa fa-play"></i> Open</button>
                                </form>
                                <a href="{{ route('routers.edit', $r) }}" class="router-btn btn-edit" style="min-width:110px; justify-content:center; text-align:center;"><i class="fa fa-cog"></i> Edit</a>
                                @if(strtolower($r->session_name) !== 'mikpay')
                                <form method="POST" action="{{ route('routers.destroy', $r) }}" style="display:block;" onsubmit="return confirm('Hapus router {{ $r->session_name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="router-btn btn-delete" style="min-width:110px; justify-content:center;"><i class="fa fa-trash"></i> Hapus</button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="glass-card" style="margin-top: 16px;">
            <div class="glass-card-header">
                <div class="glass-card-header-icon">
                    <i class="fa fa-sliders"></i>
                </div>
                <div class="glass-card-header-text">
                    <h2>Fitur Tambahan</h2>
                    <p>Pengaturan bisnis dan Telegram bot</p>
                </div>
            </div>
            <div class="glass-card-body">
                <div style="margin: 0 0 16px;">
                    <h4 style="margin: 0 0 6px; font-size: 15px; font-weight: 700; color: #fff;"><i class="fa fa-calendar-check-o"></i> Pengaturan tagihan (tunggakan)</h4>
                    <p style="margin: 0 0 14px; font-size: 12px; color: rgba(255,255,255,0.65); line-height: 1.45;">Tanggal mulai pencatatan di MIKPAY untuk kolom <strong>Tunggakan</strong> di Tagihan PPP — bukan tanggal mulai berlangganan pelanggan.</p>
                    <form method="POST" action="{{ route('admin.business-settings.update') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label"><i class="fa fa-play-circle"></i> Tanggal mulai pencatatan tagihan</label>
                            <input type="date" name="billing_tracking_start_date" class="form-control"
                                value="{{ old('billing_tracking_start_date', $businessSetting->billing_tracking_start_date?->format('Y-m-d') ?? '') }}">
                            <small style="color: rgba(255,255,255,0.55); display: block; margin-top: 8px; font-size: 11px; line-height: 1.4;">Kosongkan = hanya <strong>bulan berjalan</strong>. Contoh go-live 1 Jan 2026 → <code style="background:rgba(0,0,0,0.2);padding:2px 6px;border-radius:4px;">2026-01-01</code></small>
                        </div>

                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label"><i class="fa fa-file-image-o"></i> Kop surat (opsional)</label>
                            <input type="file" name="letterhead_file" class="form-control" accept=".png,.jpg,.jpeg,.webp">
                            <small style="color: rgba(255,255,255,0.55); display: block; margin-top: 8px; font-size: 11px; line-height: 1.4;">Jika belum upload, sistem pakai fallback: <code style="background:rgba(0,0,0,0.2);padding:2px 6px;border-radius:4px;">public/mikpay/img/kop-andidev.png</code></small>
                            @if(!empty($businessSetting->letterhead_image))
                                <div style="margin-top:10px;">
                                    <img src="{{ asset('mikpay/img/' . $businessSetting->letterhead_image) }}" alt="Kop surat saat ini" style="max-width:100%;max-height:140px;border-radius:8px;border:1px solid rgba(255,255,255,0.2);background:#fff;">
                                    <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#fff;margin-top:8px;">
                                        <input type="checkbox" name="letterhead_remove" value="1"> Hapus kop surat saat ini
                                    </label>
                                </div>
                            @endif
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%;"><i class="fa fa-save"></i> Simpan pengaturan</button>
                    </form>
                </div>

                <div style="margin: 8px 0 0; padding-top: 22px; border-top: 1px solid rgba(255,255,255,0.12);">
                    <h4 style="margin: 0 0 6px; font-size: 15px; font-weight: 700; color: #fff;"><i class="fa fa-telegram"></i> Telegram Bot (per user)</h4>
                    <p style="margin: 0 0 14px; font-size: 12px; color: rgba(255,255,255,0.65); line-height: 1.45;">Setiap user bisa pakai bot Telegram sendiri (token + chat ID masing-masing), tanpa `.env`.</p>
                    <form method="POST" action="{{ route('sessions.telegram.update') }}">
                        @csrf

                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-key"></i> Bot Token</label>
                            <input type="password" name="telegram_bot_token" class="form-control"
                                value="{{ old('telegram_bot_token', auth()->user()->telegram_bot_token ?? '') }}"
                                placeholder="123456789:AA....">
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-comments"></i> Chat ID</label>
                            <input type="text" name="telegram_chat_id" class="form-control"
                                value="{{ old('telegram_chat_id', auth()->user()->telegram_chat_id ?? '') }}"
                                placeholder="contoh: 123456789">
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-random"></i> Router untuk aksi bot</label>
                            <select name="telegram_router_session" class="form-control">
                                <option value="">-- Ikuti router aktif saat ini --</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->session_name }}"
                                        {{ old('telegram_router_session', auth()->user()->telegram_router_session ?? ($selected_router ?? '')) === $router->session_name ? 'selected' : '' }}>
                                        {{ $router->session_name }} @if(($router->router_name ?? '') !== '') - {{ $router->router_name }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @php
                            $telegramEnabledValue = old('telegram_enabled', (auth()->user()->telegram_enabled ?? false) ? '1' : '0');
                            $telegramNotifyConnectionValue = old('telegram_notify_connection', (auth()->user()->telegram_notify_connection ?? true) ? '1' : '0');
                        @endphp
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#fff;">
                                <input type="checkbox" name="telegram_enabled" value="1"
                                    {{ $telegramEnabledValue === '1' ? 'checked' : '' }}>
                                Aktifkan Telegram command bot
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#fff; margin-top:8px;">
                                <input type="checkbox" name="telegram_notify_connection" value="1"
                                    {{ $telegramNotifyConnectionValue === '1' ? 'checked' : '' }}>
                                Aktifkan notifikasi PPP ON/OFF (kabel putus / dimatikan)
                            </label>
                            <small style="color: rgba(255,255,255,0.55); display: block; margin-top: 8px; font-size: 11px; line-height: 1.4;">
                                Command tersedia: <code style="background:rgba(0,0,0,0.2);padding:2px 6px;border-radius:4px;">/start</code>,
                                <code style="background:rgba(0,0,0,0.2);padding:2px 6px;border-radius:4px;">/help</code>,
                                <code style="background:rgba(0,0,0,0.2);padding:2px 6px;border-radius:4px;">/telat</code>,
                                <code style="background:rgba(0,0,0,0.2);padding:2px 6px;border-radius:4px;">/isolir nama_secret</code>,
                                <code style="background:rgba(0,0,0,0.2);padding:2px 6px;border-radius:4px;">/buka nama_secret</code>,
                                <code style="background:rgba(0,0,0,0.2);padding:2px 6px;border-radius:4px;">/tunda nama_secret YYYY-MM-DD</code>.
                            </small>
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%; margin-bottom: 10px;"><i class="fa fa-save"></i> Simpan Telegram</button>
                    </form>

                    <form method="POST" action="{{ route('sessions.telegram.test') }}">
                        @csrf
                        <button type="submit" class="btn-primary btn-primary-no-shadow" style="width: 100%; background:#0ea5e9;">
                            <i class="fa fa-paper-plane"></i> Test Kirim Telegram
                        </button>
                    </form>

                    @php
                        $tgStatus = auth()->user()->telegram_test_last_status ?? null;
                        $tgStatusColor = $tgStatus === 'success' ? '#10b981' : ($tgStatus === 'failed' ? '#ef4444' : 'rgba(255,255,255,0.65)');
                        $tgStatusText = $tgStatus === 'success' ? 'Berhasil' : ($tgStatus === 'failed' ? 'Gagal' : 'Belum pernah test');
                    @endphp
                    <div style="margin-top:12px; padding:10px 12px; border-radius:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.14);">
                        <div style="font-size:12px; color:#fff; font-weight:600; margin-bottom:6px;">
                            <i class="fa fa-heartbeat"></i> Status test terakhir:
                            <span style="color: {{ $tgStatusColor }};">{{ $tgStatusText }}</span>
                        </div>
                        <div style="font-size:11px; color:rgba(255,255,255,0.75);">
                            Waktu: {{ auth()->user()->telegram_test_last_at ? auth()->user()->telegram_test_last_at->format('d M Y H:i:s') : '-' }}
                        </div>
                        <div style="font-size:11px; color:rgba(255,255,255,0.75); margin-top:2px;">
                            Detail: {{ auth()->user()->telegram_test_last_message ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="settings-right-column">
            <div class="admin-panel">
                <div class="admin-panel-header">
                    <div class="admin-panel-header-icon">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="admin-panel-header-text">
                        <h3>Admin Account</h3>
                        <p>Pengaturan akses administrator</p>
                    </div>
                </div>
                <div class="admin-panel-body">
                    <form method="POST" action="{{ route('sessions.account.update') }}">
                        @csrf
                        <p style="margin: 0 0 14px; font-size: 12px; color: rgba(255,255,255,0.65); line-height: 1.45;">Login memakai <strong>username</strong> (bukan email). Wajib isi <strong>password saat ini</strong> untuk menyimpan perubahan.</p>

                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-key"></i> Password saat ini</label>
                            <input class="form-control @error('current_password') is-invalid @enderror" type="password" name="current_password" autocomplete="current-password" required>
                            @error('current_password')
                                <small style="color:#fca5a5;display:block;margin-top:6px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-user"></i> Username</label>
                            <input class="form-control @error('username') is-invalid @enderror" type="text" name="username" value="{{ old('username', auth()->user()->username ?? auth()->user()->name) }}" autocomplete="username" required>
                            @error('username')
                                <small style="color:#fca5a5;display:block;margin-top:6px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-lock"></i> Password baru <span style="font-weight:400;opacity:0.75;">(opsional)</span></label>
                            <div class="password-input-wrapper">
                                <input class="form-control @error('password') is-invalid @enderror" id="adminPassNew" type="password" name="password" autocomplete="new-password" placeholder="Kosongkan jika tidak diganti">
                                <button class="password-toggle-btn" type="button" title="Tampilkan/Sembunyikan" onclick="(function(){var x=document.getElementById('adminPassNew'); if(!x) return; x.type = x.type==='password' ? 'text' : 'password';})()">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <small style="color:#fca5a5;display:block;margin-top:6px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-lock"></i> Konfirmasi password baru</label>
                            <input class="form-control" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi password baru">
                        </div>

                        <button type="submit" class="btn-primary btn-primary-no-shadow" style="width: 100%; margin-top: 4px; margin-bottom: 28px;"><i class="fa fa-save"></i> Simpan akun</button>
                    </form>

                    <div class="version-update" style="margin-top: 22px;">
                        <div class="version-label"><i class="fa fa-info-circle"></i> Current Version</div>
                        <div class="version-tag" style="margin-top:10px;">v3.2.0.06-30-2021</div>
                    </div>
                </div>
            </div>

            @if($fonnteSetting)
            <div class="admin-panel" style="margin-top: 16px;">
                <div class="admin-panel-header">
                    <div class="admin-panel-header-icon">
                        <i class="fa fa-whatsapp"></i>
                    </div>
                    <div class="admin-panel-header-text">
                        <h3>WhatsApp (Fonnte)</h3>
                        <p>API token & template reminder per akun</p>
                    </div>
                </div>
                <div class="admin-panel-body">
                    <p style="margin: 0 0 12px; font-size: 12px; color: rgba(255,255,255,0.65); line-height: 1.45;">
                        Integrasi Fonnte dipakai untuk notifikasi tagihan via WhatsApp. Setiap user menyimpan token sendiri (tanpa <code style="background:rgba(0,0,0,0.2);padding:2px 6px;border-radius:4px;">.env</code>).
                    </p>
                    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:14px;">
                        <span style="font-size:12px; color:#fff;">Status:</span>
                        <span style="font-size:12px; font-weight:600; padding:4px 10px; border-radius:8px; background: {{ $fonnteSetting->enabled ? 'rgba(34,197,94,0.18)' : 'rgba(148,163,184,0.2)' }}; border:1px solid rgba(255,255,255,0.14);">
                            {{ $fonnteSetting->enabled ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        @if(filled($fonnteSetting->sender_name))
                            <span style="font-size:11px; color:rgba(255,255,255,0.55);">Pengirim: {{ $fonnteSetting->sender_name }}</span>
                        @endif
                    </div>
                    <a href="{{ route('sessions.fonnte') }}" class="btn-primary btn-primary-no-shadow" style="width:100%; display:inline-flex; justify-content:center; align-items:center; gap:8px; text-decoration:none;">
                        <i class="fa fa-cog"></i> Kelola konfigurasi Fonnte
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
