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
    $gatewayLabel = \App\Support\PaymentGateway::platformProviderLabel();

    $currentPkg = \App\Services\SubscriptionPackageService::get((string) $tenant->plan);
    $periodDays = max(1, (int) ($currentPkg['duration'] ?? config('mikpay.trial_days', 3)));
    $progress = $remainingDays !== null ? (int) round(min(100, max(0, $remainingDays / $periodDays * 100))) : 0;
    $ringTone = ! $isActive || $remainingDays === 0 ? 'text-rose-500' : ($remainingDays <= 3 ? 'text-amber-500' : 'text-emerald-500');
    $ringCirc = 2 * M_PI * 52;

    $pkgPrices = [];
    foreach ($packages as $pkg) {
        $pkgPrices[] = [
            'name' => $pkg['name'],
            'price' => \App\Services\PartnerProgramService::applyDiscount((int) $pkg['price'], $referralDiscount),
            'days' => (int) $pkg['duration'],
        ];
    }
    $bestAffordable = collect($pkgPrices)->filter(fn ($p) => $p['price'] <= (int) $balance)->sortByDesc('days')->first();

    $autoPkg = $packages[$autoRenew['package']] ?? $monthlyPkg;
    $autoPrice = $autoPkg ? \App\Services\PartnerProgramService::applyDiscount((int) $autoPkg['price'], $referralDiscount) : 0;
    $autoShort = max(0, $autoPrice - (int) $balance);
    $autoLast = $autoRenew['last'];
@endphp

@section('content')
<div class="space-y-5">

{{-- Notifikasi --}}
@if (session('success'))
    <div class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-200 text-xs font-semibold px-4 py-3"><i class="fa-solid fa-circle-check mt-0.5"></i><span>{{ session('success') }}</span></div>
@endif
@if (session('error'))
    <div class="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 dark:bg-rose-500/10 dark:border-rose-500/30 dark:text-rose-200 text-xs font-semibold px-4 py-3"><i class="fa-solid fa-circle-exclamation mt-0.5"></i><span>{{ session('error') }}</span></div>
@endif
@if (in_array($returnStatus ?? '', ['deposit_success', 'success'], true))
    <div class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-200 text-xs font-semibold px-4 py-3">
        <i class="fa-solid fa-circle-check mt-0.5"></i>
        <span>Pembayaran diterima. Jika saldo belum berubah, tunggu beberapa detik lalu muat ulang halaman.</span>
    </div>
@elseif (in_array($returnStatus ?? '', ['deposit_failed', 'failed'], true))
    <div class="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 dark:bg-rose-500/10 dark:border-rose-500/30 dark:text-rose-200 text-xs font-semibold px-4 py-3">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <span>Pembayaran dibatalkan atau gagal. Silakan isi saldo lagi.</span>
    </div>
@endif

@if ($needsRenewal ?? false)
    <div class="relative overflow-hidden rounded-2xl border border-rose-200 dark:border-rose-900 bg-gradient-to-r from-rose-50 to-white dark:from-rose-950/40 dark:to-slate-900 px-5 py-4 sm:px-6">
        <div class="flex items-start gap-3">
            <span class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-rose-500/30"><i class="fa-solid fa-lock"></i></span>
            <div>
                <h2 class="text-sm sm:text-base font-extrabold text-rose-900 dark:text-rose-200">Panel nonaktif — perpanjang langganan</h2>
                <p class="text-xs sm:text-sm text-rose-800/90 dark:text-rose-200/80 mt-1 leading-relaxed">
                    Isi saldo lalu pilih paket untuk mengaktifkan kembali panel <strong>{{ $tenant->name }}</strong>.
                    @if ($autoRenew['enabled'])
                        Perpanjang otomatis aktif, jadi panel langsung aktif kembali begitu saldo cukup.
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif

{{-- Judul --}}
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Langganan & Saldo</h1>
        <p class="text-xs text-slate-500 mt-0.5">Kelola masa aktif panel, saldo MikPay, dan perpanjangan otomatis.</p>
    </div>
    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
        <i class="fa-solid fa-shield-halved text-emerald-500"></i> Pembayaran aman via {{ $gatewayLabel }}
    </span>
</div>

