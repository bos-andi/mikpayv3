{{-- Modal konfirmasi / alert / toast custom MikPay --}}
<div id="mikpayConfirmModal" class="fixed inset-0 z-[9000] hidden items-center justify-center p-4" aria-modal="true" role="dialog">
    <div id="mikpayConfirmBackdrop" class="absolute inset-0 bg-slate-950/55 backdrop-blur-[2px]"></div>
    <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-5 pt-5 pb-3 flex items-start gap-3">
            <div id="mikpayConfirmIconWrap" class="h-10 w-10 rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300 flex items-center justify-center shrink-0">
                <i id="mikpayConfirmIcon" class="fa-solid fa-circle-question text-lg"></i>
            </div>
            <div class="min-w-0 pt-0.5">
                <p id="mikpayConfirmTitle" class="text-sm font-extrabold text-slate-900 dark:text-white">Konfirmasi</p>
                <p id="mikpayConfirmMessage" class="text-[12px] text-slate-500 dark:text-slate-300 mt-1 leading-relaxed"></p>
            </div>
        </div>
        <div id="mikpayConfirmActions" class="px-5 pb-5 pt-2 flex gap-2">
            <button type="button" id="mikpayConfirmCancel" class="flex-1 px-3 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-600 text-xs font-bold text-slate-600 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                Batal
            </button>
            <button type="button" id="mikpayConfirmOk" class="flex-1 px-3 py-2.5 rounded-2xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold shadow-md shadow-violet-600/20 transition">
                Ya, lanjut
            </button>
        </div>
    </div>
</div>

<div id="mikpayToast" class="fixed left-1/2 -translate-x-1/2 bottom-6 z-[9100] hidden max-w-sm w-[calc(100%-2rem)] pointer-events-none">
    <div class="rounded-2xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-[11px] font-semibold px-4 py-3 shadow-xl text-center"></div>
</div>

<script src="{{ asset('js/mikpay-dialogs.js') }}?v=1"></script>
