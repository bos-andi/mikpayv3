@extends('layouts.panel')

@section('title', 'Toko Voucher Online — MikPay')
@section('page', 'voucher-shop')

@section('content')
@php
    $rp = fn ($v) => 'Rp '.number_format((int) $v, 0, ',', '.');
    $inputClass = 'w-full text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500';
    $labelClass = 'block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5';
    $cardClass = 'rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm';
    $statusBadge = [
        'pending' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
        'paid' => 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
        'fulfilled' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        'failed' => 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
        'expired' => 'bg-slate-500/10 text-slate-500 dark:text-slate-400',
    ];
    $sub = $tenant->subdomain;
    $shopHost = parse_url($shopUrl, PHP_URL_HOST);
    $walledGarden = "/ip hotspot walled-garden\n"
        ."add dst-host={$shopHost} comment=\"MikPay Toko Voucher\"\n"
        ."add dst-host=mikpay.link comment=\"MikPay Toko Voucher\"\n"
        ."/ip hotspot walled-garden ip\n"
        ."add action=accept dst-host={$shopHost} comment=\"MikPay Toko Voucher\"\n"
        ."add action=accept dst-host=mikpay.link comment=\"MikPay Toko Voucher\"";
    $loginButton = '<a href="'.$shopUrl.'" style="display:block;margin:12px 0;padding:12px;border-radius:10px;background:#4f46e5;color:#fff;text-align:center;font-weight:bold;text-decoration:none">Beli Voucher Online (QRIS)</a>';
    $quotaMb = fn ($b) => $b ? (int) round($b / 1048576) : null;
@endphp

<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Hotspot &amp; Voucher</p>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="fa-solid fa-store text-purple-500 text-base"></i>
            Toko Voucher Online
            @if ($availability['open'])
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"><i class="fa-solid fa-circle text-[6px] align-middle mr-1"></i>BUKA</span>
            @else
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-500/10 text-slate-600 dark:text-slate-300">TUTUP</span>
            @endif
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed max-w-2xl">
            Pelanggan WiFi beli voucher sendiri 24 jam: pilih paket → bayar QRIS → kode langsung jadi di MikroTik. Uang masuk ke <a href="{{ panel_route('finance.withdrawals') }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Saldo Penarikan</a>.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <button type="button" onclick="vsCopy(@js($shopUrl), this)" class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800">
            <i class="fa-regular fa-copy"></i> Salin link
        </button>
        <button type="button" onclick="vsPoster()" class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800">
            <i class="fa-solid fa-qrcode"></i> Unduh QR toko
        </button>
        <a href="{{ $shopUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka toko
        </a>
    </div>
</div>

@foreach (['success' => 'emerald', 'error' => 'rose'] as $key => $color)
    @if (session($key))
        <div class="rounded-xl border border-{{ $color }}-200 bg-{{ $color }}-50 dark:bg-{{ $color }}-500/10 dark:border-{{ $color }}-500/30 px-4 py-3 text-xs font-semibold text-{{ $color }}-700 dark:text-{{ $color }}-300">{{ session($key) }}</div>
    @endif
