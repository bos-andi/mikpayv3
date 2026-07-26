@extends('layouts.app')

@section('title', 'Upload Logo')
@section('heading', 'Upload Logo')

@push('styles')
<link rel="stylesheet" href="{{ asset('mikpay/sessions.css') }}">
@endpush

@section('content')
<div class="settings-page-bg"></div>

<div style="padding: 20px; max-width: 1400px; margin: 0 auto;">
    <div class="settings-header">
        <div class="settings-header-icon">
            <i class="fa fa-upload"></i>
        </div>
        <div class="settings-header-text">
            <h1>Upload Logo</h1>
            <p>Format file: <strong>PNG</strong> — disimpan sebagai <strong>logo-SESSION.png</strong> (pilih session di bawah)</p>
        </div>
        <div class="settings-header-actions">
            <button class="btn-reload" onclick="location.reload();" title="Reload"><i class="fa fa-refresh"></i></button>
        </div>
    </div>

    <div class="settings-container" style="grid-template-columns: 1fr;">
        <div class="glass-card">
            <div class="glass-card-header">
                <div class="glass-card-header-icon">
                    <i class="fa fa-image"></i>
                </div>
                <div class="glass-card-header-text">
                    <h2>Upload</h2>
                    <p>Logo akan dipakai untuk preview/print voucher dan invoice (sesuai template)</p>
                </div>
            </div>
            <div class="glass-card-body">
                <form method="POST" action="{{ route('admin.upload-logo.store') }}" enctype="multipart/form-data" style="max-width: 520px;">
                    @csrf
                    <div class="form-group">
                        <label class="form-label"><i class="fa fa-tag"></i> Session (Router)</label>
                        <select class="form-control" name="session_name">
                            <option value="{{ $session_name }}">{{ $session_name }} (default)</option>
                            @foreach($router_sessions ?? [] as $s)
                                @if($s !== $session_name)
                                <option value="{{ $s }}">{{ $s }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('session_name')<div style="margin-top:8px;color:#fecaca;font-size:12px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa fa-file-image-o"></i> File Logo (PNG)</label>
                        <input class="form-control" type="file" name="logo" accept="image/png" required>
                        @error('logo')<div style="margin-top:8px;color:#fecaca;font-size:12px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-actions" style="border-top:none; padding-top:0; margin-top: 14px;">
                        <button type="submit" class="btn-primary"><i class="fa fa-upload"></i> Upload</button>
                    </div>
                </form>

                <div style="margin-top: 24px;">
                    <div style="color: rgba(255,255,255,0.75); font-weight: 700; margin-bottom: 12px;">Daftar Logo</div>
                    <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; overflow: hidden;">
                        <table style="width:100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.06);">
                                    <th style="text-align:left; padding: 12px 14px; color: rgba(255,255,255,0.75); font-size: 12px;">Logo</th>
                                    <th style="text-align:left; padding: 12px 14px; color: rgba(255,255,255,0.75); font-size: 12px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logo_files as $f)
                                <tr style="border-top: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 14px; color:#fff; font-size: 13px;">
                                        <a href="{{ asset('mikpay/img/' . $f) }}" target="_blank" rel="noopener" style="color:#fff; text-decoration:none;">
                                            <img src="{{ asset('mikpay/img/' . $f) }}" alt="{{ $f }}" style="height: 30px; border-radius: 6px; background: rgba(255,255,255,0.9); padding: 4px; margin-right: 10px; vertical-align: middle;">
                                            <span style="vertical-align: middle;">{{ $f }}</span>
                                        </a>
                                    </td>
                                    <td style="padding: 12px 14px;">
                                        <form method="POST" action="{{ route('admin.upload-logo.delete', ['filename' => $f]) }}" onsubmit="return confirm('Hapus {{ $f }} ?');" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="router-btn btn-delete" style="padding: 10px 14px; text-transform:none;">
                                                <i class="fa fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" style="padding: 16px 14px; color: rgba(255,255,255,0.6);">Belum ada logo.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

