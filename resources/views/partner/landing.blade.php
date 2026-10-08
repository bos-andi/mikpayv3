@extends('layouts.app')

@section('title', 'Program Affiliate MikPay — Rekomendasikan & Dapatkan Komisi')
@section('meta_description', 'Gabung Program Affiliate MikPay. Bagikan link referral ke pemilik ISP & RT RW Net, dapatkan komisi '.rtrim(rtrim(number_format($settings['first_percent'], 2, ',', '.'), '0'), ',').'% dari pembayaran pertama dan komisi perpanjangan setiap pelanggan Anda membayar.')

@php
    $pct = fn (float $v) => rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
    $rp = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
    $first = $pct($settings['first_percent']);
    $recurring = $pct($settings['recurring_percent']);
    $months = (int) $settings['recurring_months'];
    $holdDays = (int) $settings['hold_days'];
    $cookieDays = (int) $settings['cookie_days'];
    $minWd = (int) $settings['min_withdrawal'];
    $hasRecurring = $settings['recurring_percent'] > 0 && $months > 0;
    $basePrice = (int) ($packages['bulan1']['price'] ?? 35000);
    $discPct = $settings['enabled'] ? (float) $settings['referral_discount_percent'] : 0.0;
    $firstBase = fn (int $price) => \App\Services\PartnerProgramService::applyDiscount($price, $discPct);
    $perFirst = (int) floor($firstBase($basePrice) * $settings['first_percent'] / 100);
    $calcPackages = collect($packages)->map(fn ($p) => [
        'key' => $p['key'],
        'name' => $p['name'],
        'price' => (int) $p['price'],
        'months' => (int) ($p['months'] ?? 1),
    ])->values();
    $registerUrl = auth()->user()?->isPartner() ? route('partner.dashboard') : route('partner.register');
@endphp