{{-- Ringkasan: dompet + status --}}
<div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
    <div class="lg:col-span-3 relative overflow-hidden rounded-3xl bg-slate-950 text-white shadow-2xl shadow-indigo-950/30 ring-1 ring-white/10">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(120%_90%_at_100%_0%,rgba(129,140,248,0.45),transparent_55%),radial-gradient(90%_80%_at_0%_100%,rgba(56,189,248,0.25),transparent_60%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[0.06] [background-image:linear-gradient(rgba(255,255,255,.6)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.6)_1px,transparent_1px)] [background-size:28px_28px]"></div>

        <div class="relative p-6 sm:p-7">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-white/10 ring-1 ring-white/15 backdrop-blur flex items-center justify-center"><i class="fa-solid fa-wallet"></i></span>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-indigo-200">Saldo MikPay</p>
                        <p class="text-[11px] text-slate-400">Khusus pembayaran langganan panel</p>
                    </div>
                </div>
                <span class="w-12 h-9 rounded-lg bg-gradient-to-br from-amber-100 via-amber-300 to-amber-600 shadow-inner ring-1 ring-amber-200/40" aria-hidden="true"></span>
            </div>

            <p class="mt-7 text-4xl sm:text-5xl font-extrabold tracking-tight tabular-nums">{{ $idr($balance) }}</p>
            <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
                @if ($bestAffordable)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/15 ring-1 ring-emerald-300/30 px-2.5 py-1 font-semibold text-emerald-200">
                        <i class="fa-solid fa-circle-check"></i> Cukup untuk paket {{ $bestAffordable['name'] }}
                    </span>
                @elseif ($monthlyPrice > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-400/15 ring-1 ring-amber-300/30 px-2.5 py-1 font-semibold text-amber-200">
                        <i class="fa-solid fa-circle-info"></i> Kurang {{ $idr($shortfall) }} untuk paket {{ $monthlyPkg['name'] }}
                    </span>
                @endif
                @if ($bonusBalance > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 ring-1 ring-white/15 px-2.5 py-1 font-semibold text-amber-200">
                        <i class="fa-solid fa-gift"></i> Termasuk bonus {{ $idr($bonusBalance) }}
                    </span>
                @endif
            </div>

            <div class="mt-7 flex flex-wrap items-end justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Pemilik</p>
                    <p class="text-sm font-bold truncate">{{ $tenant->name }}</p>
                    <p class="text-[11px] font-mono text-slate-400 truncate">{{ $tenant->subdomain }}.{{ config('mikpay.tenant_base_domain', 'mikpay.link') }}</p>
                </div>
                @if ($depositReady)
                    <button type="button" data-deposit-open class="relative inline-flex items-center gap-3 rounded-2xl bg-gradient-to-r from-amber-300 to-amber-400 text-slate-900 text-sm sm:text-base font-extrabold pl-2 pr-5 py-2 shadow-lg shadow-amber-500/30 ring-1 ring-amber-200/60 hover:from-amber-200 hover:to-amber-300 hover:-translate-y-0.5 transition">
                        @unless ($bestAffordable)
                            <span class="absolute inset-0 rounded-2xl ring-2 ring-amber-300 animate-ping opacity-30 pointer-events-none" aria-hidden="true"></span>
                        @endunless
                        <span class="w-9 h-9 rounded-xl bg-slate-900 text-amber-300 flex items-center justify-center"><i class="fa-solid fa-plus"></i></span>
                        Isi Saldo
                    </button>
                @endif
            </div>
        </div>

        <div class="relative flex items-start gap-2.5 border-t border-white/10 bg-white/[0.04] px-6 sm:px-7 py-3 text-[11px] leading-relaxed text-slate-300">
            <i class="fa-solid fa-circle-info mt-0.5 text-indigo-300"></i>
            <p>Saldo hanya dapat dipakai untuk membayar langganan MikPay. Saldo <b class="text-white">tidak dapat ditarik, ditransfer, atau ditukar dengan uang tunai</b>.</p>
        </div>
    </div>

    <div class="lg:col-span-2 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm flex flex-col">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-extrabold text-slate-900 dark:text-white">Status Langganan</p>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold tracking-wide {{ $isActive ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:ring-emerald-500/30 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:ring-rose-500/30 dark:text-rose-300' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                {{ $isActive ? 'AKTIF' : 'NONAKTIF' }}
            </span>
        </div>

        <div class="mt-5 flex items-center gap-5">
            <div class="relative w-28 h-28 shrink-0">
                <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                    <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" class="stroke-slate-100 dark:stroke-slate-800" />
                    <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" stroke-linecap="round" stroke="currentColor"
                            class="{{ $ringTone }} transition-all"
                            stroke-dasharray="{{ $ringCirc }}" stroke-dashoffset="{{ $ringCirc * (1 - max($progress, $isActive ? 3 : 0) / 100) }}" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-extrabold tabular-nums text-slate-900 dark:text-white">{{ $remainingDays ?? '—' }}</span>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">hari lagi</span>
                </div>
            </div>
            <div class="min-w-0 space-y-2.5">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Paket saat ini</p>
                    <p class="text-base font-extrabold text-slate-900 dark:text-white">{{ $planLabel }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $isActive ? 'Berlaku sampai' : 'Berakhir' }}</p>
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $subscriptionUntil ?: 'Belum ada masa aktif' }}</p>
                </div>
            </div>
        </div>

        <div id="perpanjang-otomatis" class="mt-5 rounded-2xl border {{ $autoRenew['enabled'] ? 'border-indigo-200 bg-indigo-50/50 dark:border-indigo-500/30 dark:bg-indigo-500/5' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/40' }} p-3.5 transition">
            <form method="POST" action="{{ panel_route('subscription.auto-renew') }}" class="flex items-center justify-between gap-3">
                @csrf
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center {{ $autoRenew['enabled'] ? 'bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-md shadow-indigo-500/30' : 'bg-white dark:bg-slate-900 text-slate-400 ring-1 ring-slate-200 dark:ring-slate-700' }}"><i class="fa-solid fa-arrows-rotate text-sm"></i></span>
                    <div class="min-w-0">
                        <p class="text-xs font-extrabold text-slate-900 dark:text-white">Perpanjang otomatis</p>
                        <p class="text-[11px] text-slate-500 truncate">
                            {{ $autoPkg['name'] ?? '-' }} · {{ $idr($autoPrice) }}
                            <button type="button" data-modal-open="autoPkgModal" class="ml-1 font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Ganti paket</button>
                        </p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0" title="{{ $autoRenew['enabled'] ? 'Matikan' : 'Aktifkan' }} perpanjang otomatis">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" class="peer sr-only" @checked($autoRenew['enabled']) onchange="this.form.requestSubmit()">
                    <span class="w-11 h-6 rounded-full bg-slate-300 dark:bg-slate-700 peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition"></span>
                    <span class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    <span class="sr-only">Perpanjang otomatis</span>
                </label>
            </form>

            <div class="mt-3 pt-3 border-t border-dashed {{ $autoRenew['enabled'] ? 'border-indigo-200 dark:border-indigo-500/30' : 'border-slate-200 dark:border-slate-700' }} text-[11px] leading-relaxed">
                @if (! $autoRenew['enabled'])
                    <p class="text-slate-500">Nyalakan agar paket diperpanjang dari saldo {{ $autoRenewHours }} jam sebelum masa aktif habis, panel tidak terkunci karena lupa bayar.</p>
                @elseif ($autoShort > 0)
                    <button type="button" @if ($depositReady) data-deposit-fill="{{ max($autoShort, $depositMin) }}" @else disabled @endif class="w-full text-left text-amber-700 dark:text-amber-300">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Saldo kurang <b>{{ $idr($autoShort) }}</b> untuk perpanjangan berikutnya.
                        @if ($depositReady)<span class="font-bold underline">Isi saldo</span>@endif
                    </button>
                @else
                    <p class="text-emerald-700 dark:text-emerald-300">
                        <i class="fa-solid fa-circle-check mr-1"></i>
                        Saldo cukup. Diperpanjang {{ $autoRenewAt && $autoRenewAt->isFuture() ? 'pada '.format_wib($autoRenewAt) : 'segera' }}.
                    </p>
                @endif
                @if ($autoRenew['enabled'] && $autoLast && ! ($autoLast['ok'] ?? false))
                    <p class="mt-1.5 text-rose-600 dark:text-rose-400"><i class="fa-solid fa-circle-exclamation mr-1"></i> Percobaan terakhir {{ format_wib(\Carbon\Carbon::parse($autoLast['at'])) }}: {{ $autoLast['message'] ?? '' }}</p>
                @endif
            </div>
        </div>

        <a href="#perpanjang" class="mt-auto pt-5">
            <span class="w-full inline-flex items-center justify-center gap-2 rounded-xl {{ $isActive ? 'border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800' : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/20' }} text-xs font-bold py-2.5 transition">
                <i class="fa-solid fa-rotate"></i> {{ $isActive ? 'Perpanjang lebih awal' : 'Aktifkan kembali' }}
            </span>
        </a>
    </div>
