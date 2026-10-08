@extends('layouts.partner')

@section('title', 'Dashboard')
@section('heading', 'Dashboard Affiliate')
@section('subheading', 'Ringkasan performa referral Anda')

@php
    $rp = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
    $pct = fn (float $v) => rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
    $user = auth()->user();
    $firstName = explode(' ', trim($user->name))[0] ?: $user->name;
@endphp

@section('content')
@if (! $partner->isActive())
    {{-- Pending / rejected / suspended --}}
    <div class="rounded-3xl bg-white border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-6 sm:p-10 text-center relative overflow-hidden">
            <div class="absolute inset-0 bg-grid opacity-50"></div>
            @if ($partner->isPending())
                <span class="relative inline-flex w-16 h-16 rounded-2xl bg-amber-50 text-amber-500 items-center justify-center text-2xl border border-amber-100"><i class="fa-solid fa-hourglass-half"></i></span>
                <h2 class="relative mt-5 text-2xl font-extrabold text-slate-900">Halo {{ $firstName }}, pendaftaran Anda sedang ditinjau</h2>
                <p class="relative mt-2 text-sm text-slate-600 max-w-lg mx-auto">Tim MikPay akan memeriksa data Anda dan mengaktifkan akun affiliate, biasanya dalam waktu kurang dari 1×24 jam. Anda akan menerima email beserta kode referral saat akun aktif.</p>
            @elseif ($partner->status === \App\Models\Partner::STATUS_REJECTED)
                <span class="relative inline-flex w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 items-center justify-center text-2xl border border-rose-100"><i class="fa-solid fa-circle-xmark"></i></span>
                <h2 class="relative mt-5 text-2xl font-extrabold text-slate-900">Pendaftaran affiliate belum dapat disetujui</h2>
                @if ($partner->reject_reason)
                    <p class="relative mt-3 inline-block text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2">{{ $partner->reject_reason }}</p>
                @endif
                <p class="relative mt-3 text-sm text-slate-600">Silakan hubungi admin MikPay bila ada pertanyaan.</p>
            @else
                <span class="relative inline-flex w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 items-center justify-center text-2xl"><i class="fa-solid fa-pause"></i></span>
                <h2 class="relative mt-5 text-2xl font-extrabold text-slate-900">Akun affiliate sedang dinonaktifkan</h2>
                <p class="relative mt-2 text-sm text-slate-600 max-w-lg mx-auto">Link referral sementara tidak mencatat rujukan baru. Komisi yang sudah tercatat tetap aman. Hubungi admin MikPay untuk info lebih lanjut.</p>
            @endif
        </div>

        @if ($partner->isPending())
            <div class="border-t border-slate-100 bg-slate-50/60 p-6 sm:px-10">
                <div class="grid sm:grid-cols-3 gap-4">
                    @foreach ([
                        ['fa-check', 'Pendaftaran terkirim', 'Selesai', 'emerald'],
                        ['fa-magnifying-glass', 'Ditinjau tim MikPay', 'Sedang berjalan', 'amber'],
                        ['fa-link', 'Kode referral aktif', 'Menunggu', 'slate'],
                    ] as $step)
                        <div class="flex items-center gap-3 rounded-2xl bg-white border border-slate-200 p-4">
                            <span class="w-10 h-10 rounded-xl bg-{{ $step[3] }}-50 text-{{ $step[3] }}-600 flex items-center justify-center"><i class="fa-solid {{ $step[0] }}"></i></span>
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ $step[1] }}</p>
                                <p class="text-[11px] text-slate-500">{{ $step[2] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-5 text-xs text-slate-500 text-center">Sambil menunggu, Anda bisa <a href="{{ route('partner.withdrawals') }}" class="font-bold text-indigo-600 hover:underline">menyiapkan rekening pencairan</a>.</p>
            </div>
        @endif
    </div>
@else
    {{-- Referral hero --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 text-white p-6 sm:p-8 shadow-xl shadow-indigo-900/20">
        <div class="absolute inset-0 bg-grid-dark opacity-30"></div>
        <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-white/10"></div>
        <div class="absolute right-24 -bottom-24 w-56 h-56 rounded-full bg-emerald-400/20"></div>

        <div class="relative grid lg:grid-cols-5 gap-6 items-center">
            <div class="lg:col-span-3">
                <p class="text-sm text-indigo-200 font-semibold">Halo, {{ $firstName }} 👋</p>
                <h2 class="mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight">Bagikan link Anda, komisi mengalir otomatis.</h2>

                <div class="mt-5 flex flex-col sm:flex-row gap-2">
                    <div class="flex-1 min-w-0 flex items-center gap-2 rounded-xl bg-white/10 border border-white/15 px-3.5 py-3">
                        <i class="fa-solid fa-link text-indigo-200 text-xs"></i>
                        <span class="font-mono text-sm truncate" id="refLink">{{ $partner->referralUrl() }}</span>
                    </div>
                    <button type="button" onclick="partnerCopy(@js($partner->referralUrl()))" class="shrink-0 inline-flex items-center justify-center gap-2 rounded-xl bg-white text-indigo-700 font-bold text-sm px-5 py-3 hover:bg-indigo-50 transition">
                        <i class="fa-regular fa-copy"></i> Salin Link
                    </button>
                </div>

                @php
                    $shareText = 'Kelola billing RT RW Net, auto isolir & remote MikroTik jadi gampang pakai MikPay. Coba gratis '.(int) config('mikpay.trial_days', 3).' hari: '.$partner->referralUrl();
                @endphp
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="text-[11px] text-indigo-200 font-semibold mr-1">Bagikan:</span>
                    <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-lg bg-[#25D366] flex items-center justify-center hover:scale-105 transition" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    <a href="https://t.me/share/url?url={{ rawurlencode($partner->referralUrl()) }}&text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-lg bg-[#229ED9] flex items-center justify-center hover:scale-105 transition" title="Telegram"><i class="fa-brands fa-telegram"></i></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($partner->referralUrl()) }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-lg bg-[#1877F2] flex items-center justify-center hover:scale-105 transition" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://twitter.com/intent/tweet?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-lg bg-black flex items-center justify-center hover:scale-105 transition" title="X"><i class="fa-brands fa-x-twitter"></i></a>
                </div>
            </div>

            <div class="lg:col-span-2 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-white/10 border border-white/15 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-200">Kode referral</p>
                    <p class="mt-1 font-mono text-2xl font-extrabold tracking-wider">{{ $partner->code }}</p>
                    <button type="button" onclick="partnerCopy(@js($partner->code))" class="mt-1 text-[11px] font-bold text-indigo-100 hover:text-white"><i class="fa-regular fa-copy"></i> Salin kode</button>
                </div>
                <div class="rounded-2xl bg-emerald-400/15 border border-emerald-300/30 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-200">Bisa ditarik</p>
                    <p class="mt-1 text-2xl font-extrabold tracking-tight">{{ $rp($balance['available']) }}</p>
                    <a href="{{ route('partner.withdrawals') }}" class="mt-1 inline-block text-[11px] font-bold text-emerald-100 hover:text-white">Cairkan <i class="fa-solid fa-arrow-right text-[9px]"></i></a>
                </div>
                <div class="col-span-2 rounded-2xl bg-white/5 border border-white/10 px-4 py-3 text-[11px] text-indigo-100 leading-relaxed">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    Calon pelanggan juga bisa mengetik kode <b class="font-mono">{{ $partner->code }}</b> di halaman daftar MikPay.
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        @foreach ([
            ['fa-arrow-pointer', 'Klik link', number_format($stats['clicks'], 0, ',', '.'), 'indigo', 'Pengunjung unik dari link Anda'],
            ['fa-user-plus', 'Pendaftar', number_format($stats['signups'], 0, ',', '.'), 'cyan', 'ISP yang daftar via referral'],
            ['fa-crown', 'Pelanggan berbayar', number_format($stats['paying'], 0, ',', '.'), 'violet', $stats['signups'] > 0 ? 'Konversi '.round($stats['paying'] / $stats['signups'] * 100).'%' : 'Belum ada konversi'],
            ['fa-sack-dollar', 'Total komisi', $rp($balance['total']), 'emerald', 'Sejak bergabung'],
        ] as $s)
            <div class="rounded-2xl bg-white border border-slate-200 p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">{{ $s[1] }}</p>
                    <span class="w-8 h-8 rounded-lg bg-{{ $s[3] }}-50 text-{{ $s[3] }}-600 flex items-center justify-center text-xs"><i class="fa-solid {{ $s[0] }}"></i></span>
                </div>
                <p class="mt-2 text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">{{ $s[2] }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $s[4] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-5 grid lg:grid-cols-3 gap-5">
        {{-- Balance --}}
        <div class="rounded-2xl bg-white border border-slate-200 p-5">
            <h3 class="font-extrabold text-slate-900">Saldo Komisi</h3>
            <div class="mt-4 space-y-3 text-sm">
                @foreach ([
                    ['Bisa ditarik', $balance['available'], 'text-emerald-600', 'fa-circle-check text-emerald-500'],
                    ['Tertahan ('.$settings['hold_days'].' hari)', $balance['on_hold'], 'text-amber-600', 'fa-clock text-amber-500'],
                    ['Sedang diproses', $balance['in_progress'], 'text-indigo-600', 'fa-spinner text-indigo-500'],
                    ['Sudah dicairkan', $balance['withdrawn'], 'text-slate-700', 'fa-building-columns text-slate-400'],
                ] as $b)
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-slate-600"><i class="fa-solid {{ $b[3] }} w-4 text-center text-xs"></i> {{ $b[0] }}</span>
                        <span class="font-extrabold {{ $b[2] }}">{{ $rp($b[1]) }}</span>
                    </div>
                @endforeach
            </div>
            @if ($nextPayoutDate)
                <p class="mt-4 text-[11px] text-slate-500"><i class="fa-regular fa-calendar text-indigo-500"></i> Jadwal pencairan berikutnya: <b class="text-slate-700">{{ $nextPayoutDate->translatedFormat('d F Y') }}</b></p>
            @endif
            <a href="{{ route('partner.withdrawals') }}" class="mt-3 w-full inline-flex items-center justify-center gap-2 rounded-xl gradient-bg text-white text-sm font-bold py-2.5 shadow-md shadow-indigo-500/20 hover:opacity-95">
                <i class="fa-solid fa-money-bill-transfer"></i> Cairkan Komisi
            </a>
        </div>

        {{-- Rates --}}
        <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <h3 class="font-extrabold text-slate-900">Tarif Komisi Anda</h3>
                <span class="text-[11px] text-slate-400 font-semibold">Dihitung dari harga paket</span>
            </div>
            <div class="mt-4 grid sm:grid-cols-2 gap-3">
                <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4">
                    <p class="text-[11px] font-bold text-emerald-700 uppercase tracking-wide">Pembayaran pertama</p>
                    <p class="mt-1 text-3xl font-extrabold text-emerald-700">{{ $pct($rates['first']) }}%</p>
                </div>
                <div class="rounded-xl bg-cyan-50 border border-cyan-100 p-4">
                    <p class="text-[11px] font-bold text-cyan-700 uppercase tracking-wide">Perpanjangan</p>
                    <p class="mt-1 text-3xl font-extrabold text-cyan-700">{{ $pct($rates['recurring']) }}%</p>
                    <p class="text-[11px] text-cyan-700/80">{{ $settings['recurring_months'] > 0 ? 'selama '.$settings['recurring_months'].' bulan' : 'tidak berlaku' }}</p>
                </div>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-slate-400">
                            <th class="py-2 font-semibold">Paket</th>
                            <th class="py-2 font-semibold">Harga</th>
                            <th class="py-2 font-semibold text-right">Komisi pertama</th>
                            <th class="py-2 font-semibold text-right">Perpanjangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($packages as $p)
                            <tr>
                                <td class="py-2 font-bold text-slate-800">{{ $p['name'] }}</td>
                                <td class="py-2 text-slate-600">{{ $rp((int) $p['price']) }}</td>
                                <td class="py-2 text-right font-extrabold text-emerald-600">{{ $rp((int) floor($p['price'] * $rates['first'] / 100)) }}</td>
                                <td class="py-2 text-right font-extrabold text-cyan-600">{{ $settings['recurring_months'] > 0 ? $rp((int) floor($p['price'] * $rates['recurring'] / 100)) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-5 grid lg:grid-cols-2 gap-5">
        <div class="rounded-2xl bg-white border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-extrabold text-slate-900">Rujukan Terbaru</h3>
                <a href="{{ route('partner.referrals') }}" class="text-xs font-bold text-indigo-600 hover:underline">Lihat semua</a>
            </div>
            @forelse ($recentReferrals as $t)
                @php $paid = ($t->plan ?? 'trial') !== 'trial'; @endphp
                <div class="px-5 py-3 flex items-center gap-3 border-b border-slate-50 last:border-0">
                    <span class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-extrabold text-sm">{{ strtoupper(mb_substr($t->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">{{ $t->name }}</p>
                        <p class="text-[11px] text-slate-400">Daftar {{ $t->created_at?->timezone(app_timezone())->translatedFormat('d M Y') }}</p>
                    </div>
                    <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $paid ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">{{ $paid ? 'Berlangganan' : 'Trial' }}</span>
                </div>
            @empty
                <div class="px-5 py-10 text-center">
                    <span class="inline-flex w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 items-center justify-center"><i class="fa-solid fa-user-plus"></i></span>
                    <p class="mt-3 text-sm font-bold text-slate-700">Belum ada rujukan</p>
                    <p class="text-xs text-slate-500">Bagikan link Anda untuk mendapatkan rujukan pertama.</p>
                </div>
            @endforelse
        </div>

        <div class="rounded-2xl bg-white border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-extrabold text-slate-900">Komisi Terbaru</h3>
                <a href="{{ route('partner.commissions') }}" class="text-xs font-bold text-indigo-600 hover:underline">Lihat semua</a>
            </div>
            @forelse ($recentCommissions as $c)
                <div class="px-5 py-3 flex items-center gap-3 border-b border-slate-50 last:border-0">
                    <span class="w-9 h-9 rounded-xl {{ $c->kind === 'first' ? 'bg-emerald-50 text-emerald-600' : 'bg-cyan-50 text-cyan-600' }} flex items-center justify-center text-sm"><i class="fa-solid fa-coins"></i></span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">{{ $c->tenant_name }}</p>
                        <p class="text-[11px] text-slate-400">{{ $c->kindLabel() }} · {{ $c->created_at?->timezone(app_timezone())->translatedFormat('d M Y') }}</p>
                    </div>
                    <div class="ml-auto text-right">
                        <p class="text-sm font-extrabold {{ $c->isCancelled() ? 'text-slate-400 line-through' : 'text-slate-900' }}">+{{ $rp($c->amount) }}</p>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full border {{ $c->statusBadgeClass() }}">{{ $c->statusLabel() }}</span>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center">
                    <span class="inline-flex w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 items-center justify-center"><i class="fa-solid fa-coins"></i></span>
                    <p class="mt-3 text-sm font-bold text-slate-700">Belum ada komisi</p>
                    <p class="text-xs text-slate-500">Komisi muncul saat rujukan Anda membayar langganan.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Promo templates --}}
    @php
        $link = $partner->referralUrl();
        $trial = (int) config('mikpay.trial_days', 3);
        $price = 'Rp'.number_format((int) ($packages['bulan1']['price'] ?? 35000), 0, ',', '.');
        $discountPct = (float) ($settings['enabled'] ? $settings['referral_discount_percent'] : 0);
        $discountText = $discountPct > 0
            ? "\n🎁 Pakai kode *{$partner->code}* saat daftar, dapat diskon ".rtrim(rtrim(number_format($discountPct, 2, ',', ''), '0'), ',').'% pembayaran pertama.'
            : '';
        $templates = [
            ['WhatsApp personal', 'fa-brands fa-whatsapp', "Halo kak 👋\n\nMasih rekap tagihan pelanggan WiFi manual? Coba MikPay — billing RT RW Net yang terhubung langsung ke MikroTik:\n\n✅ Auto isolir & buka otomatis\n✅ Tagihan & pengingat via WhatsApp\n✅ Remote Winbox & Web OLT tanpa IP publik\n✅ Voucher hotspot & peta ODP\n\nGratis trial {$trial} hari, lanjut mulai {$price}/bulan.{$discountText}\nDaftar di sini: {$link}"],
            ['Grup komunitas', 'fa-solid fa-people-group', "Rekan-rekan pemilik RT RW Net 🙏\n\nSaya pakai & rekomendasikan MikPay untuk kelola pelanggan MikroTik. Semua dalam satu panel: billing, auto isolir, WA otomatis, remote VPN gratis, sampai laporan keuangan.\n\nCoba gratis {$trial} hari tanpa kartu kredit.{$discountText}\n👉 {$link}"],
            ['Status / media sosial', 'fa-solid fa-bullhorn', "Punya usaha WiFi / RT RW Net? 📡\nBerhenti pusing nagih manual. MikPay bikin tagihan, isolir, dan remote MikroTik serba otomatis.\n\nTrial gratis {$trial} hari.{$discountText}\n→ {$link}\n\n#RTRWNet #MikroTik #ISP"],
        ];
    @endphp
    <div class="mt-5 rounded-2xl bg-white border border-slate-200 p-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="font-extrabold text-slate-900">Materi Promosi</h3>
                <p class="text-xs text-slate-500">Template siap kirim, link referral Anda sudah terpasang.</p>
            </div>
            <span class="hidden sm:inline-flex w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 items-center justify-center"><i class="fa-solid fa-bullhorn"></i></span>
        </div>
        <div class="mt-4 grid lg:grid-cols-3 gap-4">
            @foreach ($templates as $i => $tpl)
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col">
                    <div class="px-4 py-3 border-b border-slate-200 flex items-center gap-2">
                        <i class="{{ $tpl[1] }} text-indigo-600"></i>
                        <p class="text-xs font-extrabold text-slate-800">{{ $tpl[0] }}</p>
                    </div>
                    <pre id="tpl{{ $i }}" class="flex-1 px-4 py-3 text-[11px] text-slate-700 whitespace-pre-wrap font-sans leading-relaxed">{{ $tpl[2] }}</pre>
                    <div class="px-4 py-3 border-t border-slate-200 flex gap-2">
                        <button type="button" onclick="partnerCopy(document.getElementById('tpl{{ $i }}').textContent)" class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white text-xs font-bold text-slate-700 py-2 hover:border-indigo-300 hover:text-indigo-700">
                            <i class="fa-regular fa-copy"></i> Salin
                        </button>
                        <a href="https://wa.me/?text={{ rawurlencode($tpl[2]) }}" target="_blank" rel="noopener" class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#25D366] text-white text-xs font-bold py-2 hover:opacity-90">
                            <i class="fa-brands fa-whatsapp"></i> Kirim
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