@endforeach
@if ($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 dark:border-rose-500/30 px-4 py-3 text-xs font-semibold text-rose-700 dark:text-rose-300">
        <ul class="list-disc pl-4 space-y-0.5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@if (! $availability['open'])
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-500/10 dark:border-amber-500/30 px-4 py-3 text-xs text-amber-800 dark:text-amber-200 flex gap-2">
        <i class="fa-solid fa-circle-info mt-0.5"></i>
        <span><b>Toko belum bisa dipakai pembeli:</b> {{ $availability['reason'] }}
            @if (! $settings['enabled']) Aktifkan di kartu <b>Pengaturan</b>, lalu tambahkan minimal satu paket.@endif
        </span>
    </div>
@endif

{{-- Statistik --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    @foreach ([
        ['Penjualan hari ini', $rp($stats['today_amount']), $stats['today_count'].' voucher', 'fa-calendar-day', 'text-indigo-500'],
        ['Bulan ini', $rp($stats['month_amount']), wib_now()->locale('id')->translatedFormat('F Y'), 'fa-chart-line', 'text-emerald-500'],
        ['Total penjualan', $rp($stats['total_amount']), 'Sejak toko dibuka', 'fa-sack-dollar', 'text-amber-500'],
        ['Perlu dicek', $stats['failed'], 'Lunas, voucher gagal', 'fa-triangle-exclamation', $stats['failed'] ? 'text-rose-500' : 'text-slate-400'],
    ] as [$label, $value, $hint, $icon, $color])
        <div class="{{ $cardClass }} p-4">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                <i class="fa-solid {{ $icon }} {{ $color }}"></i>
            </div>
            <p class="mt-1.5 text-lg font-bold text-slate-900 dark:text-white">{{ $value }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $hint }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
    {{-- Paket --}}
    <div class="xl:col-span-3 {{ $cardClass }}">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Paket yang dijual</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Harga di sini diterima utuh oleh Anda. Biaya layanan (QRIS + MikPay) ditanggung pembeli.</p>
            </div>
            <a href="{{ panel_route('vouchers.shop') }}?refresh=1" class="text-[11px] font-semibold text-slate-500 hover:text-indigo-600" title="Muat ulang profile dari router"><i class="fa-solid fa-rotate"></i> Profile</a>
        </div>

        @if ($profilesError)
            <div class="mx-5 mt-4 rounded-xl bg-amber-50 dark:bg-amber-500/10 px-3 py-2 text-[11px] text-amber-800 dark:text-amber-200">
                <i class="fa-solid fa-plug-circle-xmark"></i> Gagal membaca profile dari router ({{ $profilesError }}). Nama profile bisa diketik manual.
            </div>
        @endif

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($items as $item)
                <details class="group">
                    <summary class="flex items-center gap-3 px-5 py-3.5 cursor-pointer list-none hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <div class="w-10 h-10 rounded-xl grid place-items-center {{ $item->is_active ? 'bg-purple-500/10 text-purple-600 dark:text-purple-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                {{ $item->label }}
                                @unless ($item->is_active)<span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">NONAKTIF</span>@endunless
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                Profile <b>{{ $item->profile }}</b>
                                @if ($item->limit_uptime) · {{ $item->limit_uptime }}@endif
                                @if ($item->limit_bytes) · {{ $quotaMb($item->limit_bytes) >= 1024 ? rtrim(rtrim(number_format($quotaMb($item->limit_bytes) / 1024, 1, ',', '.'), '0'), ',').' GB' : $quotaMb($item->limit_bytes).' MB' }}@endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $rp($item->price) }}</p>
                            <p class="text-[10px] text-slate-400">pembeli bayar {{ $rp($item->price + \App\Services\VoucherShopService::feeFor($item->price)) }}</p>
                        </div>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition group-open:rotate-180"></i>
                    </summary>
                    @if ($canManage)
                        <div class="px-5 pb-4 pt-1 bg-slate-50/60 dark:bg-slate-950/40">
                            <form method="POST" action="{{ panel_route('vouchers.shop.items.update', ['item' => $item->id]) }}" class="vs-item-form grid grid-cols-2 md:grid-cols-6 gap-3">
                                @csrf
                                @method('PUT')
                                @include('panel.partials.voucher-shop-item-fields', ['row' => $item])
                                <div class="col-span-2 md:col-span-6 flex items-center justify-between gap-2">
                                    <button type="submit" form="vsDelete{{ $item->id }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700"><i class="fa-regular fa-trash-can"></i> Hapus</button>
                                    <button type="submit" class="text-xs font-semibold px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Simpan perubahan</button>
                                </div>
                            </form>
                            <form id="vsDelete{{ $item->id }}" method="POST" action="{{ panel_route('vouchers.shop.items.destroy', ['item' => $item->id]) }}" onsubmit="return MikPay.confirmForm(event, { title: 'Hapus paket?', message: 'Paket {{ addslashes($item->label) }} tidak akan tampil lagi di toko.', variant: 'danger' })">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @endif
                </details>
            @empty
                <div class="px-5 py-10 text-center">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-purple-500/10 text-purple-500 grid place-items-center mb-2"><i class="fa-solid fa-ticket"></i></div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Belum ada paket</p>
                    <p class="text-xs text-slate-500">Tambahkan paket dari profile hotspot di router Anda.</p>
                </div>
            @endforelse
        </div>

        @if ($canManage)
            <details class="border-t border-slate-100 dark:border-slate-800" @if ($items->isEmpty() || $errors->hasAny(['label', 'profile', 'price'])) open @endif>
                <summary class="px-5 py-3.5 cursor-pointer list-none text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <i class="fa-solid fa-plus"></i> Tambah paket
                </summary>
                <form method="POST" action="{{ panel_route('vouchers.shop.items.store') }}" class="vs-item-form grid grid-cols-2 md:grid-cols-6 gap-3 px-5 pb-5">
                    @csrf
                    @include('panel.partials.voucher-shop-item-fields', ['row' => null])
                    <div class="col-span-2 md:col-span-6 flex justify-end">
                        <button type="submit" class="text-xs font-semibold px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700"><i class="fa-solid fa-plus"></i> Tambah ke toko</button>
                    </div>
                </form>
            </details>
        @endif
        <datalist id="vsProfiles">
            @foreach ($profiles as $p)
                <option value="{{ $p['name'] }}" data-price="{{ $p['price'] }}" data-validity="{{ $p['validity'] }}">{{ $p['validity'] ? $p['validity'] : '' }}{{ $p['price'] ? ' · '.$rp($p['price']) : '' }}</option>
            @endforeach
        </datalist>
    </div>

    {{-- Pengaturan --}}
    <div class="xl:col-span-2 {{ $cardClass }}">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pengaturan</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">Tampilan toko &amp; format kode voucher.</p>
        </div>
        <form method="POST" action="{{ panel_route('vouchers.shop.settings') }}" class="p-5 space-y-4">
            @csrf
            <label class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2.5 cursor-pointer">
                <span>
                    <span class="block text-sm font-semibold text-slate-900 dark:text-white">Buka toko</span>
                    <span class="block text-[11px] text-slate-500">Pembeli bisa membeli di {{ $shopHost }}/voucher</span>
                </span>
                <input type="checkbox" name="enabled" value="1" class="w-5 h-5 accent-indigo-600" @checked(old('enabled', $settings['enabled'])) @disabled(! $canManage)>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="{{ $labelClass }}">Nama WiFi</label>
                    <input type="text" name="title" maxlength="60" class="{{ $inputClass }}" value="{{ old('title', $settings['title']) }}" placeholder="{{ $tenant->name }}">
                </div>
                <div class="col-span-2">
                    <label class="{{ $labelClass }}">Tagline</label>
                    <input type="text" name="tagline" maxlength="160" class="{{ $inputClass }}" value="{{ old('tagline', $settings['tagline']) }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">WA bantuan</label>
                    <input type="tel" name="contact_wa" class="{{ $inputClass }}" value="{{ old('contact_wa', $settings['contact_wa']) }}" placeholder="08xxxxxxxxxx">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Router</label>
                    <select name="router_id" class="{{ $inputClass }}">
                        @foreach ($routers as $r)
                            <option value="{{ $r->id }}" @selected((int) old('router_id', $router?->id) === $r->id)>{{ $r->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="{{ $labelClass }}">URL login hotspot <span class="normal-case font-normal">(opsional)</span></label>
                    <input type="url" name="login_url" class="{{ $inputClass }}" value="{{ old('login_url', $settings['login_url']) }}" placeholder="http://10.5.50.1/login">
                    <p class="mt-1 text-[11px] text-slate-500">Menampilkan tombol <b>Login sekarang</b> yang mengisi kode otomatis. Agar bisa login otomatis, centang <b>HTTP PAP</b> di Hotspot Server Profile.</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Panjang kode</label>
                    <input type="number" name="code_length" min="4" max="10" class="{{ $inputClass }}" value="{{ old('code_length', $settings['code_length']) }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Karakter</label>
                    <select name="code_chars" class="{{ $inputClass }}">
                        <option value="num" @selected(old('code_chars', $settings['code_chars']) === 'num')>Angka (482915)</option>
                        <option value="lower" @selected(old('code_chars', $settings['code_chars']) === 'lower')>Huruf kecil + angka</option>
                        <option value="upper" @selected(old('code_chars', $settings['code_chars']) === 'upper')>Huruf besar + angka</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Prefix</label>
                    <input type="text" name="prefix" maxlength="4" class="{{ $inputClass }}" value="{{ old('prefix', $settings['prefix']) }}" placeholder="mis. OL">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Server hotspot</label>
                    <input type="text" name="server" maxlength="64" class="{{ $inputClass }}" value="{{ old('server', $settings['server']) }}" placeholder="all">
                </div>
            </div>
            <p class="text-[11px] text-slate-500 leading-relaxed">Kode dibuat tanpa karakter yang mirip (0/O, 1/l/I) supaya mudah diketik. Kode = password (mode voucher).</p>
            @if ($canManage)
                <button type="submit" class="w-full text-sm font-semibold px-4 py-2.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Simpan pengaturan</button>
            @endif
        </form>
    </div>
</div>

{{-- Setup MikroTik --}}
<div class="{{ $cardClass }}">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
        <i class="fa-solid fa-screwdriver-wrench text-amber-500"></i>
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Setup di MikroTik (sekali saja)</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">Supaya pelanggan yang belum login hotspot tetap bisa membuka toko ini.</p>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-5">
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">1. Walled garden (paste di Terminal)</p>
                <button type="button" onclick="vsCopy(document.getElementById('vsWg').textContent, this)" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400"><i class="fa-regular fa-copy"></i> Salin</button>
            </div>
            <pre id="vsWg" class="text-[11px] leading-relaxed rounded-xl bg-slate-950 text-emerald-300 p-3 overflow-x-auto">{{ $walledGarden }}</pre>
        </div>
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">2. Tombol di login.html hotspot (opsional)</p>
                <button type="button" onclick="vsCopy(document.getElementById('vsBtn').textContent, this)" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400"><i class="fa-regular fa-copy"></i> Salin</button>
            </div>
            <pre id="vsBtn" class="text-[11px] leading-relaxed rounded-xl bg-slate-950 text-sky-300 p-3 overflow-x-auto whitespace-pre-wrap break-all">{{ $loginButton }}</pre>
            <p class="mt-2 text-[11px] text-slate-500 leading-relaxed">Pembayaran QRIS dilakukan di aplikasi e-wallet/m-banking pembeli memakai paket data, atau scan dari HP lain. Bisa juga cetak <b>QR toko</b> dan tempel di lokasi WiFi.</p>
        </div>
    </div>
</div>

{{-- Pesanan --}}
<div class="{{ $cardClass }}">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-800">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pesanan terbaru</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">100 pesanan terakhir. Pesanan belum dibayar otomatis kedaluwarsa.</p>
        </div>
        <div class="flex flex-wrap gap-1.5">
            @foreach (['' => 'Semua', 'fulfilled' => 'Sukses', 'pending' => 'Menunggu', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa'] as $key => $label)
                <a href="{{ panel_route('vouchers.shop') }}{{ $key !== '' ? '?status='.$key : '' }}" class="text-[11px] font-semibold px-2.5 py-1 rounded-lg {{ $statusFilter === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 dark:bg-slate-950/60 text-[10px] uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="text-left font-semibold px-5 py-2.5">Waktu</th>
                    <th class="text-left font-semibold px-3 py-2.5">Referensi</th>
                    <th class="text-left font-semibold px-3 py-2.5">Paket</th>
                    <th class="text-left font-semibold px-3 py-2.5">Kode</th>
                    <th class="text-right font-semibold px-3 py-2.5">Diterima</th>
                    <th class="text-left font-semibold px-3 py-2.5">Status</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($orders as $o)
                    @php $st = $o->isPaymentExpired() ? 'expired' : $o->status; @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                        <td class="px-5 py-2.5 whitespace-nowrap text-slate-500">{{ format_wib($o->created_at, 'd M H:i') }}</td>
                        <td class="px-3 py-2.5 font-mono text-[11px] text-slate-700 dark:text-slate-200">
                            {{ $o->reference }}
                            @if ($o->phone)<span class="block text-[10px] text-slate-400 font-sans"><i class="fa-brands fa-whatsapp"></i> {{ $o->phone }}@if ($o->wa_sent_at) ✓@endif</span>@endif
                        </td>
                        <td class="px-3 py-2.5 text-slate-700 dark:text-slate-200">{{ $o->item_label }}<span class="block text-[10px] text-slate-400">{{ $o->profile }}</span></td>
                        <td class="px-3 py-2.5 font-mono font-semibold text-slate-900 dark:text-white">{{ $o->voucher_code ?: '—' }}</td>
                        <td class="px-3 py-2.5 text-right font-semibold text-slate-900 dark:text-white whitespace-nowrap">{{ $rp($o->price) }}</td>
                        <td class="px-3 py-2.5">
                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full {{ $statusBadge[$st] ?? '' }}">{{ $st === 'expired' ? 'Kedaluwarsa' : $o->statusLabel() }}</span>
                            @if ($o->last_error && in_array($o->status, ['paid', 'failed'], true))
                                <span class="block mt-0.5 text-[10px] text-rose-500 max-w-[220px] truncate" title="{{ $o->last_error }}">{{ $o->last_error }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-2.5 text-right">
                            @if ($canManage && in_array($o->status, ['paid', 'failed'], true))
                                <form method="POST" action="{{ panel_route('vouchers.shop.orders.retry', ['order' => $o->id]) }}">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"><i class="fa-solid fa-rotate-right"></i> Proses ulang</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Belum ada pesanan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/vendor/qrcode.min.js') }}"></script>
<script>
function vsCopy(text, btn) {
    const done = () => {
        if (window.MikPay && MikPay.toast) MikPay.toast('Tersalin');
        if (btn) { const old = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-check"></i> Tersalin'; setTimeout(() => btn.innerHTML = old, 1600); }
    };
    if (navigator.clipboard) { navigator.clipboard.writeText(text).then(done); return; }
    const t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select();
    try { document.execCommand('copy'); done(); } catch (e) {}
    t.remove();
}

function vsPoster() {
    const url = @js($shopUrl), title = @js($settings['title']);
    const holder = document.createElement('div');
    new QRCode(holder, { text: url, width: 600, height: 600, correctLevel: QRCode.CorrectLevel.M });
    const qr = holder.querySelector('canvas');
    const c = document.createElement('canvas'); c.width = 900; c.height = 1200;
    const g = c.getContext('2d');
    const grad = g.createLinearGradient(0, 0, 900, 0); grad.addColorStop(0, '#4f46e5'); grad.addColorStop(1, '#7c3aed');
    g.fillStyle = grad; g.fillRect(0, 0, 900, 1200);
    g.fillStyle = '#fff'; g.beginPath(); g.roundRect ? g.roundRect(60, 230, 780, 900, 40) : g.rect(60, 230, 780, 900); g.fill();
    g.textAlign = 'center'; g.fillStyle = '#fff';
    g.font = 'bold 60px system-ui, sans-serif'; g.fillText('Beli Voucher WiFi', 450, 110);
    g.font = '36px system-ui, sans-serif'; g.fillText(title, 450, 170);
    g.drawImage(qr, 150, 290, 600, 600);
    g.fillStyle = '#0f172a'; g.font = 'bold 40px system-ui, sans-serif'; g.fillText('Scan → pilih paket → bayar QRIS', 450, 960);
    g.fillStyle = '#64748b'; g.font = '30px system-ui, sans-serif'; g.fillText(url.replace('https://', ''), 450, 1020);
    g.fillText('Kode voucher langsung muncul', 450, 1070);
    const a = document.createElement('a'); a.href = c.toDataURL('image/png'); a.download = 'qr-toko-voucher.png';
    document.body.appendChild(a); a.click(); a.remove();
}

document.querySelectorAll('.vs-item-form').forEach((form) => {
    const profile = form.querySelector('[name="profile"]');
    if (!profile) return;
    profile.addEventListener('change', () => {
        const opt = [...document.querySelectorAll('#vsProfiles option')].find((o) => o.value === profile.value);
        if (!opt) return;
        const price = form.querySelector('[name="price"]'), label = form.querySelector('[name="label"]'), uptime = form.querySelector('[name="limit_uptime"]');
        if (price && !price.value && +opt.dataset.price > 0) price.value = opt.dataset.price;
        if (label && !label.value) label.value = opt.value;
        if (uptime && !uptime.value && opt.dataset.validity && /^([0-9]+[wdhms])+$/.test(opt.dataset.validity)) uptime.value = opt.dataset.validity;
    });
});
</script>
@endpush