@section('content')
{{-- HERO --}}
<section class="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden bg-slate-950 text-white">
    <div class="absolute inset-0 bg-grid-dark opacity-60"></div>
    <div class="glow-blob w-[560px] h-[560px] bg-indigo-600/30 -top-48 -left-40"></div>
    <div class="glow-blob w-[480px] h-[480px] bg-emerald-500/20 top-20 -right-40"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-12 gap-14 lg:gap-10 items-center">
            <div class="lg:col-span-6 text-center lg:text-left">
                <span class="reveal inline-flex items-center gap-2 pl-1.5 pr-3.5 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs font-bold text-slate-200 backdrop-blur">
                    <span class="bg-gradient-to-r from-emerald-400 to-cyan-400 text-slate-950 text-[10px] px-2 py-0.5 rounded-full font-extrabold">AFFILIATE</span>
                    Program Afiliasi Resmi MikPay
                </span>

                <h1 class="reveal mt-6 text-4xl sm:text-5xl xl:text-6xl font-extrabold tracking-tight leading-[1.08]">
                    Rekomendasikan MikPay,
                    <span class="bg-gradient-to-r from-emerald-300 via-cyan-300 to-indigo-300 bg-clip-text text-transparent">dapat komisi setiap mereka bayar.</span>
                </h1>

                <p class="reveal mt-6 text-base sm:text-lg text-slate-300 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    Kenal pemilik ISP, RT RW Net, atau teknisi jaringan? Bagikan link referral Anda.
                    Dapatkan <b class="text-white">{{ $first }}% dari pembayaran pertama</b>
                    @if ($hasRecurring)
                        dan <b class="text-white">{{ $recurring }}% dari setiap perpanjangan</b> selama {{ $months }} bulan.
                    @endif
                    Tanpa modal, tanpa target.
                </p>

                <div class="reveal mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3">
                    <a href="{{ $registerUrl }}" class="group w-full sm:w-auto inline-flex items-center justify-center gap-2.5 text-sm font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 px-7 py-4 rounded-2xl shadow-xl shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition">
                        <i class="fa-solid fa-handshake"></i>
                        <span>Daftar Jadi Affiliate — Gratis</span>
                    </a>
                    <a href="#kalkulator" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 text-sm font-bold text-white px-7 py-4 rounded-2xl border border-white/15 bg-white/5 hover:bg-white/10 transition">
                        <i class="fa-solid fa-calculator text-emerald-300"></i>
                        <span>Hitung Potensi Komisi</span>
                    </a>
                </div>

                <div class="reveal mt-8 flex flex-wrap items-center justify-center lg:justify-start gap-x-6 gap-y-2 text-xs text-slate-400 font-semibold">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-emerald-400"></i> Gratis daftar</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-emerald-400"></i> Komisi tercatat otomatis</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-emerald-400"></i> Cair ke rekening / e-wallet</span>
                </div>
            </div>

            {{-- Mock panel affiliate --}}
            <div class="lg:col-span-6 relative reveal">
                <div class="relative mx-auto max-w-md lg:max-w-lg">
                    <div class="absolute -inset-6 rounded-[2.5rem] bg-gradient-to-tr from-emerald-500/20 via-cyan-500/10 to-indigo-500/30 blur-2xl"></div>

                    <div class="relative rounded-3xl border border-white/10 bg-slate-900/80 backdrop-blur-xl shadow-2xl overflow-hidden">
                        <div class="p-6 bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 relative overflow-hidden">
                            <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-white/10"></div>
                            <div class="absolute right-10 -bottom-16 w-32 h-32 rounded-full bg-emerald-400/20"></div>
                            <div class="relative flex items-start justify-between">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-widest text-indigo-200">Saldo komisi</p>
                                    <p class="mt-1 text-3xl font-extrabold tracking-tight">Rp1.245.500</p>
                                    <p class="mt-1 text-[11px] text-indigo-200"><i class="fa-solid fa-arrow-trend-up text-emerald-300"></i> +Rp318.000 bulan ini</p>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-white/10 border border-white/15">Ilustrasi</span>
                            </div>
                            <div class="relative mt-5 flex items-center gap-2 rounded-xl bg-white/10 border border-white/15 px-3 py-2">
                                <i class="fa-solid fa-link text-[11px] text-indigo-200"></i>
                                <span class="font-mono text-xs truncate">mikpay.link/r/ANDI27</span>
                                <span class="ml-auto text-[10px] font-bold px-2 py-1 rounded-md bg-white text-indigo-700"><i class="fa-regular fa-copy"></i> Salin</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 divide-x divide-white/5 border-b border-white/5">
                            @foreach ([['Klik link', '1.284'], ['Pendaftar', '46'], ['Berbayar', '29']] as $s)
                                <div class="px-4 py-3 text-center">
                                    <p class="text-lg font-extrabold">{{ $s[1] }}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold">{{ $s[0] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="p-4 space-y-2">
                            @foreach ([
                                ['NetCepat WiFi', 'Paket 3 bulan · pembayaran pertama', '+'.$rp((int) floor($firstBase(80000) * $settings['first_percent'] / 100)), 'emerald'],
                                ['Barokah Net', 'Paket 1 bulan · perpanjangan', '+'.$rp((int) floor($basePrice * $settings['recurring_percent'] / 100)), 'cyan'],
                                ['Sinar RT RW Net', 'Paket 6 bulan · pembayaran pertama', '+'.$rp((int) floor($firstBase(155000) * $settings['first_percent'] / 100)), 'emerald'],
                            ] as $row)
                                <div class="flex items-center gap-3 rounded-xl bg-white/[0.03] border border-white/5 px-3 py-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-{{ $row[3] }}-500/15 text-{{ $row[3] }}-300 flex items-center justify-center text-xs"><i class="fa-solid fa-coins"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold truncate">{{ $row[0] }}</p>
                                        <p class="text-[10px] text-slate-400 truncate">{{ $row[1] }}</p>
                                    </div>
                                    <span class="ml-auto text-xs font-extrabold text-{{ $row[3] }}-300">{{ $row[2] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="hidden sm:flex animate-float absolute -left-10 bottom-10 items-center gap-3 bg-white text-slate-900 rounded-2xl shadow-xl shadow-black/30 px-4 py-3">
                        <span class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center"><i class="fa-solid fa-building-columns"></i></span>
                        <div class="text-left">
                            <p class="text-[11px] font-extrabold">Komisi ditransfer</p>
                            <p class="text-[10px] text-slate-500">langsung ke rekening Anda</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- KEY NUMBERS --}}