</div>

    @if ($pendingDeposit)
        <div class="rounded-2xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-4 py-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
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


{{-- Ganti paket perpanjang otomatis (modal) --}}
<div id="autoPkgModal" data-modal class="fixed inset-0 z-[80] hidden" role="dialog" aria-modal="true" aria-labelledby="autoPkgModalTitle">
    <div data-modal-close class="modal-backdrop absolute inset-0 bg-slate-950/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>
    <div class="relative flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4 pointer-events-none">
        <form method="POST" action="{{ panel_route('subscription.auto-renew') }}" class="modal-panel pointer-events-auto w-full sm:max-w-lg max-h-[92vh] overflow-y-auto rounded-t-3xl sm:rounded-3xl bg-white dark:bg-slate-900 shadow-2xl ring-1 ring-slate-200 dark:ring-slate-800 opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 transition duration-200">
            @csrf
            <input type="hidden" name="enabled" value="{{ $autoRenew['enabled'] ? 1 : 0 }}">
            <div class="flex items-center gap-3 px-5 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <span class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/30"><i class="fa-solid fa-arrows-rotate"></i></span>
                <div class="min-w-0 flex-1">
                    <h3 id="autoPkgModalTitle" class="text-sm font-extrabold text-slate-900 dark:text-white">Paket Perpanjang Otomatis</h3>
                    <p class="text-[11px] text-slate-500">Paket ini yang dibayar dari saldo setiap kali masa aktif akan habis.</p>
                </div>
                <button type="button" data-modal-close class="w-9 h-9 shrink-0 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-5 sm:p-6 space-y-2.5">
                @foreach ($packages as $key => $pkg)
                    @php $p = \App\Services\PartnerProgramService::applyDiscount((int) $pkg['price'], $referralDiscount); @endphp
                    <label class="relative block cursor-pointer">
                        <input type="radio" name="package" value="{{ $key }}" class="peer sr-only" @checked($autoRenew['package'] === $key)>
                        <span class="flex items-center justify-between gap-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 px-4 py-3.5 transition hover:border-indigo-300 peer-checked:border-indigo-500 peer-checked:bg-indigo-50/60 dark:peer-checked:bg-indigo-500/10">
                            <span class="min-w-0">
                                <span class="flex items-center gap-1.5 text-sm font-extrabold text-slate-900 dark:text-white">
                                    {{ $pkg['name'] }}
                                    @if ((string) $tenant->plan === $key)
                                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-50 border border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 px-1.5 py-0.5 rounded-full">Paket saat ini</span>
                                    @endif
                                </span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">{{ $pkg['duration'] }} hari masa aktif @if ($pkg['months'] > 1) · setara {{ $idr($pkg['monthly_equivalent']) }}/bulan @endif</span>
                            </span>
                            <span class="text-sm font-extrabold tabular-nums text-slate-900 dark:text-white">{{ $idr($p) }}</span>
                        </span>
                    </label>
                @endforeach
                @unless ($autoRenew['enabled'])
                    <p class="text-[11px] text-slate-400 pt-1">Perpanjang otomatis sedang mati. Paket ini dipakai setelah Anda menyalakannya.</p>
                @endunless
            </div>
            <div class="px-5 sm:px-6 pb-5 sm:pb-6 flex gap-2.5">
                <button type="button" data-modal-close class="flex-1 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold py-3 transition">Batal</button>
                <button type="submit" class="flex-1 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-extrabold py-3 shadow-lg shadow-indigo-600/25 transition">Simpan paket</button>
            </div>
        </form>
    </div>
