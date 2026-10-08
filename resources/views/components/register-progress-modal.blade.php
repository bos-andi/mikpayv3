{{-- Modal progres pendaftaran realtime --}}
<div id="registerProgressModal" class="fixed inset-0 z-[8000] hidden items-center justify-center p-4" aria-modal="true" role="dialog" aria-labelledby="registerProgressTitle">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-6 pt-6 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div id="registerProgressSpinner" class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </div>
                <div class="min-w-0">
                    <h3 id="registerProgressTitle" class="text-sm font-extrabold text-slate-900">Memproses pendaftaran…</h3>
                    <p id="registerProgressSubtitle" class="text-[11px] text-slate-500 mt-0.5 truncate">Mohon tunggu, jangan tutup halaman ini.</p>
                </div>
            </div>
            <div class="mt-4 w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                <div id="registerProgressBar" class="bg-indigo-600 h-full rounded-full transition-all duration-500 ease-out" style="width: 0%;"></div>
            </div>
        </div>
        <ul id="registerProgressSteps" class="px-6 py-4 space-y-2.5 max-h-[50vh] overflow-y-auto text-xs">
            {{-- Diisi via JS --}}
        </ul>
        <div id="registerProgressFooter" class="hidden px-6 pb-6 pt-2">
            <button type="button" id="registerProgressClose" class="w-full py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                Tutup
            </button>
        </div>
    </div>
</div>