<section class="relative -mt-10 z-10">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            @foreach (array_values(array_filter([
                ['fa-percent', $first.'%', 'Komisi pembayaran pertama', 'from-emerald-500 to-teal-500'],
                $hasRecurring ? ['fa-rotate', $recurring.'%', 'Komisi perpanjangan '.$months.' bulan', 'from-cyan-500 to-sky-500'] : null,
                ['fa-cookie-bite', $cookieDays.' hari', 'Masa berlaku link referral', 'from-indigo-500 to-violet-500'],
                ['fa-wallet', $rp($minWd), 'Minimal pencairan komisi', 'from-amber-500 to-orange-500'],
            ])) as $card)
                <div class="reveal rounded-2xl bg-white border border-slate-200 shadow-lg shadow-slate-900/5 p-5">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $card[3] }} text-white flex items-center justify-center shadow-md"><i class="fa-solid {{ $card[0] }}"></i></span>
                    <p class="mt-3 text-2xl font-extrabold text-slate-900 tracking-tight">{{ $card[1] }}</p>
                    <p class="text-xs text-slate-500 font-semibold">{{ $card[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- HOW IT WORKS --}}
<section id="cara-kerja-affiliate" class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
            <p class="reveal text-xs font-extrabold uppercase tracking-widest text-indigo-600">Cara Kerja</p>
            <h2 class="reveal mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">4 langkah mulai menghasilkan</h2>
            <p class="reveal mt-4 text-slate-600">Semua tercatat otomatis — Anda cukup fokus merekomendasikan.</p>
        </div>

        <div class="mt-14 grid sm:grid-cols-2 lg:grid-cols-4 gap-5 relative">
            <div class="hidden lg:block absolute top-10 left-[12%] right-[12%] h-px bg-gradient-to-r from-indigo-200 via-cyan-200 to-emerald-200"></div>
            @foreach ([
                ['fa-user-plus', 'Daftar affiliate', 'Isi formulir singkat. Tim MikPay meninjau & mengaktifkan akun Anda.'],
                ['fa-link', 'Dapat kode & link', 'Anda menerima kode referral unik dan link siap dibagikan.'],
                ['fa-share-nodes', 'Bagikan', 'Kirim ke grup WhatsApp, komunitas, atau langsung ke pemilik ISP yang Anda kenal.'],
                ['fa-money-bill-wave', 'Terima komisi', 'Saat rujukan Anda membayar langganan, komisi masuk ke panel & bisa dicairkan.'],
            ] as $i => $step)
                <div class="reveal relative rounded-2xl border border-slate-200 bg-white p-6 hover:shadow-xl hover:shadow-indigo-900/5 hover:-translate-y-1 transition">
                    <div class="flex items-center gap-3">
                        <span class="relative w-12 h-12 rounded-2xl gradient-bg text-white flex items-center justify-center shadow-lg shadow-indigo-500/25"><i class="fa-solid {{ $step[0] }}"></i></span>
                        <span class="text-4xl font-extrabold text-slate-100">0{{ $i + 1 }}</span>
                    </div>
                    <h3 class="mt-4 font-extrabold text-slate-900">{{ $step[1] }}</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $step[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CALCULATOR --}}
