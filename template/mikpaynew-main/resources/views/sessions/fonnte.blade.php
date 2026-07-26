@extends('layouts.app')

@section('title', 'WhatsApp (Fonnte)')
@section('heading', 'Admin Settings')

@push('styles')
<link rel="stylesheet" href="{{ asset('mikpay/sessions.css') }}">
@endpush

@section('content')
<div class="settings-page-bg"></div>

<div class="fonnte-page" style="padding: 20px; max-width: 900px; margin: 0 auto;">
    <div class="settings-header">
        <div class="settings-header-icon">
            <i class="fa fa-whatsapp"></i>
        </div>
        <div class="settings-header-text">
            <h1>WhatsApp API (Fonnte)</h1>
            <p>Konfigurasi per akun — token & template reminder tagihan</p>
        </div>
        <div class="settings-header-actions">
            <a href="{{ route('sessions') }}" class="btn-reload" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px; padding:8px 14px; width:auto; min-width:auto;" title="Kembali">
                <i class="fa fa-arrow-left"></i> Admin Settings
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="margin: 0 auto 16px;">
        <div class="alert alert-success" style="margin:0;border-radius:12px;"><i class="fa fa-check-circle"></i> {{ session('success') }}</div>
    </div>
    @endif
    @if(session('error'))
    <div style="margin: 0 auto 16px;">
        <div class="alert alert-danger" style="margin:0;border-radius:12px;"><i class="fa fa-exclamation-circle"></i> {{ session('error') }}</div>
    </div>
    @endif

    <div class="glass-card">
        <div class="glass-card-header">
            <div class="glass-card-header-icon">
                <i class="fa fa-cog"></i>
            </div>
            <div class="glass-card-header-text">
                <h2>Pengaturan Fonnte</h2>
                <p>Dapatkan API token di <a href="https://fonnte.com" target="_blank" rel="noopener" style="color:#7dd3fc;">fonnte.com</a> — setiap user memakai token sendiri.</p>
            </div>
        </div>
        <div class="glass-card-body">
            <form method="POST" action="{{ route('sessions.fonnte.update') }}">
                @csrf

                @php
                    $enabledVal = old('enabled', $fonnte->enabled ? '1' : '0');
                @endphp

                <label class="fonnte-switch fonnte-switch--hero">
                    <input type="checkbox" name="enabled" value="1" class="fonnte-switch__input" {{ $enabledVal === '1' ? 'checked' : '' }}>
                    <span class="fonnte-switch__track" aria-hidden="true"></span>
                    <span class="fonnte-switch__body">
                        <span class="fonnte-switch__title">Aktifkan integrasi Fonnte</span>
                        <span class="fonnte-switch__hint">Hubungkan akun dengan API WhatsApp untuk pengingat tagihan otomatis.</span>
                    </span>
                </label>

                <div class="form-group" style="margin-top: 22px;">
                    <label class="form-label"><i class="fa fa-key"></i> API token</label>
                    <input type="password" name="api_token" class="form-control fonnte-field" value="" autocomplete="off" placeholder="Tempel token baru di sini (kosongkan jika tidak diubah)">
                    @if(filled($fonnte->api_token))
                        <p class="fonnte-token-note"><i class="fa fa-lock" style="opacity:0.7;margin-right:6px;"></i>Token sudah tersimpan dengan aman. Isi field di atas hanya jika ingin mengganti token.</p>
                    @endif
                    <div class="fonnte-token-actions">
                        <label class="fonnte-switch fonnte-switch--compact fonnte-switch--danger">
                            <input type="checkbox" name="api_token_clear" value="1" class="fonnte-switch__input">
                            <span class="fonnte-switch__track" aria-hidden="true"></span>
                            <span class="fonnte-switch__body">
                                <span class="fonnte-switch__title">Hapus token yang tersimpan</span>
                                <span class="fonnte-switch__hint">Aktifkan hanya jika ingin menghapus token dari server.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="fonnte-nested" style="margin-bottom: 16px;">
                    <p style="margin: 0 0 10px; font-size: 12px; font-weight: 700; color: #fff;">
                        <i class="fa fa-paper-plane-o" style="margin-right:8px;opacity:0.85;"></i>Test kirim WhatsApp
                    </p>
                    <div class="fonnte-num-grid" style="grid-template-columns: 1.5fr auto; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 13px;">Nomor WhatsApp test</label>
                            <input type="text" name="test_target" class="form-control fonnte-field @error('test_target') is-invalid @enderror"
                                value="{{ old('test_target') }}" placeholder="08xxxxxxxxxx / 628xxxxxxxxxx / +628xxxxxxxxxx" autocomplete="off">
                            @error('test_target')
                                <small style="color:#fca5a5;display:block;margin-top:6px;">{{ $message }}</small>
                            @enderror
                        </div>
                        <button type="submit" formaction="{{ route('sessions.fonnte.test') }}" class="btn-save" style="width:auto;min-width:200px;">
                            <i class="fa fa-paper-plane"></i> Kirim Test WhatsApp
                        </button>
                    </div>
                    <p style="margin: 10px 0 0; font-size: 11px; color: rgba(255,255,255,0.58);">
                        Tombol ini mengirim pesan uji coba ke nomor tujuan memakai token Fonnte yang tersimpan.
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa fa-user"></i> Nama pengirim (sender)</label>
                    <input type="text" name="sender_name" class="form-control fonnte-field @error('sender_name') is-invalid @enderror"
                        value="{{ old('sender_name', $fonnte->sender_name) }}" maxlength="255" placeholder="contoh: MIKPAY WiFi">
                    @error('sender_name')
                        <small style="color:#fca5a5;display:block;margin-top:6px;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="fonnte-section">
                    <div class="fonnte-section__head">
                        <div class="fonnte-section__icon fonnte-section__icon--time"><i class="fa fa-clock-o"></i></div>
                        <div class="fonnte-section__titles">
                            <h3>Jadwal kirim otomatis</h3>
                            <p>Pilih jenis pengingat yang dikirim ke pelanggan via WhatsApp.</p>
                        </div>
                    </div>
                    <div class="fonnte-option-list">
                        <label class="fonnte-switch fonnte-switch--compact">
                            <input type="checkbox" name="auto_send_h3" value="1" class="fonnte-switch__input" {{ old('auto_send_h3', $fonnte->auto_send_h3 ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="fonnte-switch__track" aria-hidden="true"></span>
                            <span class="fonnte-switch__body">
                                <span class="fonnte-switch__title">Pengingat H-3</span>
                                <span class="fonnte-switch__hint">3 hari sebelum jatuh tempo (tidak dipakai jika pelanggan punya janji bayar periode ini)</span>
                            </span>
                        </label>
                        <label class="fonnte-switch fonnte-switch--compact">
                            <input type="checkbox" name="auto_send_h0" value="1" class="fonnte-switch__input" {{ old('auto_send_h0', $fonnte->auto_send_h0 ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="fonnte-switch__track" aria-hidden="true"></span>
                            <span class="fonnte-switch__body">
                                <span class="fonnte-switch__title">Pengingat H-0</span>
                                <span class="fonnte-switch__hint">Pada hari jatuh tempo (janji bayar: hanya di hari tanggal janji; teks pakai template Janji bayar jika diisi)</span>
                            </span>
                        </label>
                        <label class="fonnte-switch fonnte-switch--compact">
                            <input type="checkbox" name="auto_send_overdue" value="1" class="fonnte-switch__input" {{ old('auto_send_overdue', $fonnte->auto_send_overdue ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="fonnte-switch__track" aria-hidden="true"></span>
                            <span class="fonnte-switch__body">
                                <span class="fonnte-switch__title">Pengingat tunggakan</span>
                                <span class="fonnte-switch__hint">Setelah melewati jatuh tempo (tidak dipakai jika pelanggan punya janji bayar periode ini)</span>
                            </span>
                        </label>
                    </div>
                    <p style="margin: 10px 0 0; font-size: 11px; line-height: 1.45; color: rgba(255,255,255,0.5);">
                        <strong style="color:rgba(255,255,255,0.75);">Janji bayar:</strong> WhatsApp otomatis hanya pada tanggal janji (satu kali). Pakai template <strong style="color:rgba(255,255,255,0.75);">Janji bayar</strong> di bawah; jika dikosongkan, dipakai template H-0. H-3 dan pengingat tunggakan tidak dikirim selama janji periode itu masih tercatat.
                    </p>

                    <div class="fonnte-nested" style="margin-top: 16px;">
                        <p style="margin: 0 0 12px; font-size: 12px; font-weight: 700; color: #fff;"><i class="fa fa-clock-o" style="margin-right:8px;opacity:0.85;"></i>Rentang jam pengiriman (harian)</p>
                        <p style="margin: 0 0 14px; font-size: 11px; line-height: 1.45; color: rgba(255,255,255,0.55);">
                            Pengingat otomatis hanya akan dijalankan saat jam server berada di antara <strong style="color:rgba(255,255,255,0.85);">mulai</strong> dan <strong style="color:rgba(255,255,255,0.85);">selesai</strong> (format 24 jam). Zona waktu: <code style="background:rgba(0,0,0,0.25);padding:2px 6px;border-radius:4px;">{{ config('app.timezone') }}</code> — sesuaikan di <code style="background:rgba(0,0,0,0.25);padding:2px 6px;border-radius:4px;">APP_TIMEZONE</code> di <code style="background:rgba(0,0,0,0.25);padding:2px 6px;border-radius:4px;">.env</code> bila perlu.
                            <span style="display:block;margin-top:8px;color:rgba(167,243,208,0.95);"><i class="fa fa-clock-o"></i> Pengiriman otomatis dijalankan oleh <strong>scheduler Laravel</strong> (perintah <code style="background:rgba(0,0,0,0.25);padding:2px 6px;border-radius:4px;">mikpay:fonnte-reminders</code> setiap <strong>15 menit</strong>), hanya jika jam sekarang masuk rentang di atas. Pastikan di server ada cron: <code style="background:rgba(0,0,0,0.25);padding:2px 6px;border-radius:4px;">* * * * * php /path/to/artisan schedule:run</code> atau proses <code style="background:rgba(0,0,0,0.25);padding:2px 6px;border-radius:4px;">php artisan schedule:work</code> berjalan terus.</span>
                        </p>
                        <div class="fonnte-num-grid">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 13px;">Jam mulai kirim</label>
                                <input type="time" name="send_window_start" class="form-control fonnte-field @error('send_window_start') is-invalid @enderror"
                                    value="{{ old('send_window_start', $fonnte->send_window_start ?? '08:00') }}" step="60">
                                @error('send_window_start')
                                    <small style="color:#fca5a5;">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 13px;">Jam selesai kirim</label>
                                <input type="time" name="send_window_end" class="form-control fonnte-field @error('send_window_end') is-invalid @enderror"
                                    value="{{ old('send_window_end', $fonnte->send_window_end ?? '20:00') }}" step="60">
                                @error('send_window_end')
                                    <small style="color:#fca5a5;">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <p style="margin: 12px 0 0; font-size: 10px; color: rgba(255,255,255,0.45);">Jika jam selesai &lt; jam mulai, sistem menganggap rentang melewati tengah malam (contoh 22:00–06:00).</p>
                    </div>
                </div>

                <div class="fonnte-section">
                    <div class="fonnte-section__head">
                        <div class="fonnte-section__icon fonnte-section__icon--shield"><i class="fa fa-shield"></i></div>
                        <div class="fonnte-section__titles">
                            <h3>Antispam &amp; throttling</h3>
                            <p>Delay acak dan batas frekuensi agar pengiriman tetap aman.</p>
                        </div>
                    </div>
                    <label class="fonnte-switch fonnte-switch--compact" style="margin-bottom: 4px;">
                        <input type="checkbox" name="antispam_enabled" value="1" class="fonnte-switch__input" {{ old('antispam_enabled', $fonnte->antispam_enabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <span class="fonnte-switch__track" aria-hidden="true"></span>
                        <span class="fonnte-switch__body">
                            <span class="fonnte-switch__title">Aktifkan antispam</span>
                            <span class="fonnte-switch__hint">Terapkan delay acak dan limit per jam / per hari</span>
                        </span>
                    </label>
                    <div class="fonnte-nested">
                        <div class="fonnte-num-grid">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 13px;">Delay min (detik)</label>
                                <input type="number" name="antispam_delay_min" class="form-control fonnte-field @error('antispam_delay_min') is-invalid @enderror"
                                    value="{{ old('antispam_delay_min', $fonnte->antispam_delay_min) }}" min="0" max="600">
                                @error('antispam_delay_min')
                                    <small style="color:#fca5a5;">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 13px;">Delay max (detik)</label>
                                <input type="number" name="antispam_delay_max" class="form-control fonnte-field @error('antispam_delay_max') is-invalid @enderror"
                                    value="{{ old('antispam_delay_max', $fonnte->antispam_delay_max) }}" min="0" max="600">
                                @error('antispam_delay_max')
                                    <small style="color:#fca5a5;">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="fonnte-num-grid" style="margin-top: 14px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 13px;">Limit per jam</label>
                                <input type="number" name="antispam_hourly_limit" class="form-control fonnte-field @error('antispam_hourly_limit') is-invalid @enderror"
                                    value="{{ old('antispam_hourly_limit', $fonnte->antispam_hourly_limit) }}" min="1" max="5000">
                                @error('antispam_hourly_limit')
                                    <small style="color:#fca5a5;">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 13px;">Limit per hari</label>
                                <input type="number" name="antispam_daily_limit" class="form-control fonnte-field @error('antispam_daily_limit') is-invalid @enderror"
                                    value="{{ old('antispam_daily_limit', $fonnte->antispam_daily_limit) }}" min="1" max="50000">
                                @error('antispam_daily_limit')
                                    <small style="color:#fca5a5;">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <label class="fonnte-switch fonnte-switch--compact" style="margin-top: 16px;">
                            <input type="checkbox" name="antispam_randomize_message" value="1" class="fonnte-switch__input" {{ old('antispam_randomize_message', $fonnte->antispam_randomize_message ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="fonnte-switch__track" aria-hidden="true"></span>
                            <span class="fonnte-switch__body">
                                <span class="fonnte-switch__title">Variasikan teks pesan</span>
                                <span class="fonnte-switch__hint">Randomisasi agar isi pesan tidak identik berulang</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="fonnte-section">
                    <div class="fonnte-section__head">
                        <div class="fonnte-section__icon fonnte-section__icon--doc"><i class="fa fa-file-text-o"></i></div>
                        <div class="fonnte-section__titles">
                            <h3>Template pesan</h3>
                            <p>Susun teks untuk tiap jenis notifikasi. Variasi salam &amp; penutup memakai format JSON array.</p>
                        </div>
                    </div>

                    <div class="fonnte-placeholder-row">
                        <p class="fonnte-placeholder-row__label">Placeholder</p>
                        <span class="fonnte-chip">{nama}</span>
                        <span class="fonnte-chip">{nominal}</span>
                        <span class="fonnte-chip">{tanggal}</span>
                        <span class="fonnte-chip">{hari}</span>
                        <span class="fonnte-chip">{pengirim}</span>
                    </div>

                    <div class="fonnte-policy-note">
                        <i class="fa fa-info-circle" aria-hidden="true"></i>
                        <div>
                            <strong>Apakah salam &amp; penutup masih perlu?</strong>
                            <p>Untuk alur yang <em>paling sederhana</em>, cukup isi template <strong>H-3</strong>, <strong>H-0</strong>, dan <strong>tunggakan</strong> sebagai satu pesan lengkap (sudah boleh berisi salam dan <code>{pengirim}</code> di akhir). Kolom salam/penutup JSON di bawah ini <strong>belum digabung otomatis</strong> ke pesan oleh sistem — data hanya disimpan untuk jika nanti ingin dipakai variasi teks antispam. Kalau template utama Anda sudah lengkap, Anda bisa mengosongkan JSON atau mengabaikannya.</p>
                        </div>
                    </div>

                    @php
                        $tplEx = \App\Models\FonnteSetting::defaultTemplatePayload();
                        $exGreetingsJson = json_encode($tplEx['template_greetings'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        $exClosingsJson = json_encode($tplEx['template_closings'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    @endphp

                    <div class="fonnte-template-grid-2">
                        <div class="fonnte-template-card fonnte-template-card--json">
                            <div class="fonnte-template-card__top">
                                <div class="fonnte-template-card__badge"><i class="fa fa-comments-o"></i></div>
                                <div>
                                    <h4 class="fonnte-template-card__title">Salam pembuka</h4>
                                    <p class="fonnte-template-card__desc">JSON array — untuk variasi salam acak (opsional; lihat catatan di bawah).</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <textarea name="template_greetings_json" class="form-control fonnte-field fonnte-template-area fonnte-template-area--json" rows="5" aria-label="Salam pembuka JSON" placeholder='["Assalamualaikum","Halo"]'>{{ old('template_greetings_json', $fonnte->template_greetings ? json_encode($fonnte->template_greetings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '') }}</textarea>
                                @error('template_greetings_json')
                                    <small style="color:#fca5a5;display:block;margin-top:6px;">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="fonnte-example-block">
                                <p class="fonnte-example-label"><i class="fa fa-lightbulb-o"></i> Contoh isian</p>
                                <pre class="fonnte-example-pre">{{ $exGreetingsJson }}</pre>
                            </div>
                        </div>
                        <div class="fonnte-template-card fonnte-template-card--json">
                            <div class="fonnte-template-card__top">
                                <div class="fonnte-template-card__badge"><i class="fa fa-check-circle-o"></i></div>
                                <div>
                                    <h4 class="fonnte-template-card__title">Penutup</h4>
                                    <p class="fonnte-template-card__desc">JSON array — untuk variasi penutup acak (opsional; lihat catatan di bawah).</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <textarea name="template_closings_json" class="form-control fonnte-field fonnte-template-area fonnte-template-area--json" rows="5" aria-label="Penutup JSON" placeholder='["Terima kasih","Hormat kami"]'>{{ old('template_closings_json', $fonnte->template_closings ? json_encode($fonnte->template_closings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '') }}</textarea>
                                @error('template_closings_json')
                                    <small style="color:#fca5a5;display:block;margin-top:6px;">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="fonnte-example-block">
                                <p class="fonnte-example-label"><i class="fa fa-lightbulb-o"></i> Contoh isian</p>
                                <pre class="fonnte-example-pre">{{ $exClosingsJson }}</pre>
                            </div>
                        </div>
                    </div>

                    <div class="fonnte-template-card fonnte-template-card--reminder">
                        <div class="fonnte-template-card__top">
                            <div class="fonnte-template-card__badge"><i class="fa fa-bell-o"></i></div>
                            <div>
                                <h4 class="fonnte-template-card__title">Pengingat sebelum jatuh tempo (H-3)</h4>
                                <p class="fonnte-template-card__desc">Dikirim beberapa hari sebelum tanggal bayar. Pakai *teks* untuk cetak tebal di WhatsApp.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <textarea name="template_reminder" class="form-control fonnte-field fonnte-template-area" rows="5" aria-label="Template pengingat H-3">{{ old('template_reminder', $fonnte->template_reminder) }}</textarea>
                        </div>
                        <div class="fonnte-example-block">
                            <p class="fonnte-example-label"><i class="fa fa-lightbulb-o"></i> Contoh isian</p>
                            <pre class="fonnte-example-pre">{{ $tplEx['template_reminder'] }}</pre>
                        </div>
                    </div>

                    <div class="fonnte-template-card fonnte-template-card--due">
                        <div class="fonnte-template-card__top">
                            <div class="fonnte-template-card__badge"><i class="fa fa-calendar-check-o"></i></div>
                            <div>
                                <h4 class="fonnte-template-card__title">Jatuh tempo hari ini (H-0)</h4>
                                <p class="fonnte-template-card__desc">Pesan pada hari pelanggan harus membayar.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <textarea name="template_due_today" class="form-control fonnte-field fonnte-template-area" rows="5" aria-label="Template jatuh tempo hari ini">{{ old('template_due_today', $fonnte->template_due_today) }}</textarea>
                        </div>
                        <div class="fonnte-example-block">
                            <p class="fonnte-example-label"><i class="fa fa-lightbulb-o"></i> Contoh isian</p>
                            <pre class="fonnte-example-pre">{{ $tplEx['template_due_today'] }}</pre>
                        </div>
                    </div>

                    <div class="fonnte-template-card fonnte-template-card--due" style="border-color: rgba(125, 211, 252, 0.35);">
                        <div class="fonnte-template-card__top">
                            <div class="fonnte-template-card__badge"><i class="fa fa-handshake-o"></i></div>
                            <div>
                                <h4 class="fonnte-template-card__title">Janji bayar (hari tanggal janji)</h4>
                                <p class="fonnte-template-card__desc">Hanya untuk pelanggan yang punya janji bayar periode ini — dikirim sekali di hari janji. Kosongkan untuk memakai template H-0 di atas.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <textarea name="template_janji_bayar" class="form-control fonnte-field fonnte-template-area" rows="5" aria-label="Template janji bayar">{{ old('template_janji_bayar', $fonnte->template_janji_bayar) }}</textarea>
                        </div>
                        <div class="fonnte-example-block">
                            <p class="fonnte-example-label"><i class="fa fa-lightbulb-o"></i> Contoh isian</p>
                            <pre class="fonnte-example-pre">{{ $tplEx['template_janji_bayar'] }}</pre>
                        </div>
                    </div>

                    <div class="fonnte-template-card fonnte-template-card--overdue">
                        <div class="fonnte-template-card__top">
                            <div class="fonnte-template-card__badge"><i class="fa fa-exclamation-triangle"></i></div>
                            <div>
                                <h4 class="fonnte-template-card__title">Tunggakan / overdue</h4>
                                <p class="fonnte-template-card__desc">Setelah lewat tanggal jatuh tempo — nada tetap sopan.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <textarea name="template_overdue" class="form-control fonnte-field fonnte-template-area" rows="5" aria-label="Template tunggakan">{{ old('template_overdue', $fonnte->template_overdue) }}</textarea>
                        </div>
                        <div class="fonnte-example-block">
                            <p class="fonnte-example-label"><i class="fa fa-lightbulb-o"></i> Contoh isian</p>
                            <pre class="fonnte-example-pre">{{ $tplEx['template_overdue'] }}</pre>
                        </div>
                    </div>
                </div>

                <div class="fonnte-submit-wrap">
                    <button type="submit" class="btn-primary" style="width: 100%;"><i class="fa fa-save"></i> Simpan pengaturan Fonnte</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
