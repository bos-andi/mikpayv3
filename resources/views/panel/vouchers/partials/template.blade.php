@php
    $tplKeys = [
        'default' => 'Default',
        'thermal' => 'Thermal',
        'small' => 'Small',
    ];
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Template Editor</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    File: <span class="font-mono">{{ $templateFile }}</span>
                    @if ($templateTenantId)
                        · tenant <strong>{{ $templateTenantId }}</strong>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($tplKeys as $key => $label)
                    <a href="{{ $voucherUrl('template', ['template' => $key, 'profile' => null, 'comment' => null, 'exp' => null]) }}"
                       class="text-[11px] font-bold px-3 py-1.5 rounded-xl border transition {{ ($templateKey ?? 'default') === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ panel_route('vouchers.template.save') }}" id="voucherTemplateForm">
            @csrf
            <input type="hidden" name="template" value="{{ $templateKey ?? 'default' }}">
            <div class="flex flex-wrap gap-2 mb-3">
                <button type="submit" class="text-xs font-bold px-4 py-2 rounded-xl bg-indigo-600 text-white">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan
                </button>
                <button type="button" id="templatePreviewBtn" class="text-xs font-bold px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200">
                    <i class="fa-solid fa-eye mr-1"></i> Preview
                </button>
            </div>
            <textarea name="content" rows="22"
                      class="w-full text-[12px] leading-relaxed font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 px-3 py-3">{{ $templateContent }}</textarea>
            @error('content')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </form>

        <p class="mt-3 text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
            Sintaks Blade. Teks: <code class="font-mono">@{{ $username }}</code> · HTML/QR: <code class="font-mono">&#123;!! $qrcode !!&#125;</code>.
            Logo: <code class="font-mono">$logo</code> (aplikasi) / <code class="font-mono">$logo_session</code> (per router).
            Cetak pakai <strong>Default</strong>, <strong>Thermal</strong> (<code>tpl=thermal</code>), atau <strong>Small</strong> (<code>small=yes</code>).
        </p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2">Variabel</h3>
        <pre class="text-[11px] leading-relaxed text-slate-600 dark:text-slate-300 whitespace-pre-wrap font-mono bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-xl p-3 max-h-[560px] overflow-auto">{{ $variableContent }}</pre>
    </div>
</div>

<div id="templatePreviewModal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 bg-slate-900/60">
    <div class="w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-2xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 bg-indigo-600 text-white">
            <div class="text-sm font-bold"><i class="fa-solid fa-eye mr-2"></i>Preview Template</div>
            <button type="button" id="templatePreviewClose" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25">Tutup</button>
        </div>
        <div class="bg-white min-h-[320px] relative">
            <iframe id="templatePreviewFrame" class="w-full h-[580px] border-0 hidden" title="Preview Template"></iframe>
            <div id="templatePreviewLoading" class="p-4 text-sm text-slate-500">Loading...</div>
        </div>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('templatePreviewBtn');
    var modal = document.getElementById('templatePreviewModal');
    var closeBtn = document.getElementById('templatePreviewClose');
    var frame = document.getElementById('templatePreviewFrame');
    var loadingEl = document.getElementById('templatePreviewLoading');
    var form = document.getElementById('voucherTemplateForm');
    if (!btn || !form || !modal) return;

    var csrf = form.querySelector('input[name="_token"]')?.value || '';
    var previewUrl = @json(panel_route('vouchers.template.preview'));

    function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

    closeBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        var template = form.querySelector('input[name="template"]')?.value || 'default';
        var content = form.querySelector('textarea[name="content"]')?.value || '';
        if (loadingEl) { loadingEl.style.display = 'block'; loadingEl.textContent = 'Rendering preview...'; }
        if (frame) { frame.classList.add('hidden'); frame.srcdoc = ''; }
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
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.html && frame) {
                frame.srcdoc = d.html;
                frame.classList.remove('hidden');
                if (loadingEl) loadingEl.style.display = 'none';
            } else if (loadingEl) {
                loadingEl.innerHTML = '<span class="text-rose-600 font-bold">Preview tidak berhasil.</span>';
            }
        })
        .catch(function () {
            if (loadingEl) loadingEl.innerHTML = '<span class="text-rose-600 font-bold">Gagal render preview.</span>';
        });
    });
})();
</script>