</div>

{{-- Isi saldo (modal) --}}
@if ($depositReady)
<div id="depositModal" class="fixed inset-0 z-[80] hidden" role="dialog" aria-modal="true" aria-labelledby="depositModalTitle">
    <div data-deposit-close class="deposit-backdrop absolute inset-0 bg-slate-950/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>
    <div class="relative flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4 pointer-events-none">
        <div class="deposit-panel pointer-events-auto w-full sm:max-w-3xl max-h-[92vh] overflow-y-auto rounded-t-3xl sm:rounded-3xl bg-white dark:bg-slate-900 shadow-2xl ring-1 ring-slate-200 dark:ring-slate-800 opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 transition duration-200">
            <div class="sticky top-0 z-10 flex items-center gap-3 px-5 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur">
                <span class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/30"><i class="fa-solid fa-wallet"></i></span>
                <div class="min-w-0 flex-1">
                    <h3 id="depositModalTitle" class="text-sm font-extrabold text-slate-900 dark:text-white">Isi Saldo MikPay</h3>
                    <p class="text-[11px] text-slate-500">Saldo masuk otomatis begitu pembayaran lunas.</p>
                </div>
                <button type="button" data-deposit-close class="w-9 h-9 shrink-0 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            @if ($pendingDeposit)
                <div class="mx-5 sm:mx-6 mt-5 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-3.5 py-2.5 text-[11px] text-amber-800 dark:text-amber-200">
                    <i class="fa-solid fa-hourglass-half mr-1"></i> Masih ada deposit {{ $idr($pendingDeposit->amount) }} yang menunggu pembayaran. Selesaikan atau batalkan dulu sebelum membuat yang baru.
                </div>
            @endif

        <form method="POST" action="{{ panel_route('subscription.deposit') }}" id="depositForm" class="grid grid-cols-1 md:grid-cols-5">
            @csrf
            <input type="hidden" name="amount" id="depositAmount" value="">

            <div class="md:col-span-3 p-5 sm:p-6 space-y-5">
                <div>
                    <label for="depositAmountDisplay" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Nominal isi saldo</label>
                    <div id="depositAmountWrap" class="flex items-center rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 focus-within:border-indigo-500 focus-within:bg-white dark:focus-within:bg-slate-900 transition">
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
                        @foreach ([['fa-qrcode', 'QRIS & e-wallet'], ['fa-building-columns', 'Virtual Account Bank'], ['fa-store', 'Alfamart / Indomaret']] as [$icon, $label])
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                                <i class="fa-solid {{ $icon }} text-indigo-500"></i> {{ $label }}
                            </span>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1.5">Pilih metode di halaman pembayaran setelah klik tombol bayar.</p>
                </div>
            </div>

            <div class="md:col-span-2 p-5 sm:p-6 bg-slate-50 dark:bg-slate-800/30 border-t md:border-t-0 md:border-l border-slate-100 dark:border-slate-800 flex flex-col">
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
                    <p class="text-[10px] text-slate-400 text-center mt-2 leading-relaxed">
                        Anda akan diarahkan ke halaman pembayaran {{ $gatewayLabel }}. Dengan melanjutkan, Anda memahami saldo MikPay tidak dapat ditarik atau ditukar dengan uang tunai.
                    </p>
                </div>
            </div>
        </form>
        </div>
    </div>