<section id="kalkulator" class="py-20 lg:py-28 bg-slate-50 relative overflow-hidden">
    <div class="absolute inset-0 bg-grid opacity-60"></div>
    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-10 items-center">
            <div>
                <p class="reveal text-xs font-extrabold uppercase tracking-widest text-emerald-600">Kalkulator Komisi</p>
                <h2 class="reveal mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Berapa yang bisa Anda hasilkan?</h2>
                <p class="reveal mt-4 text-slate-600 leading-relaxed">
                    Geser jumlah ISP yang berhasil Anda ajak dan pilih paket yang mereka ambil.
                    Hitungan memakai tarif komisi yang berlaku saat ini.
                </p>
                <ul class="reveal mt-6 space-y-3 text-sm text-slate-700">
                    <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-500 mt-0.5"></i> Komisi pertama {{ $first }}% dihitung dari harga paket yang dibayar.</li>
                    @if ($hasRecurring)
                        <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-500 mt-0.5"></i> Setiap kali pelanggan perpanjang, Anda dapat {{ $recurring }}% lagi dari harga paketnya — terus berulang selama {{ $months }} bulan.</li>
                    @endif
                    <li class="flex gap-3"><i class="fa-solid fa-circle-check text-emerald-500 mt-0.5"></i> Pelanggan cukup daftar lewat link Anda dalam {{ $cookieDays }} hari, atau mengisi kode referral Anda.</li>
                    @if ($settings['enabled'] && $settings['referral_discount_percent'] > 0)
                        <li class="flex gap-3"><i class="fa-solid fa-gift text-emerald-500 mt-0.5"></i> Pelanggan Anda dapat diskon {{ rtrim(rtrim(number_format($settings['referral_discount_percent'], 2, ',', ''), '0'), ',') }}% di pembayaran pertama — lebih mudah diajak bergabung.</li>
                    @endif
                </ul>
            </div>

            <div class="reveal rounded-3xl bg-white border border-slate-200 shadow-2xl shadow-indigo-900/10 p-6 sm:p-8">
                <div class="flex items-center justify-between">
                    <label for="calcCount" class="text-sm font-bold text-slate-700">Jumlah ISP yang Anda ajak</label>
                    <span class="text-sm font-extrabold text-indigo-700 bg-indigo-50 px-3 py-1 rounded-lg"><span id="calcCountLabel">5</span> ISP</span>
                </div>
                <input id="calcCount" type="range" min="1" max="50" value="5" class="mt-3 w-full accent-indigo-600">

                <p class="mt-6 text-sm font-bold text-slate-700">Paket yang diambil</p>
                <div class="mt-2 grid grid-cols-3 gap-2" id="calcPackages">
                    @foreach ($calcPackages as $i => $p)
                        <button type="button" data-calc-package="{{ $p['key'] }}"
                                class="calc-pkg rounded-xl border px-3 py-2.5 text-left transition {{ $i === 0 ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/20' : 'border-slate-200 hover:border-indigo-300' }}">
                            <span class="block text-xs font-extrabold text-slate-900">{{ $p['name'] }}</span>
                            <span class="block text-[11px] text-slate-500">{{ $rp($p['price']) }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-extrabold uppercase tracking-widest text-slate-500">Komisi dari 1 ISP</p>
                    <div class="mt-3 space-y-2 text-xs">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-600">Pembayaran pertama <span class="text-slate-400" id="calcFirstFormula"></span></span>
                            <b class="text-slate-900 whitespace-nowrap" id="calcFirst">Rp0</b>
                        </div>
                        @if ($hasRecurring)
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-600"><span id="calcRecurLabel">Setiap bulan</span> <span class="text-slate-400" id="calcRecurFormula"></span></span>
                                <b class="text-emerald-700 whitespace-nowrap" id="calcRecur">Rp0</b>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3 pt-2 border-t border-slate-200">
                            <span class="font-bold text-slate-700">Total 1 ISP selama 12 bulan</span>
                            <b class="text-indigo-700 whitespace-nowrap" id="calcPerYear">Rp0</b>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl bg-gradient-to-br from-slate-900 to-indigo-950 text-white p-5">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-indigo-200">Jika Anda mengajak <span id="calcCountLabel2">5</span> ISP</p>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-white/5 border border-white/10 p-3">
                            <p class="text-[10px] text-slate-300 font-semibold">Komisi pertama <span class="text-slate-400">(sekali)</span></p>
                            <p class="text-lg font-extrabold text-emerald-300" id="calcFirstTotal">Rp0</p>
                        </div>
                        <div class="rounded-xl bg-white/5 border border-white/10 p-3">
                            <p class="text-[10px] text-slate-300 font-semibold">Komisi rutin <span class="text-slate-400" id="calcRecurUnit">/bulan</span></p>
                            <p class="text-lg font-extrabold text-cyan-300" id="calcRecurTotal">Rp0</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-white/10 flex items-end justify-between gap-3">
                        <p class="text-xs text-slate-300 font-semibold">Total selama 12 bulan</p>
                        <p class="text-3xl font-extrabold tracking-tight" id="calcYear">Rp0</p>
                    </div>
                    <p class="mt-3 text-[11px] text-slate-300 leading-relaxed" id="calcExplain"></p>
                </div>
                <p class="mt-3 text-[11px] text-slate-400 leading-relaxed">Simulasi dengan asumsi semua rujukan terus berlangganan. Hasil sebenarnya bergantung pada pembayaran pelanggan.</p>
            </div>
        </div>
    </div>
</section>

{{-- WHY EASY TO SELL --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-12 gap-12 items-start">
            <div class="lg:col-span-5 lg:sticky lg:top-28">
                <p class="reveal text-xs font-extrabold uppercase tracking-widest text-indigo-600">Kenapa Mudah Direkomendasikan</p>
                <h2 class="reveal mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Produk yang memang dibutuhkan ISP lokal.</h2>
                <p class="reveal mt-4 text-slate-600 leading-relaxed">
                    Anda tidak perlu jago jualan. MikPay menyelesaikan masalah harian pemilik RT RW Net:
                    tagihan manual, pelanggan telat bayar, dan router yang susah diremote.
                </p>
                <a href="{{ route('home') }}" target="_blank" class="reveal mt-6 inline-flex items-center gap-2 text-sm font-bold text-indigo-700 hover:text-indigo-800">
                    Lihat fitur lengkap MikPay <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                </a>
            </div>
            <div class="lg:col-span-7 grid sm:grid-cols-2 gap-4">
                @foreach ([
                    ['fa-gift', 'Trial gratis '.$trialDays.' hari', 'Calon pelanggan bisa coba dulu tanpa bayar — lebih mudah diyakinkan.', 'emerald'],
                    ['fa-tags', 'Harga terjangkau', 'Mulai '.$rp($basePrice).'/bulan untuk pelanggan tak terbatas.', 'indigo'],
                    ['fa-shield-halved', 'Remote VPN gratis', 'Winbox, API, Web OLT bisa diakses dari mana saja tanpa IP publik.', 'cyan'],
                    ['fa-robot', 'Auto isolir & WA', 'Tagihan dan pengingat WhatsApp terkirim otomatis.', 'violet'],
                    ['fa-ticket', 'Voucher hotspot', 'Generate & cetak voucher langsung dari panel.', 'amber'],
                    ['fa-map-location-dot', 'Peta ODP & laporan', 'GIS jaringan dan laporan keuangan lengkap.', 'rose'],
                ] as $f)
                    <div class="reveal rounded-2xl border border-slate-200 p-5 hover:border-{{ $f[3] }}-200 hover:bg-{{ $f[3] }}-50/40 transition">
                        <span class="w-10 h-10 rounded-xl bg-{{ $f[3] }}-50 text-{{ $f[3] }}-600 flex items-center justify-center"><i class="fa-solid {{ $f[0] }}"></i></span>
                        <h3 class="mt-3 font-extrabold text-slate-900">{{ $f[1] }}</h3>
                        <p class="mt-1 text-sm text-slate-600 leading-relaxed">{{ $f[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- WHO --}}
<section class="py-20 lg:py-24 bg-slate-950 text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-grid-dark opacity-50"></div>
    <div class="glow-blob w-[420px] h-[420px] bg-indigo-600/25 -bottom-40 left-1/3"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
            <p class="reveal text-xs font-extrabold uppercase tracking-widest text-emerald-300">Cocok Untuk</p>
            <h2 class="reveal mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight">Siapa pun yang dekat dengan dunia jaringan</h2>
        </div>
        <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach ([
                ['fa-screwdriver-wrench', 'Teknisi & installer', 'Sering setting MikroTik untuk klien.'],
                ['fa-store', 'Toko perangkat', 'Jual router, ONT, dan perangkat jaringan.'],
                ['fa-user-tie', 'Konsultan RT RW Net', 'Membantu orang membangun usaha WiFi.'],
                ['fa-people-group', 'Admin komunitas', 'Mengelola grup MikroTik / ISP lokal.'],
                ['fa-video', 'Kreator konten', 'Membuat tutorial jaringan di YouTube/TikTok.'],
            ] as $w)
                <div class="reveal rounded-2xl border border-white/10 bg-white/[0.03] p-5 hover:bg-white/[0.06] transition">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-400 text-slate-950 flex items-center justify-center"><i class="fa-solid {{ $w[0] }}"></i></span>
                    <h3 class="mt-3 font-extrabold">{{ $w[1] }}</h3>
                    <p class="mt-1 text-sm text-slate-400">{{ $w[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- PANEL FEATURES --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-12 items-center">
        <div class="reveal order-2 lg:order-1 rounded-3xl border border-slate-200 bg-slate-50 p-5 shadow-xl shadow-slate-900/5">
            <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <p class="text-xs font-extrabold text-slate-900">Riwayat Komisi</p>
                    <span class="text-[10px] font-bold text-slate-400">Ilustrasi</span>
                </div>
                <div class="divide-y divide-slate-100 text-xs">
                    @foreach ([
                        ['NetCepat WiFi', 'Pembayaran pertama', 'Bisa ditarik', 'emerald', $firstBase(80000), $settings['first_percent']],
                        ['Barokah Net', 'Perpanjangan', 'Tertahan', 'amber', $basePrice, $settings['recurring_percent']],
                        ['Sinar Net', 'Pembayaran pertama', 'Bisa ditarik', 'emerald', $firstBase(155000), $settings['first_percent']],
                        ['Mandiri WiFi', 'Pembayaran pertama', 'Tertahan', 'amber', $firstBase($basePrice), $settings['first_percent']],
                    ] as $c)
                        <div class="flex items-center gap-3 px-4 py-3">
                            <span class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-extrabold">{{ substr($c[0], 0, 1) }}</span>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800 truncate">{{ $c[0] }}</p>
                                <p class="text-[10px] text-slate-400">{{ $c[1] }} · {{ $pct($c[5]) }}% × {{ $rp($c[4]) }}</p>
                            </div>
                            <div class="ml-auto text-right">
                                <p class="font-extrabold text-slate-900">{{ $rp((int) floor($c[4] * $c[5] / 100)) }}</p>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full border bg-{{ $c[3] }}-50 text-{{ $c[3] }}-700 border-{{ $c[3] }}-200">{{ $c[2] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="order-1 lg:order-2">
            <p class="reveal text-xs font-extrabold uppercase tracking-widest text-indigo-600">Panel Affiliate</p>
            <h2 class="reveal mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Transparan dari klik sampai pencairan.</h2>
            <div class="mt-8 space-y-5">
                @foreach ([
                    ['fa-chart-line', 'Dashboard real-time', 'Pantau klik link, pendaftar, pelanggan berbayar, dan saldo komisi.'],
                    ['fa-list-check', 'Riwayat lengkap', 'Setiap komisi tercatat beserta paket, persentase, dan statusnya.'],
                    ['fa-building-columns', 'Pencairan mudah', 'Ajukan penarikan ke rekening bank atau e-wallet dari panel.'],
                    ['fa-bullhorn', 'Materi promosi', 'Template pesan siap kirim ke WhatsApp, Telegram, dan media sosial.'],
                ] as $f)
                    <div class="reveal flex gap-4">
                        <span class="w-11 h-11 shrink-0 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="fa-solid {{ $f[0] }}"></i></span>
                        <div>
                            <h3 class="font-extrabold text-slate-900">{{ $f[1] }}</h3>
                            <p class="text-sm text-slate-600 mt-0.5">{{ $f[2] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section id="faq-affiliate" class="py-20 lg:py-28 bg-slate-50">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <p class="reveal text-xs font-extrabold uppercase tracking-widest text-indigo-600">FAQ</p>
            <h2 class="reveal mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Pertanyaan seputar Program Affiliate</h2>
        </div>
        <div class="mt-10 space-y-3">
            @foreach (array_values(array_filter([
                ['Apakah ada biaya untuk menjadi affiliate?', 'Tidak ada. Pendaftaran Program Affiliate MikPay 100% gratis dan tanpa target penjualan.'],
                ['Berapa komisi yang saya dapat?', "Anda mendapat {$first}% dari pembayaran langganan pertama pelanggan rujukan".($hasRecurring ? ", lalu {$recurring}% dari setiap perpanjangan selama {$months} bulan sejak pembayaran pertama." : '.').' Contoh: rujukan membayar paket '.$rp($firstBase($basePrice)).($discPct > 0 ? ' (setelah diskon referral)' : '').' → komisi Anda '.$rp($perFirst).'.'],
                ['Bagaimana sistem tahu pelanggan itu rujukan saya?', "Saat calon pelanggan membuka link referral Anda, kode Anda tersimpan di browser mereka selama {$cookieDays} hari. Mereka juga bisa mengetik kode referral Anda secara manual di formulir pendaftaran."],
                ['Kapan komisi bisa dicairkan?', ($holdDays > 0 ? "Komisi ditahan {$holdDays} hari setelah pembayaran untuk memastikan transaksi valid, lalu otomatis berstatus \"Bisa ditarik\". " : 'Komisi langsung bisa ditarik setelah pembayaran terkonfirmasi. ').'Minimal pencairan '.$rp($minWd).'.'.($settings['payout_day'] > 0 ? ' Pencairan dikirim setiap tanggal '.$settings['payout_day'].' tiap bulan.' : '')],
                ['Ke mana komisi dikirim?', 'Ke rekening bank atau e-wallet atas nama Anda. Untuk keamanan, rekening diverifikasi sekali dengan foto buku tabungan dan KTP sebelum pencairan pertama.'],
                ['Apakah masa trial gratis juga dapat komisi?', 'Komisi dihitung saat pelanggan rujukan benar-benar membayar paket langganan. Trial gratis tidak menghasilkan komisi, tetapi pelanggan tetap tercatat sebagai rujukan Anda.'],
                ['Bolehkah saya memakai kode referral sendiri?', 'Tidak. Kode referral hanya berlaku untuk pelanggan baru selain pemilik akun affiliate.'],
            ])) as $i => $faq)
                <details class="reveal group rounded-2xl border border-slate-200 bg-white px-5 py-4 open:shadow-lg open:shadow-slate-900/5 transition" @if ($i === 0) open @endif>
                    <summary class="flex items-center justify-between gap-4 cursor-pointer list-none">
                        <span class="font-bold text-slate-900 text-sm sm:text-base">{{ $faq[0] }}</span>
                        <span class="w-8 h-8 shrink-0 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center group-open:bg-indigo-600 group-open:text-white transition">
                            <i class="fa-solid fa-plus text-xs group-open:rotate-45 transition"></i>
                        </span>
                    </summary>
                    <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ $faq[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-20 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-indigo-700 via-indigo-800 to-slate-950 px-6 py-14 sm:px-14 text-center text-white shadow-2xl shadow-indigo-900/30">
            <div class="absolute inset-0 bg-grid-dark opacity-40"></div>
            <div class="glow-blob w-[360px] h-[360px] bg-emerald-400/25 -top-32 -right-24"></div>
            <div class="relative">
                <span class="inline-flex w-14 h-14 rounded-2xl bg-white/10 border border-white/15 items-center justify-center text-2xl"><i class="fa-solid fa-handshake-angle"></i></span>
                <h2 class="mt-5 text-3xl sm:text-4xl font-extrabold tracking-tight">Siap tumbuh bersama MikPay?</h2>
                <p class="mt-4 text-indigo-100 max-w-xl mx-auto">Daftar sekarang, dapatkan kode referral Anda, dan mulai hasilkan komisi dari jaringan yang sudah Anda punya.</p>
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ $registerUrl }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 text-sm font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 px-8 py-4 rounded-2xl shadow-xl hover:-translate-y-0.5 transition">
                        <i class="fa-solid fa-user-plus"></i> Daftar Jadi Affiliate
                    </a>
                    <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 text-sm font-bold text-white px-8 py-4 rounded-2xl border border-white/20 hover:bg-white/10 transition">
                        Sudah jadi affiliate? Masuk
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const packages = @json($calcPackages);
    const firstPct = {{ (float) $settings['first_percent'] }};
    const discPct = {{ $discPct }};
    const firstBase = (price) => price - Math.floor(price * discPct / 100);
    const recurPct = {{ $hasRecurring ? (float) $settings['recurring_percent'] : 0 }};
    const recurMonths = {{ $hasRecurring ? $months : 0 }};
    const fmt = (v) => 'Rp' + Math.round(v).toLocaleString('id-ID');
    const range = document.getElementById('calcCount');
    let selected = packages[0];

    const pct = (v) => String(v).replace('.', ',') + '%';
    const setText = (id, text) => {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    };

    function render() {
        const n = parseInt(range.value, 10) || 0;
        const step = selected.months;
        const firstPaid = firstBase(selected.price);
        const perFirst = Math.floor(firstPaid * firstPct / 100);
        const perRecur = Math.floor(selected.price * recurPct / 100);

        let renewals = 0;
        for (let age = step; age < 12 && age < recurMonths; age += step) renewals++;
        const perYear = perFirst + renewals * perRecur;
        const year = n * perYear;

        setText('calcCountLabel', n);
        setText('calcCountLabel2', n);
        setText('calcFirstTotal', fmt(n * perFirst));
        setText('calcRecurTotal', fmt(n * perRecur));
        setText('calcRecurUnit', step === 1 ? '/bulan' : '/' + step + ' bulan');
        setText('calcFirst', fmt(perFirst));
        setText('calcFirstFormula', '(' + pct(firstPct) + ' × ' + fmt(firstPaid) + ')');
        setText('calcRecurLabel', step === 1 ? 'Setiap bulan perpanjang' : 'Setiap perpanjang (tiap ' + step + ' bulan)');
        setText('calcRecur', fmt(perRecur));
        setText('calcRecurFormula', '(' + pct(recurPct) + ' × ' + fmt(selected.price) + ')');
        setText('calcPerYear', fmt(perYear));
        setText('calcYear', fmt(year));

        const every = step === 1 ? 'setiap bulan' : 'setiap ' + step + ' bulan';
        setText('calcExplain', recurPct > 0 && renewals > 0
            ? 'Saat ' + n + ' ISP membayar pertama kali, Anda dapat ' + fmt(n * perFirst) + '. Setelah itu ' + fmt(n * perRecur) + ' ' + every + ' selama mereka perpanjang (' + renewals + '× dalam 12 bulan). Totalnya ' + fmt(year) + '.'
            : 'Komisi dari pembayaran pertama: ' + fmt(n * perFirst) + ' untuk ' + n + ' ISP.');
    }

    document.querySelectorAll('[data-calc-package]').forEach((btn) => {
        btn.addEventListener('click', () => {
            selected = packages.find((p) => p.key === btn.dataset.calcPackage) || packages[0];
            document.querySelectorAll('.calc-pkg').forEach((b) => {
                const on = b === btn;
                b.classList.toggle('border-indigo-500', on);
                b.classList.toggle('bg-indigo-50', on);
                b.classList.toggle('ring-2', on);
                b.classList.toggle('ring-indigo-500/20', on);
                b.classList.toggle('border-slate-200', !on);
            });
            render();
        });
    });
    range.addEventListener('input', render);
    render();
})();
</script>
@endpush
