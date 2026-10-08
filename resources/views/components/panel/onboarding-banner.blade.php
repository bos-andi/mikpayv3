@php
    $onboarding = $onboarding ?? null;
@endphp
@if ($onboarding && ($onboarding['eligible'] ?? false) && ! ($onboarding['dismissed'] ?? true) && ! ($onboarding['all_required_done'] ?? true))
    <div class="rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-violet-50 dark:from-indigo-950/40 dark:to-violet-950/30 dark:border-indigo-500/30 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3 min-w-0">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                <i class="fa-solid fa-rocket"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900 dark:text-white">Lengkapi setup MikPay Anda</p>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                    {{ $onboarding['required_completed'] ?? 0 }}/{{ $onboarding['required_total'] ?? 5 }} langkah wajib selesai
                    · {{ $onboarding['progress'] ?? 0 }}%
                </p>
                <div class="w-full max-w-xs bg-white/60 dark:bg-slate-800 h-1.5 rounded-full mt-2 overflow-hidden">
                    <div class="bg-indigo-600 h-full rounded-full" style="width: {{ $onboarding['progress'] ?? 0 }}%;"></div>
                </div>
            </div>
        </div>
        <a href="{{ panel_route('onboarding') }}" class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shrink-0 transition shadow-lg shadow-indigo-600/20">
            <i class="fa-solid fa-list-check"></i> Lihat panduan setup
        </a>
    </div>
@endif
