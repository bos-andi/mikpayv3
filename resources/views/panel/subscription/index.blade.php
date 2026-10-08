@extends('layouts.panel')

@section('title', 'Langganan — MikPay')
@section('page', 'subscription')

@php
    $idr = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    $pkgList = collect($packages);
    $monthlyPkg = $pkgList->firstWhere('months', 1) ?? $pkgList->sortBy('price')->first();
    $monthlyPrice = $monthlyPkg ? \App\Services\PartnerProgramService::applyDiscount((int) $monthlyPkg['price'], $referralDiscount) : 0;
    $shortfall = $monthlyPrice > 0 ? max(0, $monthlyPrice - (int) $balance) : 0;
    $bonusBalance = $tenant->bonusPlatformBalance();

    $currentPkg = \App\Services\SubscriptionPackageService::get((string) $tenant->plan);
    $periodDays = (int) ($currentPkg['duration'] ?? config('mikpay.trial_days', 3));
    $periodDays = max(1, $periodDays);
    $progress = $remainingDays !== null ? (int) round(min(100, max(0, $remainingDays / $periodDays * 100))) : 0;
    $progressTone = ! $isActive || $remainingDays === 0 ? 'bg-rose-500' : ($remainingDays <= 3 ? 'bg-amber-500' : 'bg-emerald-500');

    $presetLabels = [];
    $pkgPrices = [];
    foreach ($packages as $pkg) {
        $presetLabels[(int) $pkg['price']] = 'Pas untuk paket '.$pkg['name'];
        $pkgPrices[] = [
            'name' => $pkg['name'],
            'price' => \App\Services\PartnerProgramService::applyDiscount((int) $pkg['price'], $referralDiscount),
            'days' => (int) $pkg['duration'],
        ];
    }
    $bestAffordable = collect($pkgPrices)->filter(fn ($p) => $p['price'] <= (int) $balance)->sortByDesc('days')->first();
@endphp

@section('content')
@if (session('success'))
    <div class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3"><i class="fa-solid fa-circle-check mt-0.5"></i><span>{{ session('success') }}</span></div>
@endif
@if (session('error'))
    <div class="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs font-semibold px-4 py-3"><i class="fa-solid fa-circle-exclamation mt-0.5"></i><span>{{ session('error') }}</span></div>
@endif
@if (($returnStatus ?? '') === 'deposit_success' || ($returnStatus ?? '') === 'success')
    <div class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3">
        <i class="fa-solid fa-circle-check mt-0.5"></i>
        <span>Pembayaran deposit diterima. Jika saldo belum berubah, tunggu beberapa detik lalu refresh halaman.</span>
    </div>
@elseif (($returnStatus ?? '') === 'deposit_failed' || ($returnStatus ?? '') === 'failed')
    <div class="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs font-semibold px-4 py-3">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <span>Pembayaran deposit dibatalkan / gagal. Silakan isi saldo lagi.</span>
    </div>
@endif

@if ($needsRenewal ?? false)
    <div class="rounded-2xl border border-rose-200 bg-rose-50 dark:bg-rose-950/30 dark:border-rose-900 px-5 py-4 sm:px-6">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div>
                <h2 class="text-sm sm:text-base font-extrabold text-rose-900 dark:text-rose-200">Panel nonaktif — perpanjang langganan</h2>
                <p class="text-xs sm:text-sm text-rose-800/90 dark:text-rose-200/80 mt-1 leading-relaxed">
                    Isi saldo di bawah, lalu pilih paket untuk mengaktifkan kembali panel <strong>{{ $tenant->name }}</strong>.
                    Menu lain akan terbuka setelah langganan diperpanjang.
                </p>
            </div>
        </div>
    </div>
@endif

