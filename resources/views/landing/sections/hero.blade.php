@php
    $landing = $landing ?? [];
    $trialDays = (int) config('mikpay.trial_days', 4);
    $startPrice = (int) (($pricingPackages ?? [])['bulan1']['price'] ?? 35000);
@endphp

<section class="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden bg-gradient-to-b from-indigo-50/80 via-white to-white">
    <div class="absolute inset-0 bg-grid"></div>
    <div class="glow-blob w-[520px] h-[520px] bg-indigo-400/25 -top-40 -left-40"></div>
    <div class="glow-blob w-[460px] h-[460px] bg-cyan-300/25 top-10 -right-40"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-12 gap-14 lg:gap-10 items-center">
            {{-- Copy --}}
            <div class="lg:col-span-6 text-center lg:text-left">
                <a href="#harga" class="reveal inline-flex items-center gap-2 pl-1.5 pr-3.5 py-1.5 rounded-full bg-white border border-indigo-100 shadow-sm text-xs font-bold text-slate-700 hover:border-indigo-300 transition">
                    <span class="gradient-bg text-white text-[10px] px-2 py-0.5 rounded-full">BARU</span>
                    {{ $landing['hero_badge'] ?? 'Software All-in-One ISP & RTRW Net' }}
                    <i class="fa-solid fa-chevron-right text-[9px] text-slate-400"></i>
                </a>

                <h1 class="reveal mt-6 text-4xl sm:text-5xl xl:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.08]">
                    {{ $landing['hero_title'] ?? 'Kelola Bisnis WiFi, Remote OLT & Auto Isolir' }}
                    <span class="gradient-text">{{ $landing['hero_title_highlight'] ?? 'Tanpa Pusing.' }}</span>
                </h1>

                <p class="reveal mt-6 text-base sm:text-lg text-slate-600 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    {{ $landing['hero_subtitle'] ?? '' }}
                </p>

                <div class="reveal mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3">
                    <a href="{{ route('register') }}" class="group w-full sm:w-auto inline-flex items-center justify-center gap-2.5 text-sm font-bold text-white gradient-bg px-7 py-4 rounded-2xl shadow-xl shadow-indigo-600/25 hover:shadow-indigo-600/40 hover:-translate-y-0.5 transition">
                        <i class="fa-solid fa-rocket"></i>
                        <span>{{ $landing['hero_cta_primary'] ?? "Mulai Coba Gratis {$trialDays} Hari" }}</span>
                    </a>
                    <a href="#cara-kerja" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 text-sm font-bold text-slate-700 px-7 py-4 rounded-2xl border border-slate-200 bg-white/80 hover:bg-white hover:border-indigo-200 transition shadow-sm">
                        <i class="fa-regular fa-circle-play text-indigo-600"></i>
                        <span>Lihat Cara Kerja</span>
                    </a>
                </div>

                <div class="reveal mt-8 flex flex-wrap items-center justify-center lg:justify-start gap-x-6 gap-y-2 text-xs text-slate-500 font-semibold">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-emerald-500"></i> Tanpa kartu kredit</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-emerald-500"></i> Mulai Rp{{ number_format($startPrice, 0, ',', '.') }}/bulan</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-emerald-500"></i> RouterOS v6 & v7</span>
                </div>
            </div>

            {{-- Product mockup --}}
            <div class="lg:col-span-6 relative reveal">
                <div class="relative mx-auto max-w-xl lg:max-w-none">
                    <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-tr from-indigo-500/20 to-cyan-400/20 blur-2xl"></div>

                    <div class="relative rounded-2xl border border-slate-200/80 bg-white shadow-2xl shadow-indigo-900/15 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 bg-slate-50/80">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <div class="mx-auto flex items-center gap-1.5 bg-white border border-slate-200 rounded-lg px-3 py-1 text-[10px] font-mono text-slate-500">
                                <i class="fa-solid fa-lock text-emerald-500 text-[9px]"></i> namaisp.mikpay.link/dashboard
                            </div>
                        </div>

                        <div class="flex">
                            <div class="hidden sm:flex w-14 flex-col items-center gap-3 py-4 border-r border-slate-100 bg-slate-50/60 text-slate-400">
                                <span class="w-8 h-8 rounded-lg gradient-bg text-white flex items-center justify-center text-xs"><i class="fa-solid fa-gauge-high"></i></span>
                                <i class="fa-solid fa-users text-sm"></i>
                                <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
                                <i class="fa-solid fa-map-location-dot text-sm"></i>
                                <i class="fa-solid fa-shield-halved text-sm"></i>
                                <i class="fa-solid fa-gear text-sm"></i>
                            </div>

                            <div class="flex-1 p-4 sm:p-5 space-y-4 text-left">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] text-slate-400 font-semibold">Selamat pagi 👋</p>
                                        <p class="text-sm font-extrabold text-slate-900">Ringkasan Bulan Ini</p>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-lg"><i class="fa-solid fa-circle text-[6px] mr-1 align-middle"></i>Router Online</span>
                                </div>

                                <div class="grid grid-cols-3 gap-2.5">
                                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                        <p class="text-[10px] text-slate-500 font-semibold">Pelanggan</p>
                                        <p class="text-lg font-extrabold text-slate-900">248</p>
                                        <p class="text-[9px] text-emerald-600 font-bold">+12 bulan ini</p>
                                    </div>
                                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                        <p class="text-[10px] text-slate-500 font-semibold">Lunas</p>
                                        <p class="text-lg font-extrabold text-slate-900">231</p>
                                        <p class="text-[9px] text-slate-400 font-bold">93% tertagih</p>
                                    </div>
                                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                        <p class="text-[10px] text-slate-500 font-semibold">Isolir</p>
                                        <p class="text-lg font-extrabold text-rose-600">17</p>
                                        <p class="text-[9px] text-slate-400 font-bold">otomatis</p>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-100 p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[11px] font-bold text-slate-700">Pendapatan 6 bulan</p>
                                        <p class="text-[10px] text-slate-400 font-semibold">Rp juta</p>
                                    </div>
                                    <div class="flex items-end gap-2 h-20">
                                        @foreach ([45, 58, 52, 70, 78, 92] as $i => $h)
                                            <div class="flex-1 rounded-t-md {{ $i === 5 ? 'gradient-bg' : 'bg-indigo-100' }}" style="height: {{ $h }}%"></div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-100 divide-y divide-slate-100 text-[11px]">
                                    @foreach ([['Budi Santoso', 'Home 20 Mbps', 'Lunas', 'emerald'], ['Siti Aminah', 'Home 10 Mbps', 'Jatuh tempo', 'amber'], ['Andi Wijaya', 'Home 30 Mbps', 'Isolir', 'rose']] as $row)
                                        <div class="flex items-center justify-between px-3 py-2">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] font-extrabold">{{ substr($row[0], 0, 1) }}</span>
                                                <div>
                                                    <p class="font-bold text-slate-800 leading-tight">{{ $row[0] }}</p>
                                                    <p class="text-[10px] text-slate-400">{{ $row[1] }}</p>
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-{{ $row[3] }}-50 text-{{ $row[3] }}-700 border border-{{ $row[3] }}-200">{{ $row[2] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Floating cards --}}
                    <div class="hidden sm:flex animate-float absolute -left-8 bottom-16 items-center gap-3 bg-white rounded-2xl border border-slate-200 shadow-xl shadow-slate-900/10 px-4 py-3">
                        <span class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center"><i class="fa-brands fa-whatsapp text-lg"></i></span>
                        <div class="text-left">
                            <p class="text-[11px] font-extrabold text-slate-900">Tagihan terkirim</p>
                            <p class="text-[10px] text-slate-500">via WhatsApp otomatis</p>
                        </div>
                    </div>
                    <div class="hidden sm:flex animate-float-delay absolute -right-6 top-20 items-center gap-3 bg-white rounded-2xl border border-slate-200 shadow-xl shadow-slate-900/10 px-4 py-3">
                        <span class="w-9 h-9 rounded-xl gradient-bg text-white flex items-center justify-center"><i class="fa-solid fa-shield-halved"></i></span>
                        <div class="text-left">
                            <p class="text-[11px] font-extrabold text-slate-900">VPN Remote aktif</p>
                            <p class="text-[10px] text-slate-500">Winbox & Web OLT dari mana saja</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