</div>
@else
<div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 flex items-center gap-3 text-xs text-slate-500">
    <i class="fa-solid fa-plug-circle-xmark text-slate-400"></i> Isi saldo online sedang tidak tersedia. Silakan hubungi admin MikPay.
</div>
@endif

{{-- Pilih paket --}}
<div id="perpanjang" class="scroll-mt-24 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-5" data-balance="{{ (int) $balance }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-slate-900 dark:bg-indigo-500/20 text-white flex items-center justify-center"><i class="fa-solid fa-box-open"></i></span>
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Perpanjang Manual</h3>
                <p class="text-[11px] text-slate-500">Satu paket dengan semua fitur, pilih durasinya. Biaya dipotong dari saldo MikPay.</p>
            </div>
        </div>
    </div>

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
            <div class="renew-card relative rounded-2xl border-2 {{ $featured ? 'border-indigo-500 shadow-xl shadow-indigo-500/10' : 'border-slate-200 dark:border-slate-700' }} bg-white dark:bg-slate-800/40 p-5 flex flex-col"
                 data-package-name="{{ $pkg['name'] }}"
                 data-price="{{ $price }}"
                 data-featured="{{ $featured ? '1' : '0' }}">
                <div class="flex flex-wrap items-center gap-1.5 min-h-[20px]">
                    @if (! empty($pkg['featured_ribbon']))
                        <span class="text-[10px] font-extrabold uppercase tracking-wider {{ $featured ? 'text-white bg-indigo-600 border-indigo-600' : 'text-indigo-700 bg-indigo-50 border-indigo-200' }} border px-2 py-0.5 rounded-full">{{ $pkg['featured_ribbon'] }}</span>
                    @endif
                    @if ($autoRenew['enabled'] && $autoRenew['package'] === $key)
                        <span class="text-[10px] font-extrabold text-violet-700 bg-violet-50 border border-violet-200 dark:bg-violet-500/10 dark:border-violet-500/30 dark:text-violet-300 px-2 py-0.5 rounded-full"><i class="fa-solid fa-arrows-rotate"></i> Otomatis</span>
                    @endif
                </div>
                <h4 class="mt-2 text-sm font-extrabold text-slate-900 dark:text-white">{{ $pkg['name'] }}</h4>
                <p class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white mt-1.5 tabular-nums">
                    {{ $idr($price) }}
                    <span class="text-xs font-medium tracking-normal text-slate-500">{{ $pkg['duration_label'] }}</span>
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
                <p class="text-[11px] text-slate-500 mt-2 flex-1">
                    @if ($pkg['months'] > 1)
                        Setara {{ $idr($pkg['monthly_equivalent']) }}/bulan ·
                    @endif
                    {{ $pkg['duration'] }} hari masa aktif
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

    <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-1.5 text-[11px] text-slate-600 dark:text-slate-300 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 px-4 py-3.5">
        @foreach ($features as $f)
            <li class="flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-emerald-500"></i> {{ $f }}</li>
        @endforeach
    </ul>