{{-- Dompet + status --}}
<div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
    <div class="lg:col-span-3 relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-indigo-700 text-white p-6 sm:p-7 shadow-xl shadow-indigo-950/20">
        <div class="pointer-events-none absolute -top-24 -right-16 w-72 h-72 rounded-full bg-violet-500/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 -left-10 w-72 h-72 rounded-full bg-sky-400/20 blur-3xl"></div>
        <svg class="pointer-events-none absolute right-0 bottom-0 w-64 opacity-[0.07]" viewBox="0 0 200 200" fill="none"><circle cx="150" cy="150" r="90" stroke="white" stroke-width="18"/><circle cx="150" cy="150" r="45" stroke="white" stroke-width="18"/></svg>

        <div class="relative flex items-start justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-white/10 border border-white/15 backdrop-blur flex items-center justify-center">
                    <i class="fa-solid fa-wallet text-sm"></i>
                </span>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-indigo-200">Saldo MikPay</p>
                    <p class="text-[10px] text-indigo-300/80">Dompet langganan panel</p>
                </div>
            </div>
            <span class="w-11 h-8 rounded-md bg-gradient-to-br from-amber-200 via-amber-300 to-amber-500 shadow-inner opacity-90" aria-hidden="true"></span>
        </div>

        <div class="relative mt-6">
            <p class="text-4xl sm:text-5xl font-extrabold tracking-tight tabular-nums">{{ $idr($balance) }}</p>
            <p class="mt-2 text-xs text-indigo-100/90">
                @if ($bestAffordable)
                    <i class="fa-solid fa-circle-check text-emerald-300 mr-1"></i>
                    Cukup untuk paket <b class="text-white">{{ $bestAffordable['name'] }}</b>
                @elseif ($monthlyPrice > 0)
                    <i class="fa-solid fa-circle-info text-amber-300 mr-1"></i>
                    Kurang <b class="text-white">{{ $idr($shortfall) }}</b> untuk paket {{ $monthlyPkg['name'] }} ({{ $idr($monthlyPrice) }})
                @endif
            </p>
            @if ($bonusBalance > 0)
                <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-2.5 py-1 text-[10px] font-bold text-amber-200">
                    <i class="fa-solid fa-gift"></i> Termasuk saldo bonus {{ $idr($bonusBalance) }}
                </span>
            @endif
        </div>

        <div class="relative mt-7 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[10px] uppercase tracking-[0.18em] text-indigo-300/80">Pemilik</p>
                <p class="text-sm font-bold truncate">{{ $tenant->name }}</p>
                <p class="text-[11px] font-mono text-indigo-200/80 truncate">{{ $tenant->subdomain }}.{{ config('mikpay.tenant_base_domain', 'mikpay.link') }}</p>
            </div>
            @if ($depositReady)
                <a href="#isi-saldo" class="inline-flex items-center gap-2 rounded-xl bg-white text-indigo-900 text-xs font-extrabold px-4 py-2.5 shadow-lg hover:bg-indigo-50 transition">
                    <i class="fa-solid fa-plus"></i> Isi Saldo
                </a>
            @endif
        </div>
    </div>

    <div class="lg:col-span-2 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm flex flex-col">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 flex items-center justify-center"><i class="fa-solid fa-crown text-sm"></i></span>
                <p class="text-sm font-extrabold text-slate-900 dark:text-white">Status Langganan</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold tracking-wide {{ $isActive ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/10 dark:border-rose-500/30 dark:text-rose-300' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                {{ $isActive ? 'AKTIF' : 'NONAKTIF' }}
            </span>
        </div>

        <div class="mt-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Paket saat ini</p>
            <p class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $planLabel }}</p>
        </div>

        <div class="mt-4">
            <div class="flex items-center justify-between text-[11px] mb-1.5">
                <span class="font-semibold text-slate-500">Sisa masa aktif</span>
                <span class="font-extrabold {{ $isActive ? 'text-slate-900 dark:text-white' : 'text-rose-600' }}">
                    {{ $remainingDays !== null ? $remainingDays.' hari' : '—' }}
                </span>
            </div>
            <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full {{ $progressTone }} transition-all" style="width: {{ max($progress, $isActive ? 4 : 0) }}%"></div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">
                @if ($subscriptionUntil)
                    {{ $isActive ? 'Berlaku sampai' : 'Berakhir' }} <b class="text-slate-700 dark:text-slate-200">{{ $subscriptionUntil }}</b>
                @else
                    Belum ada masa aktif.
                @endif
            </p>
        </div>

        <a href="#perpanjang" class="mt-auto pt-5">
            <span class="w-full inline-flex items-center justify-center gap-2 rounded-xl {{ $isActive ? 'border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800' : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/20' }} text-xs font-bold py-2.5 transition">
                <i class="fa-solid fa-rotate"></i> {{ $isActive ? 'Perpanjang lebih awal' : 'Aktifkan kembali' }}
            </span>
        </a>
    </div>
