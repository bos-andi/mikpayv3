@extends('layouts.app')

@section('title', 'Template Editor')
@section('heading', 'Template Editor')

@push('styles')
<link rel="stylesheet" href="{{ asset('mikpay/sessions.css') }}">
@endpush

@section('content')
<div class="settings-page-bg"></div>

<div style="padding: 20px; max-width: 1400px; margin: 0 auto;">
    <div class="settings-header">
        <div class="settings-header-icon">
            <i class="fa fa-edit"></i>
        </div>
        <div class="settings-header-text">
            <h1>Template Editor</h1>
            <p>Edit template voucher Blade (default / thermal / small) <strong>per akun login</strong>. Bawaan di <code>resources/views/voucher/</code>; override tersimpan di <code>storage/app/voucher-templates/user-{{ $template_user_id }}/</code></p>
        </div>
        <div class="settings-header-actions">
            <button class="btn-reload" onclick="location.reload();" title="Reload"><i class="fa fa-refresh"></i></button>
        </div>
    </div>

    <div class="settings-container" style="grid-template-columns: 1.5fr 1fr;">
        <div class="glass-card">
            <div class="glass-card-header">
                <div class="glass-card-header-icon">
                    <i class="fa fa-code"></i>
                </div>
                <div class="glass-card-header-text">
                    <h2>Editor</h2>
                    <p>File: <strong>{{ $template_file }}</strong></p>
                </div>
            </div>
            <div class="glass-card-body">
                <div style="display:flex; gap: 10px; flex-wrap:wrap; align-items:center; margin-bottom: 12px;">
                    <a class="router-btn btn-edit" style="text-decoration:none; text-transform:none; padding: 10px 14px;" href="{{ route('admin.template-editor', ['template' => 'default']) }}">Default</a>
                    <a class="router-btn btn-edit" style="text-decoration:none; text-transform:none; padding: 10px 14px;" href="{{ route('admin.template-editor', ['template' => 'thermal']) }}">Thermal</a>
                    <a class="router-btn btn-edit" style="text-decoration:none; text-transform:none; padding: 10px 14px;" href="{{ route('admin.template-editor', ['template' => 'small']) }}">Small</a>
                    <div style="margin-left:auto; color: rgba(255,255,255,0.7); font-size: 12px;">
                        Akun user ID: <strong>{{ $template_user_id }}</strong>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.template-editor.save') }}">
                    @csrf
                    <input type="hidden" name="template" value="{{ $template_key }}">

                    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom:12px;">
                        <button type="submit" class="btn-primary" style="flex:1; min-width: 160px;"><i class="fa fa-save"></i> Save</button>
                        <button type="button" id="templatePreviewBtn" class="btn-secondary" style="height: auto; padding: 12px 16px; text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
                            <i class="fa fa-eye"></i> Preview
                        </button>
                        <a class="btn-secondary" href="{{ route('admin.template-editor', ['template' => $template_key]) }}" title="Reload" style="text-decoration:none; padding: 12px 16px; display:inline-flex; align-items:center; justify-content:center;">
                            <i class="fa fa-refresh"></i>
                        </a>
                    </div>

                    <textarea name="content" style="width:100%; height: 520px; border-radius: 14px; border: 2px solid rgba(255,255,255,0.12); background: rgba(0,0,0,0.18); color: #fff; padding: 14px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 12px; line-height: 1.5;">{{ $template_content }}</textarea>
                    @error('content')<div style="margin-top:8px;color:#fecaca;font-size:12px;">{{ $message }}</div>@enderror
                </form>

                <div style="margin-top: 12px; color: rgba(255,255,255,0.55); font-size: 12px;">
                    Sintaks <strong>Blade</strong> (mis. <code>@@if</code>, <code>@@php</code> … <code>@@endphp</code>). Teks pakai <code>&#123;&#123; $username &#125;&#125;</code>; HTML/QR pakai <code>&#123;!! $qrcode !!&#125;</code>.
                    Variabel logo: <code>$logo</code> (fixed aplikasi) dan <code>$logo_session</code> (logo upload per session router — bukan bagian dari file template).
                    Preview memakai data contoh.
                </div>
            </div>
        </div>

        <div class="admin-panel">
            <div class="admin-panel-header">
                <div class="admin-panel-header-icon">
                    <i class="fa fa-code-fork"></i>
                </div>
                <div class="admin-panel-header-text">
                    <h3>Variable</h3>
                    <p>Daftar variable voucher</p>
                </div>
            </div>
            <div class="admin-panel-body">
                <div style="background:#111827; border-radius: 14px; padding: 14px; color:#cbd5e1; font-size: 12px; white-space: pre-wrap; overflow:auto; max-height: 520px; border: 1px solid rgba(255,255,255,0.08);">
{{ $variable_content }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="templatePreviewModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); z-index:99999; align-items:center; justify-content:center; padding:18px;">
    <div style="width: min(1100px, 96vw); max-height: 90vh; overflow:auto; background:#ffffff; border-radius:18px; border:1px solid rgba(0,0,0,0.08); box-shadow:0 30px 80px rgba(0,0,0,0.5);">
        <div style="padding:16px 18px; background:linear-gradient(135deg,#4D44B5 0%,#6366f1 100%); color:#fff; display:flex; align-items:center; gap:12px;">
            <i class="fa fa-eye"></i>
            <div style="font-weight:800;">Preview Template</div>
            <div style="margin-left:auto;">
                <button type="button" id="templatePreviewClose" style="background:rgba(255,255,255,0.18); border:1px solid rgba(255,255,255,0.2); color:#fff; border-radius:12px; padding:8px 12px; cursor:pointer;">
                    Tutup
                </button>
            </div>
        </div>
        <div id="templatePreviewBody" style="padding:0; background:#ffffff; color:#111827; min-height:320px;">
            <iframe id="templatePreviewFrame" style="width:100%; height:580px; border:0; background:#fff; display:none;" title="Preview Template"></iframe>
            <div id="templatePreviewLoading" style="padding:16px 18px; color:rgba(17,24,39,0.6);">Loading...</div>
        </div>
    </div>
</div>

<script>
    (function() {
        var btn = document.getElementById('templatePreviewBtn');
        var modal = document.getElementById('templatePreviewModal');
        var closeBtn = document.getElementById('templatePreviewClose');
        var body = document.getElementById('templatePreviewBody');
        var frame = document.getElementById('templatePreviewFrame');
        var loadingEl = document.getElementById('templatePreviewLoading');
        var form = btn ? btn.closest('form') : null;
        var csrf = form ? form.querySelector('input[name="_token"]')?.value : '';
        var previewUrl = '{{ route("admin.template-editor.preview") }}';

        function openModal() {
            if (modal) modal.style.display = 'flex';
        }

        function closeModal() {
            if (modal) modal.style.display = 'none';
        }

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (modal) modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });

        if (!btn || !form) return;

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var template = form.querySelector('input[name="template"]')?.value || '';
            var contentEl = form.querySelector('textarea[name="content"]');
            var content = contentEl ? contentEl.value : '';
            if (loadingEl) { loadingEl.style.display = 'block'; loadingEl.textContent = 'Rendering preview...'; }
            if (frame) { frame.style.display = 'none'; frame.srcdoc = ''; }
            openModal();

            fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ template: template, content: content })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d && d.html) {
                    if (frame) {
                        frame.srcdoc = d.html;
                        frame.style.display = 'block';
                    }
                    if (loadingEl) loadingEl.style.display = 'none';
                } else {
                    if (loadingEl) { loadingEl.style.display = 'block'; loadingEl.innerHTML = '<span style="color:#b91c1c; font-weight:700;">Preview tidak berhasil.</span>'; }
                    if (frame) frame.style.display = 'none';
                }
            })
            .catch(function() {
                if (loadingEl) { loadingEl.style.display = 'block'; loadingEl.innerHTML = '<span style="color:#b91c1c; font-weight:700;">Gagal render preview.</span>'; }
                if (frame) frame.style.display = 'none';
            });
        });
    })();
</script>
@endsection