</div>

{{-- Ketentuan saldo --}}
<div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 sm:p-6 shadow-sm">
    <div class="flex items-center gap-3">
        <span class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center"><i class="fa-solid fa-scale-balanced text-sm"></i></span>
        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Ketentuan Saldo MikPay</h3>
    </div>
    <ul class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2.5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
        <li class="flex gap-2"><i class="fa-solid fa-check mt-0.5 text-indigo-500"></i> Saldo hanya dapat digunakan untuk membayar dan memperpanjang langganan MikPay.</li>
        <li class="flex gap-2"><i class="fa-solid fa-ban mt-0.5 text-rose-500"></i> Saldo tidak dapat ditarik, dicairkan, atau ditukar dengan uang tunai.</li>
        <li class="flex gap-2"><i class="fa-solid fa-ban mt-0.5 text-rose-500"></i> Saldo tidak dapat dipindahkan ke akun atau tenant lain.</li>
        <li class="flex gap-2"><i class="fa-solid fa-check mt-0.5 text-indigo-500"></i> Saldo bonus dari MikPay mengikuti ketentuan yang sama.</li>
    </ul>
    <p class="mt-4 text-[11px] text-slate-400">
        Kesalahan pembayaran (misalnya terdebit ganda) ditangani sesuai <a href="{{ route('legal.refund') }}" target="_blank" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Kebijakan Pengembalian Dana</a>.
    </p>
</div>