</div>

{{-- Alur --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    @foreach ([
        ['fa-wallet', 'Isi saldo', 'QRIS, Virtual Account, atau e-wallet via Xendit.'],
        ['fa-box-open', 'Pilih paket', 'Biaya langganan dipotong otomatis dari saldo.'],
        ['fa-bolt', 'Panel aktif', 'Masa aktif bertambah seketika setelah perpanjang.'],
    ] as $i => [$icon, $title, $desc])
        <div class="flex items-start gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3">
            <span class="relative w-9 h-9 shrink-0 rounded-xl bg-slate-900 dark:bg-indigo-500/20 text-white flex items-center justify-center">
                <i class="fa-solid {{ $icon }} text-xs"></i>
                <span class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-indigo-500 text-[9px] font-extrabold flex items-center justify-center ring-2 ring-white dark:ring-slate-900">{{ $i + 1 }}</span>
            </span>
            <div>
                <p class="text-xs font-extrabold text-slate-900 dark:text-white">{{ $title }}</p>
                <p class="text-[11px] text-slate-500 leading-snug mt-0.5">{{ $desc }}</p>
            </div>
        </div>
    @endforeach
</div>

{{-- Isi saldo --}}
<div id="isi-saldo" class="scroll-mt-24 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
    <div class="px-5 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/30"><i class="fa-solid fa-plus"></i></span>
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Isi Saldo</h3>
                <p class="text-[11px] text-slate-500">
                    {{ $depositReady ? 'Saldo masuk otomatis begitu pembayaran lunas.' : 'Pembayaran online belum tersedia. Hubungi admin MikPay.' }}
                </p>
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 px-2.5 py-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300">
            <i class="fa-solid fa-shield-halved"></i> Pembayaran aman via Xendit
        </span>
    </div>

    @if ($pendingDeposit)
        <div class="mx-5 sm:mx-6 mt-5 rounded-2xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-4 py-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="w-9 h-9 shrink-0 rounded-xl bg-amber-400 text-white flex items-center justify-center"><i class="fa-solid fa-hourglass-half text-xs"></i></span>
                <div class="text-xs text-amber-900 dark:text-amber-200">
                    <p class="font-extrabold">Menunggu pembayaran {{ $idr($pendingDeposit->amount) }}</p>
                    <p class="text-[11px] mt-0.5 opacity-80">ID <code class="font-mono">{{ $pendingDeposit->payment_id }}</code> · dibuat {{ format_wib($pendingDeposit->created_at) }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($pendingDeposit->xendit_invoice_url)
                    <a href="{{ $pendingDeposit->xendit_invoice_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3 py-2 transition">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Lanjutkan bayar
                    </a>
                @endif
                <form method="POST" action="{{ panel_route('subscription.cancel-pending') }}" onsubmit="return MikPay.confirmForm(event, { title: 'Batalkan deposit?', message: 'Batalkan deposit pending ini?', variant: 'warning', confirmText: 'Ya, batalkan' })">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 dark:border-amber-500/40 text-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-500/10 text-xs font-bold px-3 py-2 transition">Batalkan</button>
                </form>
            </div>
        </div>
    @endif

    @if ($depositReady)
        <form method="POST" action="{{ panel_route('subscription.deposit') }}" id="depositForm" class="grid grid-cols-1 lg:grid-cols-3">
            @csrf
            <input type="hidden" name="amount" id="depositAmount" value="">

            <div class="lg:col-span-2 p-5 sm:p-6 space-y-5">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2.5">Pilih nominal</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($depositPresets as $preset)
                            <button type="button" data-deposit-preset="{{ (int) $preset }}"
                                    class="deposit-preset group relative text-left rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-indigo-300 px-4 py-3.5 transition">
                                <span class="preset-check absolute top-2.5 right-2.5 w-5 h-5 rounded-full bg-indigo-600 text-white text-[9px] items-center justify-center hidden"><i class="fa-solid fa-check"></i></span>
                                <span class="block text-base font-extrabold text-slate-900 dark:text-white tabular-nums">{{ $idr($preset) }}</span>
                                <span class="block text-[10px] font-semibold text-slate-500 mt-0.5">{{ $presetLabels[(int) $preset] ?? 'Bebas dipakai' }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="depositAmountDisplay" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Atau masukkan nominal lain</label>
                    <div id="depositAmountWrap" class="flex items-center rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 focus-within:border-indigo-500 focus-within:bg-white dark:focus-within:bg-slate-900 transition max-w-sm">
                        <span class="pl-4 pr-2 text-sm font-extrabold text-slate-400">Rp</span>
                        <input type="text" inputmode="numeric" id="depositAmountDisplay" autocomplete="off"
                               placeholder="{{ number_format($depositMin, 0, ',', '.') }}"
                               class="w-full bg-transparent py-3 pr-4 text-lg font-extrabold text-slate-900 dark:text-white tabular-nums focus:outline-none placeholder:text-slate-300 dark:placeholder:text-slate-600">
                    </div>
                    <p id="depositAmountHint" class="text-[11px] text-slate-400 mt-1.5">Minimal {{ $idr($depositMin) }}, kelipatan bebas.</p>
                </div>

                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Metode pembayaran</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([['fa-qrcode', 'QRIS'], ['fa-building-columns', 'Virtual Account Bank'], ['fa-mobile-screen-button', 'E-Wallet']] as [$icon, $label])
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                                <i class="fa-solid {{ $icon }} text-indigo-500"></i> {{ $label }}
                            </span>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1.5">Pilih metode di halaman pembayaran setelah klik tombol bayar.</p>
                </div>
            </div>

            <div class="p-5 sm:p-6 bg-slate-50 dark:bg-slate-800/30 border-t lg:border-t-0 lg:border-l border-slate-100 dark:border-slate-800 flex flex-col">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Ringkasan</p>
                <dl class="mt-3 space-y-2.5 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Saldo sekarang</dt>
                        <dd class="font-bold text-slate-700 dark:text-slate-200 tabular-nums">{{ $idr($balance) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Nominal isi saldo</dt>
                        <dd id="sumAmount" class="font-bold text-indigo-600 dark:text-indigo-300 tabular-nums">+ Rp 0</dd>
                    </div>
                    <div class="pt-2.5 border-t border-dashed border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3">
                        <dt class="font-bold text-slate-700 dark:text-slate-200">Saldo setelah isi</dt>
                        <dd id="sumAfter" class="text-base font-extrabold text-slate-900 dark:text-white tabular-nums">{{ $idr($balance) }}</dd>
                    </div>
                </dl>
                <p id="sumCover" class="mt-2 text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold min-h-[1rem]"></p>

                @if ($shortfall > 0)
                    <button type="button" id="shortfallHint" data-deposit-fill="{{ max($shortfall, $depositMin) }}"
                            class="mt-3 text-left rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-3 py-2.5 text-[11px] text-amber-800 dark:text-amber-200 hover:bg-amber-100 transition">
                        <i class="fa-solid fa-lightbulb mr-1"></i>
                        Butuh <b>{{ $idr($shortfall) }}</b> lagi untuk paket {{ $monthlyPkg['name'] }}. <span class="underline font-bold">Isi nominal ini</span>
                    </button>
                @endif

                <div class="mt-auto pt-5">
                    <button type="submit" id="depositSubmit" @disabled($pendingDeposit)
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-extrabold py-3 shadow-lg shadow-indigo-600/25 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-lock text-xs"></i> <span id="depositSubmitLabel">{{ $pendingDeposit ? 'Selesaikan deposit pending dulu' : 'Lanjut ke Pembayaran' }}</span>
                    </button>
                    <p class="text-[10px] text-slate-400 text-center mt-2 leading-relaxed">Anda akan diarahkan ke halaman pembayaran Xendit. Saldo otomatis bertambah setelah lunas.</p>
                </div>
            </div>
        </form>
    @else
        <div class="p-6 text-center">
            <span class="mx-auto w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center"><i class="fa-solid fa-plug-circle-xmark"></i></span>
            <p class="text-xs text-slate-500 mt-3">Isi saldo online sedang tidak tersedia. Silakan hubungi admin MikPay.</p>
        </div>
    @endif
</div>

{{-- Pilih paket --}}
<div id="perpanjang" class="scroll-mt-24 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4" data-balance="{{ (int) $balance }}">
    <div class="flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-slate-900 dark:bg-indigo-500/20 text-white flex items-center justify-center"><i class="fa-solid fa-box-open"></i></span>
        <div>
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Perpanjang Langganan</h3>
            <p class="text-[11px] text-slate-500">Satu paket dengan semua fitur — pilih durasinya. Biaya dipotong dari saldo MikPay Anda.</p>
        </div>
    </div>

    <ul class="flex flex-wrap gap-x-4 gap-y-1.5 text-[11px] text-slate-600 dark:text-slate-300 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 px-4 py-3">
        @foreach ($features as $f)
            <li class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-600"></i> {{ $f }}</li>
        @endforeach
    </ul>

    @if ($referralDiscount > 0)
        <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-800 px-4 py-3">
            <span class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0"><i class="fa-solid fa-gift text-xs"></i></span>
            <p class="text-[11px] text-emerald-800 dark:text-emerald-200 leading-relaxed">
                <b>Diskon referral {{ rtrim(rtrim(number_format($referralDiscount, 2, ',', ''), '0'), ',') }}%</b> untuk pembayaran paket pertama Anda karena mendaftar dengan kode affiliate.
                Harga di bawah sudah dipotong otomatis.
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($packages as $key => $pkg)
            @php
                $basePrice = (int) $pkg['price'];
                $price = \App\Services\PartnerProgramService::applyDiscount($basePrice, $referralDiscount);
                $featured = ! empty($pkg['featured']);
            @endphp
            <div class="renew-card relative rounded-2xl border-2 {{ $featured ? 'border-indigo-500 shadow-lg shadow-indigo-500/10' : 'border-slate-200 dark:border-slate-700' }} bg-white dark:bg-slate-800/40 p-5 flex flex-col"
                 data-package-name="{{ $pkg['name'] }}"
                 data-price="{{ $price }}"
                 data-featured="{{ $featured ? '1' : '0' }}">
                @if (! empty($pkg['featured_ribbon']))
                    <span class="self-start text-[10px] font-extrabold uppercase tracking-wider {{ $featured ? 'text-white bg-indigo-600 border-indigo-600' : 'text-indigo-700 bg-indigo-50 border-indigo-200' }} border px-2 py-0.5 rounded-full mb-2">{{ $pkg['featured_ribbon'] }}</span>
                @endif
                <h4 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $pkg['name'] }}</h4>
                <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-2 tabular-nums">
                    {{ $idr($price) }}
                    <span class="text-xs font-normal text-slate-500">{{ $pkg['duration_label'] }}</span>
                </p>
                @if ($price < $basePrice)
                    <p class="text-[11px] text-slate-400 line-through">{{ $idr($basePrice) }}</p>
                    <span class="self-start mt-1 text-[10px] font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                        <i class="fa-solid fa-gift"></i> Diskon referral −{{ $idr($basePrice - $price) }}
                    </span>
                @elseif ($pkg['normal_price'])
                    <p class="text-[11px] text-slate-400 line-through">{{ $idr($pkg['normal_price']) }}</p>
                    <span class="self-start mt-1 text-[10px] font-extrabold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                        Hemat {{ $idr($pkg['savings']) }} ({{ $pkg['savings_percent'] }}%)
                    </span>
                @endif
                <p class="text-[11px] text-slate-500 mt-1 flex-1">
                    @if ($pkg['months'] > 1)
                        Setara {{ $idr($pkg['monthly_equivalent']) }}/bulan ·
                    @endif
                    {{ $pkg['duration'] }} hari
                </p>
                <form method="POST" action="{{ panel_route('subscription.select') }}" class="mt-4 js-renew-form">
                    @csrf
                    <input type="hidden" name="package" value="{{ $key }}">
                    <button type="submit" class="js-renew-btn w-full py-2.5 rounded-xl text-xs font-bold disabled:opacity-50 transition">
                        Perpanjang dengan saldo
                    </button>
                </form>
            </div>
        @endforeach
    </div>
</div>

{{-- Riwayat --}}
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
    <div class="px-5 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
        <span class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center"><i class="fa-solid fa-clock-rotate-left text-sm"></i></span>
        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Riwayat Transaksi</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3 font-bold">ID</th>
                    <th class="text-left px-4 py-3 font-bold">Jenis</th>
                    <th class="text-left px-4 py-3 font-bold">Detail</th>
                    <th class="text-left px-4 py-3 font-bold">Nominal</th>
                    <th class="text-left px-4 py-3 font-bold">Status</th>
                    <th class="text-left px-4 py-3 font-bold">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($transactions as $p)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-600 dark:text-slate-300">{{ $p->payment_id }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700 dark:text-slate-200">
                                <i class="fa-solid {{ $p->isDeposit() ? 'fa-arrow-down text-emerald-500' : 'fa-arrow-up text-indigo-500' }} text-[10px]"></i>
                                {{ $p->purposeLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                            @if ($p->isDeposit())
                                Top up saldo
                            @else
                                {{ \App\Services\SubscriptionPackageService::planLabel($p->package) }}
                            @endif
                        </td>
                        <td class="px-4 py-3 font-bold tabular-nums text-slate-900 dark:text-white">{{ $idr($p->amount) }}</td>
                        <td class="px-4 py-3">
                            @if ($p->status === 'pending')
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">menunggu bayar</span>
                            @elseif ($p->status === 'approved')
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">sukses</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold">{{ $p->rejected_by === 'xendit' ? 'kedaluwarsa' : 'batal' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ format_wib($p->created_at) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                            <i class="fa-solid fa-receipt text-2xl mb-2 block text-slate-300"></i>
                            Belum ada transaksi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('depositForm');
    if (!form) return;
    const hidden = document.getElementById('depositAmount');
    const display = document.getElementById('depositAmountDisplay');
    const wrap = document.getElementById('depositAmountWrap');
    const hint = document.getElementById('depositAmountHint');
    const sumAmount = document.getElementById('sumAmount');
    const sumAfter = document.getElementById('sumAfter');
    const sumCover = document.getElementById('sumCover');
    const submit = document.getElementById('depositSubmit');
    const balance = {{ (int) $balance }};
    const min = {{ (int) $depositMin }};
    const pkgs = @js($pkgPrices);
    const shortfallHint = document.getElementById('shortfallHint');
    const hintDefault = hint.textContent;
    const idr = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    const presets = document.querySelectorAll('.deposit-preset');

    const markPreset = (value) => {
        presets.forEach((b) => {
            const on = Number(b.dataset.depositPreset) === value;
            b.classList.toggle('border-indigo-500', on);
            b.classList.toggle('bg-indigo-50', on);
            b.classList.toggle('dark:bg-indigo-500/10', on);
            b.classList.toggle('border-slate-200', !on);
            b.classList.toggle('dark:border-slate-700', !on);
            const check = b.querySelector('.preset-check');
            check.classList.toggle('hidden', !on);
            check.classList.toggle('flex', on);
        });
    };

    const setAmount = (value, fromInput = false) => {
        value = Math.max(0, Math.floor(Number(value) || 0));
        hidden.value = value > 0 ? String(value) : '';
        if (!fromInput) display.value = value > 0 ? value.toLocaleString('id-ID') : '';
        markPreset(value);
        sumAmount.textContent = '+ ' + idr(value);
        sumAfter.textContent = idr(balance + value);
        const best = pkgs.filter((p) => p.price <= balance + value).sort((a, b) => b.days - a.days)[0];
        sumCover.textContent = value > 0 && best ? '✓ Cukup untuk paket ' + best.name : '';
        if (shortfallHint) shortfallHint.classList.toggle('hidden', !!best);
        wrap.classList.remove('border-rose-400');
        hint.classList.remove('text-rose-600');
        hint.textContent = hintDefault;
    };

    presets.forEach((b) => b.addEventListener('click', () => setAmount(b.dataset.depositPreset)));
    document.querySelectorAll('[data-deposit-fill]').forEach((b) => b.addEventListener('click', () => {
        setAmount(b.dataset.depositFill);
        display.focus();
    }));

    display.addEventListener('input', () => {
        const digits = display.value.replace(/\D/g, '').slice(0, 10);
        const value = Number(digits || 0);
        display.value = digits ? value.toLocaleString('id-ID') : '';
        setAmount(value, true);
    });

    form.addEventListener('submit', (event) => {
        const value = Number(hidden.value || 0);
        if (value < min) {
            event.preventDefault();
            wrap.classList.add('border-rose-400');
            hint.classList.add('text-rose-600');
            hint.textContent = value > 0 ? 'Nominal minimal ' + idr(min) + '.' : 'Pilih atau masukkan nominal isi saldo dulu.';
            display.focus();
            return;
        }
        submit.disabled = true;
        document.getElementById('depositSubmitLabel').textContent = 'Membuat tagihan…';
    });
})();

(function () {
    const wrap = document.querySelector('[data-balance]');
    if (!wrap) return;
    const balance = Number(wrap.getAttribute('data-balance') || 0);
    const idr = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');

    document.querySelectorAll('.renew-card').forEach((card) => {
        const price = Number(card.dataset.price || 0);
        const featured = card.dataset.featured === '1';
        const canAfford = balance >= price;
        const btn = card.querySelector('.js-renew-btn');
        if (!btn) return;
        btn.disabled = !canAfford;
        btn.textContent = canAfford ? 'Perpanjang dengan saldo' : 'Saldo kurang ' + idr(price - balance);
        btn.className = 'js-renew-btn w-full py-2.5 rounded-xl text-xs font-bold disabled:opacity-60 disabled:cursor-not-allowed transition '
            + (featured && canAfford
                ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/20'
                : canAfford
                    ? 'border border-slate-300 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 hover:border-indigo-400'
                    : 'border border-dashed border-slate-300 bg-slate-50 dark:bg-slate-900 text-slate-500');
    });

    document.querySelectorAll('.js-renew-form').forEach((form) => {
        form.addEventListener('submit', function (event) {
            const card = form.closest('.renew-card');
            if (!card) return;
            const price = Number(card.dataset.price || 0);
            if (balance < price) {
                event.preventDefault();
                return;
            }
            const name = card.dataset.packageName || 'paket';
            const msg = 'Paket MikPay ' + name + ' seharga ' + idr(price)
                + ' akan dipotong dari saldo MikPay. Sisa saldo setelah perpanjang: '
                + idr(balance - price) + '.';
            return MikPay.confirmForm(event, {
                title: 'Perpanjang langganan?',
                message: msg,
                variant: 'default',
                confirmText: 'Ya, perpanjang',
            });
        });
    });
})();
</script>
@endpush
@endsection
