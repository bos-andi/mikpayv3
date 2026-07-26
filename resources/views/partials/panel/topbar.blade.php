<header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/70 backdrop-blur-md px-6 flex items-center justify-between gap-4 shrink-0 z-20 shadow-sm">
    <div class="flex items-center gap-3 flex-1 max-w-md">
        <div class="relative w-full">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input
                type="text"
                placeholder="Cari Pelanggan, IP PPPoE, Kode Voucher, ODP..."
                class="w-full pl-9 pr-4 py-1.5 bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-indigo-500 transition"
            >
        </div>
    </div>

    <div class="flex items-center gap-3 sm:gap-4">
        <button
            type="button"
            onclick="toggleTheme()"
            id="themeToggleBtn"
            class="flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-amber-400 border border-slate-200 dark:border-slate-700 hover:scale-105 transition shadow-sm"
            title="Ubah Tema Cerah/Gelap"
        >
            <i id="themeIcon" class="fa-solid fa-moon text-sm text-slate-700"></i>
        </button>

        <div class="hidden md:flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium {{ current_tenant()?->waapiEnabled() && current_tenant()?->hasWaapiToken() ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500' }}">
            <i class="fa-brands fa-whatsapp text-sm"></i>
            <span>{{ current_tenant()?->waapiEnabled() && current_tenant()?->hasWaapiToken() ? 'WA Gateway Connected' : 'WA Gateway Off' }}</span>
        </div>

        <details class="relative hidden lg:block group">
            <summary class="list-none cursor-pointer flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-xs font-medium hover:bg-indigo-500/15 transition max-w-[280px] [&::-webkit-details-marker]:hidden">
                <i class="fa-solid fa-network-wired text-sm shrink-0"></i>
                <span class="truncate">{{ $activeRouterLabel ?? 'Belum pilih router' }}</span>
                @if (($tenantRouters ?? collect())->count() > 1)
                    <i class="fa-solid fa-chevron-down text-[9px] opacity-70 shrink-0"></i>
                @endif
            </summary>

            @if (($tenantRouters ?? collect())->isNotEmpty())
                <div class="absolute right-0 mt-2 w-72 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-50 overflow-hidden">
                    <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">MikroTik aktif</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">Pelanggan & isolir mengikuti router yang dipilih.</p>
                    </div>
                    <div class="py-1 max-h-64 overflow-y-auto">
                        @foreach ($tenantRouters as $r)
                            @php $isActive = $activeRouter && (int) $activeRouter->id === (int) $r->id; @endphp
                            <form method="POST" action="{{ panel_route('settings.routers.select', $r) }}">
                                @csrf
                                <button
                                    type="submit"
                                    @disabled(! $r->isEnabled())
                                    class="w-full text-left px-3 py-2.5 text-xs hover:bg-slate-50 dark:hover:bg-slate-800/80 transition flex items-start gap-2 {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-500/10' : '' }} {{ $r->isEnabled() ? '' : 'opacity-50 cursor-not-allowed' }}"
                                >
                                    <i class="fa-solid {{ $isActive ? 'fa-circle-check text-indigo-600' : 'fa-circle text-slate-300' }} mt-0.5 text-[10px]"></i>
                                    <span class="min-w-0">
                                        <span class="block font-bold text-slate-800 dark:text-slate-100 truncate">{{ $r->displayName() }}</span>
                                        <span class="block font-mono text-[10px] text-slate-500 truncate">{{ $r->endpointLabel() }}</span>
                                    </span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                    <a href="{{ panel_route('settings') }}?tab=mikrotik" class="block px-3 py-2 text-[11px] font-bold text-indigo-600 border-t border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60">
                        Kelola router →
                    </a>
                </div>
            @endif
        </details>

        <button type="button" class="relative p-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition" aria-label="Notifikasi">
            <i class="fa-regular fa-bell"></i>
            <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-rose-500"></span>
        </button>

        <button
            type="button"
            onclick="showToast('Membuka form pendaftaran pelanggan baru')"
            class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-3.5 py-2 rounded-xl transition shadow-lg shadow-indigo-600/20"
        >
            <i class="fa-solid fa-user-plus"></i>
            <span class="hidden sm:inline">Pasang Baru</span>
        </button>
    </div>
</header>