{{-- Riwayat --}}
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
    <div class="px-5 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
        <span class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center"><i class="fa-solid fa-clock-rotate-left text-sm"></i></span>
        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Riwayat Transaksi</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-50 dark:bg-slate-800/60 text-[10px] text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-3 font-bold">Transaksi</th>
                    <th class="text-left px-5 py-3 font-bold">Detail</th>
                    <th class="text-right px-5 py-3 font-bold">Nominal</th>
                    <th class="text-left px-5 py-3 font-bold">Status</th>
                    <th class="text-left px-5 py-3 font-bold">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($transactions as $p)
                    @php $auto = str_starts_with((string) $p->approved_by, 'auto-renew:'); @endphp
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center {{ $p->isDeposit() ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10' }}">
                                    <i class="fa-solid {{ $p->isDeposit() ? 'fa-arrow-down' : ($auto ? 'fa-arrows-rotate' : 'fa-arrow-up') }} text-[10px]"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $p->purposeLabel() }}{{ $auto ? ' (otomatis)' : '' }}</p>
                                    <p class="font-mono text-[10px] text-slate-400">{{ $p->payment_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                            {{ $p->isDeposit() ? 'Isi saldo' : \App\Services\SubscriptionPackageService::planLabel($p->package) }}
                        </td>
                        <td class="px-5 py-3 text-right font-bold tabular-nums {{ $p->isDeposit() ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                            {{ $p->isDeposit() ? '+' : '−' }}{{ $idr($p->amount) }}
                        </td>
                        <td class="px-5 py-3">
                            @if ($p->status === 'pending')
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">menunggu bayar</span>
                            @elseif ($p->status === 'approved')
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">sukses</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold">{{ in_array($p->rejected_by, ['xendit', 'andidev_pay'], true) ? 'kedaluwarsa' : 'batal' }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-slate-500 whitespace-nowrap">{{ format_wib($p->created_at) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-slate-400">
                            <i class="fa-solid fa-receipt text-2xl mb-2 block text-slate-300"></i>
                            Belum ada transaksi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
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

    const setAmount = (value, fromInput = false) => {
        value = Math.max(0, Math.floor(Number(value) || 0));
        hidden.value = value > 0 ? String(value) : '';
        if (!fromInput) display.value = value > 0 ? value.toLocaleString('id-ID') : '';
        sumAmount.textContent = '+ ' + idr(value);
        sumAfter.textContent = idr(balance + value);
        const best = pkgs.filter((p) => p.price <= balance + value).sort((a, b) => b.days - a.days)[0];
        sumCover.textContent = value > 0 && best ? '✓ Cukup untuk paket ' + best.name : '';
        if (shortfallHint) shortfallHint.classList.toggle('hidden', !!best);
        wrap.classList.remove('border-rose-400');
        hint.classList.remove('text-rose-600');
        hint.textContent = hintDefault;
    };

    let submitting = false;
    const modal = document.getElementById('depositModal');
    const backdrop = modal.querySelector('.deposit-backdrop');
    const panel = modal.querySelector('.deposit-panel');
    const panelHidden = ['opacity-0', 'translate-y-6', 'sm:scale-95'];
    document.body.appendChild(modal);

    const openModal = () => {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove(...panelHidden);
        });
        setTimeout(() => display.focus({ preventScroll: true }), 220);
    };
    const closeModal = () => {
        if (modal.classList.contains('hidden') || submitting) return;
        backdrop.classList.add('opacity-0');
        panel.classList.add(...panelHidden);
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => modal.classList.add('hidden'), 200);
        if (location.hash === '#isi-saldo') history.replaceState(null, '', location.pathname + location.search);
    };

    document.querySelectorAll('[data-deposit-open]').forEach((b) => b.addEventListener('click', openModal));
    document.querySelectorAll('[data-deposit-fill]').forEach((b) => b.addEventListener('click', () => {
        setAmount(b.dataset.depositFill);
        if (modal.classList.contains('hidden')) openModal();
    }));
    modal.querySelectorAll('[data-deposit-close]').forEach((b) => b.addEventListener('click', closeModal));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
    document.querySelectorAll('a[href$="#isi-saldo"]').forEach((a) => a.addEventListener('click', (e) => { e.preventDefault(); openModal(); }));
    if (location.hash === '#isi-saldo' || @json($errors->has('amount'))) openModal();
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        submitting = false;
        submit.disabled = @json((bool) $pendingDeposit);
        document.getElementById('depositSubmitLabel').textContent = @json($pendingDeposit ? 'Selesaikan deposit pending dulu' : 'Lanjut ke Pembayaran');
    });

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
        submitting = true;
        submit.disabled = true;
        document.getElementById('depositSubmitLabel').textContent = 'Membuat tagihan…';
    });
})();

(function () {
    const hiddenCls = ['opacity-0', 'translate-y-6', 'sm:scale-95'];
    document.querySelectorAll('[data-modal]').forEach((modal) => {
        document.body.appendChild(modal);
        const backdrop = modal.querySelector('.modal-backdrop');
        const panel = modal.querySelector('.modal-panel');
        const open = () => {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                panel.classList.remove(...hiddenCls);
            });
        };
        const close = () => {
            if (modal.classList.contains('hidden')) return;
            backdrop.classList.add('opacity-0');
            panel.classList.add(...hiddenCls);
            document.body.classList.remove('overflow-hidden');
            setTimeout(() => modal.classList.add('hidden'), 200);
        };
        document.querySelectorAll('[data-modal-open="' + modal.id + '"]').forEach((b) => b.addEventListener('click', open));
        modal.querySelectorAll('[data-modal-close]').forEach((b) => b.addEventListener('click', close));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
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
