@extends('layouts.panel')

@section('title', 'Data Pelanggan — MikPay')
@section('page', 'customers')

@section('content')
@php
    $total = $stats['total'] ?? 0;
    $online = $stats['online'] ?? 0;
    $offline = $stats['offline'] ?? 0;
    $isolirCount = $stats['isolir'] ?? 0;
    $billingSet = $stats['billing_set'] ?? 0;
    $billingUnset = $stats['billing_unset'] ?? 0;
    $customersUrl = function (?string $filterKey = null, ?string $search = null) use ($q, $filter) {
        $params = array_filter([
            'q' => $search ?? ($q !== '' ? $q : null),
            'filter' => ($filterKey ?? $filter) !== 'all' ? ($filterKey ?? $filter) : null,
        ], fn ($v) => $v !== null && $v !== '');

        $base = panel_route('customers');

        return $params ? $base.'?'.http_build_query($params) : $base;
    };
@endphp

<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Data Pelanggan Internet</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            PPP live + data tagihan (template Excel MikPay) · Router
            <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $router->displayName() }}</span>
            <span class="font-mono text-slate-400">({{ $router->endpointLabel() }})</span>
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" onclick="openAddPppModal()" @disabled(! $fetchOk)
                class="inline-flex items-center gap-2 text-xs font-bold px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/20 transition disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fa-solid fa-user-plus"></i> Tambah PPP
        </button>
        <button type="button" onclick="openImportModal()"
                class="inline-flex items-center gap-2 text-xs font-bold px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-600/20 transition">
            <i class="fa-solid fa-file-excel"></i> Import Excel
        </button>
        <a href="{{ $customersUrl() }}"
           class="inline-flex items-center gap-2 text-xs font-bold px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition">
            <i class="fa-solid fa-rotate"></i> Refresh
        </a>
        <a href="{{ panel_route('settings') }}?tab=mikrotik"
           class="inline-flex items-center gap-2 text-xs font-bold px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition">
            <i class="fa-solid fa-network-wired"></i> Ganti Router
        </a>
    </div>
</div>

@if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-500/10 dark:border-emerald-500/30 px-4 py-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 dark:border-rose-500/30 px-4 py-3 text-xs font-semibold text-rose-700 dark:text-rose-300">
        {{ session('error') }}
    </div>
@endif

@if (! $fetchOk)
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-500/10 dark:border-amber-500/30 px-4 py-3 text-xs font-semibold text-amber-800 dark:text-amber-300">
        {{ $fetchMessage }}
    </div>
@endif

<div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total PPP</p>
        <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $total }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-500">Online</p>
        <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">{{ $online }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Offline</p>
        <p class="text-2xl font-extrabold text-slate-700 dark:text-slate-200 mt-1">{{ $offline }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Isolir</p>
        <p class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 mt-1">{{ $isolirCount }}</p>
    </div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Belum diatur</p>
        <p class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">{{ $billingUnset }}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">{{ $billingSet }} sudah diisi</p>
    </div>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row gap-3 sm:items-center">
    <form method="GET" action="{{ panel_route('customers') }}" class="flex-1 flex flex-col sm:flex-row gap-2">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari PPPoE, nama, HP, zona, paket..."
                   class="w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs focus:outline-none focus:border-indigo-500">
        </div>
        @if ($filter !== 'all')
            <input type="hidden" name="filter" value="{{ $filter }}">
        @endif
        <button type="submit" class="text-xs font-bold px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900">Cari</button>
    </form>
    <div class="flex flex-wrap gap-1.5">
        @foreach (['all' => 'Semua', 'online' => 'Online', 'offline' => 'Offline', 'isolir' => 'Isolir', 'belum' => 'Belum diatur'] as $key => $label)
            <a href="{{ $customersUrl($key) }}"
               class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg border transition {{ $filter === $key ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300 min-w-[1100px]">
            <thead class="bg-slate-100 dark:bg-slate-950 text-slate-500 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="p-3">Username PPP *</th>
                    <th class="p-3">Nama tampilan</th>
                    <th class="p-3">No. HP</th>
                    <th class="p-3">Zona</th>
                    <th class="p-3">Label paket</th>
                    <th class="p-3">Tarif / bln</th>
                    <th class="p-3">Jatuh tempo</th>
                    <th class="p-3">IP / Status</th>
                    <th class="p-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($customers as $c)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td class="p-3">
                            <p class="font-bold text-slate-900 dark:text-slate-100 font-mono">{{ $c['name'] }}</p>
                            @if (! empty($c['email']))
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $c['email'] }}</p>
                            @endif
                        </td>
                        <td class="p-3">
                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $c['display_name'] ?: '—' }}</p>
                            @if (! empty($c['address']))
                                <p class="text-[10px] text-slate-400 mt-0.5 line-clamp-1">{{ $c['address'] }}</p>
                            @endif
                        </td>
                        <td class="p-3 font-mono">{{ $c['phone'] ?: '—' }}</td>
                        <td class="p-3">{{ $c['zone'] ?: '—' }}</td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 font-semibold">
                                {{ $c['package_label'] ?: ($c['profile'] ?: '-') }}
                            </span>
                        </td>
                        <td class="p-3 font-mono">
                            @if (($c['monthly_fee'] ?? 0) > 0)
                                Rp {{ number_format($c['monthly_fee'], 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-3">
                            @if ($c['billing_configured'])
                                <span class="font-bold text-slate-800 dark:text-slate-100">tgl {{ $c['due_day'] }}</span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-600">Belum</span>
                            @endif
                        </td>
                        <td class="p-3">
                            <p class="font-mono text-[10px] text-slate-500">{{ $c['online'] ? ($c['ip'] ?: '—') : '—' }}</p>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @if ($c['isolir'])
                                    <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-600 font-bold text-[10px]">ISOLIR</span>
                                @elseif ($c['online'])
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 font-bold text-[10px]">ONLINE</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-slate-200/80 dark:bg-slate-800 text-slate-600 font-bold text-[10px]">OFFLINE</span>
                                @endif
                            </div>
                        </td>
                        <td class="p-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ panel_route('customers.edit') }}?name={{ urlencode($c['name']) }}"
                                   class="p-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 rounded text-indigo-600" title="Lengkapi data">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                @if ($c['isolir'] || $c['disabled'])
                                    <form method="POST" action="{{ panel_route('customers.open') }}" onsubmit="return MikPay.confirmForm(event, 'Buka isolir untuk {{ $c['name'] }}?', { title: 'Buka Isolir', confirmText: 'Ya, buka' })">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $c['id'] }}">
                                        <input type="hidden" name="name" value="{{ $c['name'] }}">
                                        <button type="submit" class="p-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 rounded text-emerald-600" title="Buka Isolir">
                                            <i class="fa-solid fa-lock-open"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ panel_route('customers.isolir') }}" onsubmit="return MikPay.confirmForm(event, 'Isolir {{ $c['name'] }}?', { title: 'Isolir Pelanggan', variant: 'warning', confirmText: 'Ya, isolir' })">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $c['id'] }}">
                                        <input type="hidden" name="name" value="{{ $c['name'] }}">
                                        <button type="submit" class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 rounded text-rose-600" title="Isolir">
                                            <i class="fa-solid fa-lock"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-10 text-center">
                            <p class="text-sm font-bold text-slate-900 dark:text-white">Tidak ada data</p>
                            <p class="text-xs text-slate-500 mt-1">
                                @if (! $fetchOk)
                                    Gagal mengambil data dari MikroTik.
                                @elseif ($q !== '' || $filter !== 'all')
                                    Tidak ada hasil untuk filter/pencarian ini.
                                @else
                                    Belum ada PPP Secret di router aktif.
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($fetchOk)
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-400">
            Menampilkan {{ $customers->count() }} dari {{ $total }} · Pakai Import Excel untuk isi massal, atau ikon pensil per pelanggan.
        </div>
    @endif
</div>

{{-- Modal Tambah PPP --}}
@php
    $addPppReopen = old('_form') === 'add_ppp';
    $pppInput = 'mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500';
    $pppLabel = 'text-[11px] font-bold text-slate-600 dark:text-slate-300';
@endphp
<div id="addPppModal" class="fixed inset-0 z-50 {{ $addPppReopen ? 'flex' : 'hidden' }} items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tambah Pelanggan PPP</h3>
                <p class="text-[11px] text-slate-500">Langsung dibuat di MikroTik <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $router->displayName() }}</span> (PPP → Secrets).</p>
            </div>
            <button type="button" onclick="closeAddPppModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="addPppForm" method="POST" action="{{ panel_route('customers.store') }}" class="flex-1 overflow-y-auto px-5 py-4 space-y-5">
            @csrf
            <input type="hidden" name="_form" value="add_ppp">

            @if ($addPppReopen && ($errors->any() || session('error')))
                <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 dark:border-rose-500/30 px-3 py-2 text-[11px] font-semibold text-rose-700 dark:text-rose-300">
                    @if (session('error'))
                        <p>{{ session('error') }}</p>
                    @endif
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="space-y-3">
                <p class="text-[11px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">1. Akun PPP</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $pppLabel }}">Username PPP <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="64" autocomplete="off" placeholder="contoh: budi@net"
                               class="{{ $pppInput }} font-mono">
                    </div>
                    <div>
                        <label class="{{ $pppLabel }}">Password PPP <span class="text-rose-500">*</span></label>
                        <div class="flex gap-2">
                            <input type="text" id="addPppPassword" name="password" required minlength="4" maxlength="64" autocomplete="off"
                                   class="{{ $pppInput }} font-mono">
                            <button type="button" onclick="generatePppPassword()" class="mt-1 shrink-0 px-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs" title="Buat password acak">
                                <i class="fa-solid fa-shuffle"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="{{ $pppLabel }}">Paket (PPP Profile) <span class="text-rose-500">*</span></label>
                        <select name="profile" required class="{{ $pppInput }}">
                            <option value="">— Pilih paket —</option>
                            @foreach ($profiles as $p)
                                <option value="{{ $p }}" @selected(old('profile') === $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $pppLabel }}">Service</label>
                        <select name="service" class="{{ $pppInput }}">
                            @foreach (['pppoe' => 'PPPoE', 'any' => 'Any', 'l2tp' => 'L2TP', 'pptp' => 'PPTP', 'ovpn' => 'OVPN', 'sstp' => 'SSTP'] as $val => $lbl)
                                <option value="{{ $val }}" @selected(old('service', 'pppoe') === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <details class="group" @if (old('remote_address') || old('local_address')) open @endif>
                    <summary class="cursor-pointer list-none text-[11px] font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        <i class="fa-solid fa-chevron-right text-[9px] mr-1 transition-transform group-open:rotate-90"></i> IP statis (opsional)
                    </summary>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                        <div>
                            <label class="{{ $pppLabel }}">Remote address</label>
                            <input type="text" name="remote_address" value="{{ old('remote_address') }}" placeholder="kosongkan = ikut profile" class="{{ $pppInput }} font-mono">
                        </div>
                        <div>
                            <label class="{{ $pppLabel }}">Local address</label>
                            <input type="text" name="local_address" value="{{ old('local_address') }}" placeholder="kosongkan = ikut profile" class="{{ $pppInput }} font-mono">
                        </div>
                    </div>
                </details>
            </div>

            <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">2. Data Pelanggan & Tagihan <span class="normal-case font-semibold text-slate-400">(opsional)</span></p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Isi tanggal jatuh tempo agar tagihan bulanan dibuat otomatis.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $pppLabel }}">Nama pelanggan</label>
                        <input type="text" name="display_name" value="{{ old('display_name') }}" maxlength="255" placeholder="Bpk. Budi" class="{{ $pppInput }}">
                    </div>
                    <div>
                        <label class="{{ $pppLabel }}">No. HP / WhatsApp</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" maxlength="50" placeholder="08xxxxxxxxxx" class="{{ $pppInput }} font-mono">
                    </div>
                    <div>
                        <label class="{{ $pppLabel }}">Tarif per bulan (Rp)</label>
                        <input type="number" name="monthly_fee" value="{{ old('monthly_fee') }}" min="0" step="1000" placeholder="150000" class="{{ $pppInput }}">
                    </div>
                    <div>
                        <label class="{{ $pppLabel }}">Tanggal jatuh tempo</label>
                        <select name="due_day" class="{{ $pppInput }}">
                            <option value="0">— Belum diatur —</option>
                            @for ($d = 1; $d <= 31; $d++)
                                <option value="{{ $d }}" @selected((int) old('due_day') === $d)>Tanggal {{ $d }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="{{ $pppLabel }}">Zona / Area</label>
                        <input type="text" name="zone" value="{{ old('zone') }}" maxlength="100" placeholder="OLT Port 1" class="{{ $pppInput }}">
                    </div>
                    <div>
                        <label class="{{ $pppLabel }}">Alamat</label>
                        <input type="text" name="address" value="{{ old('address') }}" maxlength="2000" class="{{ $pppInput }}">
                    </div>
                </div>
            </div>
        </form>

        <div class="flex gap-2 px-5 py-4 border-t border-slate-100 dark:border-slate-800">
            <button type="button" onclick="closeAddPppModal()" class="px-4 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200">Batal</button>
            <button type="submit" form="addPppForm" id="addPppSubmit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold py-2.5 rounded-xl shadow-md shadow-indigo-600/20">
                <i class="fa-solid fa-user-plus mr-1"></i> Simpan ke MikroTik
            </button>
        </div>
    </div>
</div>

{{-- Modal Import Excel --}}
<div id="importModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-lg p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h3 id="importModalTitle" class="text-sm font-bold text-slate-900 dark:text-white">Import Data Pelanggan (Excel)</h3>
            <button type="button" id="importModalCloseBtn" onclick="closeImportModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
        </div>

        {{-- Form --}}
        <div id="importFormPanel" class="space-y-4">
            <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2 text-[11px] text-amber-800 dark:text-amber-200 space-y-1">
                <p><strong>Username PPP</strong> (kolom kuning) sudah terisi dari MikroTik — jangan diubah.</p>
                <p>Semua kolom lain <strong>opsional</strong>: Nama, HP, Email, Zona, Tarif, Jatuh tempo, Mulai layanan, Alamat, Catatan.</p>
                <p><strong>Mulai layanan</strong> kosong + ada jatuh tempo → otomatis hari ini; tagihan hanya bulan daftar.</p>
            </div>

            <div class="space-y-2">
                <p class="text-[11px] font-bold text-slate-600 dark:text-slate-300">1. Unduh template</p>
                <a href="{{ panel_route('customers.template') }}"
                   class="inline-flex items-center gap-2 text-xs font-bold px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 hover:bg-slate-100 transition">
                    <i class="fa-solid fa-download text-emerald-600"></i> Download Template Excel
                </a>
            </div>

            <form id="importExcelForm" method="POST" action="{{ panel_route('customers.import') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <p class="text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1.5">2. Unggah template yang sudah diisi</p>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">File Excel (.xlsx)</label>
                    <input id="importFileInput" type="file" name="file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required
                           class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500">
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="submit" id="importSubmitBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-2.5 rounded-xl">
                        <i class="fa-solid fa-upload mr-1"></i> Import Sekarang
                    </button>
                    <button type="button" onclick="closeImportModal()" class="px-4 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700">Batal</button>
                </div>
            </form>
        </div>

        {{-- Progress --}}
        <div id="importProgressPanel" class="hidden space-y-5 py-2">
            <div class="flex flex-col items-center text-center gap-2">
                <div class="relative h-14 w-14">
                    <div class="absolute inset-0 rounded-full border-4 border-emerald-100 dark:border-emerald-900/40"></div>
                    <div id="importSpinner" class="absolute inset-0 rounded-full border-4 border-transparent border-t-emerald-500 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <i class="fa-solid fa-file-excel text-emerald-600 text-lg"></i>
                    </div>
                </div>
                <div>
                    <p id="importProgressLabel" class="text-sm font-bold text-slate-900 dark:text-white">Mengunggah file…</p>
                    <p id="importProgressSub" class="text-[11px] text-slate-500 mt-0.5">Jangan tutup halaman ini</p>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 mb-1.5">
                    <span id="importStepText">Upload</span>
                    <span id="importPercentText">0%</span>
                </div>
                <div class="h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div id="importProgressBar" class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-300 ease-out" style="width: 0%"></div>
                </div>
            </div>

            <ol id="importSteps" class="space-y-2 text-[11px]">
                <li data-step="upload" class="flex items-center gap-2 text-slate-400">
                    <span class="step-dot h-5 w-5 rounded-full border border-current flex items-center justify-center text-[9px]"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                    <span>Unggah file Excel</span>
                </li>
                <li data-step="validate" class="flex items-center gap-2 text-slate-400">
                    <span class="step-dot h-5 w-5 rounded-full border border-current flex items-center justify-center text-[9px]"><i class="fa-solid fa-shield-halved"></i></span>
                    <span>Validasi username PPP</span>
                </li>
                <li data-step="save" class="flex items-center gap-2 text-slate-400">
                    <span class="step-dot h-5 w-5 rounded-full border border-current flex items-center justify-center text-[9px]"><i class="fa-solid fa-database"></i></span>
                    <span>Simpan data pelanggan</span>
                </li>
                <li data-step="done" class="flex items-center gap-2 text-slate-400">
                    <span class="step-dot h-5 w-5 rounded-full border border-current flex items-center justify-center text-[9px]"><i class="fa-solid fa-check"></i></span>
                    <span>Selesai</span>
                </li>
            </ol>
        </div>

        {{-- Result --}}
        <div id="importResultPanel" class="hidden space-y-4 py-1">
            <div class="flex flex-col items-center text-center gap-2">
                <div id="importResultIcon" class="h-14 w-14 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <p id="importResultTitle" class="text-sm font-bold text-slate-900 dark:text-white">Import berhasil</p>
                    <p id="importResultMessage" class="text-[11px] text-slate-500 mt-0.5"></p>
                </div>
            </div>
            <div id="importResultStats" class="grid grid-cols-2 gap-2"></div>
            <div id="importResultErrors" class="hidden rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 px-3 py-2 text-[11px] text-rose-700 dark:text-rose-200"></div>
            <button type="button" id="importResultReloadBtn" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-2.5 rounded-xl">
                Lihat Data Pelanggan
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('addPppModal');
    const form = document.getElementById('addPppForm');
    const passwordInput = document.getElementById('addPppPassword');

    window.generatePppPassword = function () {
        const chars = 'abcdefghjkmnpqrstuvwxyz23456789';
        const bytes = new Uint32Array(8);
        crypto.getRandomValues(bytes);
        passwordInput.value = Array.from(bytes, (b) => chars[b % chars.length]).join('');
    };

    window.openAddPppModal = function () {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (!passwordInput.value) generatePppPassword();
        form.querySelector('[name="name"]')?.focus();
    };

    window.closeAddPppModal = function () {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    modal?.addEventListener('click', function (e) {
        if (e.target === this) closeAddPppModal();
    });

    form?.addEventListener('submit', function () {
        const btn = document.getElementById('addPppSubmit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan ke MikroTik…';
    });

    if (!modal.classList.contains('hidden') && !passwordInput.value) generatePppPassword();
})();

(function () {
    let importing = false;
    let progressTimer = null;
    let progressValue = 0;

    const modal = document.getElementById('importModal');
    const formPanel = document.getElementById('importFormPanel');
    const progressPanel = document.getElementById('importProgressPanel');
    const resultPanel = document.getElementById('importResultPanel');
    const form = document.getElementById('importExcelForm');
    const closeBtn = document.getElementById('importModalCloseBtn');
    const titleEl = document.getElementById('importModalTitle');

    window.openImportModal = function () {
        if (importing) return;
        resetImportUi();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeImportModal = function () {
        if (importing) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        resetImportUi();
    };

    modal?.addEventListener('click', function (e) {
        if (e.target === this && !importing) closeImportModal();
    });

    function resetImportUi() {
        stopFakeProgress();
        progressValue = 0;
        importing = false;
        formPanel.classList.remove('hidden');
        progressPanel.classList.add('hidden');
        resultPanel.classList.add('hidden');
        closeBtn.classList.remove('invisible', 'pointer-events-none');
        titleEl.textContent = 'Import Data Pelanggan (Excel)';
        setProgress(0, 'Upload', 'Mengunggah file…');
        document.querySelectorAll('#importSteps [data-step]').forEach((el) => {
            el.classList.remove('text-emerald-600', 'font-bold');
            el.classList.add('text-slate-400');
        });
        form?.reset();
    }

    function showProgressUi() {
        formPanel.classList.add('hidden');
        resultPanel.classList.add('hidden');
        progressPanel.classList.remove('hidden');
        closeBtn.classList.add('invisible', 'pointer-events-none');
        titleEl.textContent = 'Proses Import';
    }

    function setProgress(pct, stepLabel, label) {
        progressValue = Math.max(0, Math.min(100, pct));
        document.getElementById('importProgressBar').style.width = progressValue + '%';
        document.getElementById('importPercentText').textContent = Math.round(progressValue) + '%';
        if (stepLabel) document.getElementById('importStepText').textContent = stepLabel;
        if (label) document.getElementById('importProgressLabel').textContent = label;
    }

    function setActiveStep(step) {
        const order = ['upload', 'validate', 'save', 'done'];
        const idx = order.indexOf(step);
        document.querySelectorAll('#importSteps [data-step]').forEach((el) => {
            const i = order.indexOf(el.dataset.step);
            if (i < idx) {
                el.classList.remove('text-slate-400');
                el.classList.add('text-emerald-600', 'font-bold');
            } else if (i === idx) {
                el.classList.remove('text-slate-400');
                el.classList.add('text-emerald-600', 'font-bold');
            } else {
                el.classList.add('text-slate-400');
                el.classList.remove('text-emerald-600', 'font-bold');
            }
        });
    }

    function startFakeProgress() {
        stopFakeProgress();
        const phases = [
            { max: 35, step: 'upload', label: 'Mengunggah file…', stepLabel: 'Upload', tick: 180 },
            { max: 65, step: 'validate', label: 'Memvalidasi username PPP…', stepLabel: 'Validasi', tick: 220 },
            { max: 90, step: 'save', label: 'Menyimpan data pelanggan…', stepLabel: 'Simpan', tick: 260 },
        ];
        let phase = 0;
        setActiveStep('upload');
        setProgress(8, 'Upload', 'Mengunggah file…');

        progressTimer = setInterval(() => {
            const p = phases[phase];
            if (!p) return;
            if (progressValue < p.max) {
                setProgress(progressValue + (Math.random() * 3 + 0.8), p.stepLabel, p.label);
                setActiveStep(p.step);
            } else if (phase < phases.length - 1) {
                phase += 1;
            }
        }, 200);
    }

    function stopFakeProgress() {
        if (progressTimer) {
            clearInterval(progressTimer);
            progressTimer = null;
        }
    }

    function showResult(ok, data) {
        stopFakeProgress();
        setProgress(100, 'Selesai', ok ? 'Import selesai!' : 'Import gagal');
        setActiveStep('done');

        setTimeout(() => {
            importing = false;
            progressPanel.classList.add('hidden');
            resultPanel.classList.remove('hidden');
            closeBtn.classList.remove('invisible', 'pointer-events-none');
            titleEl.textContent = ok ? 'Import Selesai' : 'Import Gagal';

            const icon = document.getElementById('importResultIcon');
            const title = document.getElementById('importResultTitle');
            const msg = document.getElementById('importResultMessage');
            const stats = document.getElementById('importResultStats');
            const errBox = document.getElementById('importResultErrors');
            const reloadBtn = document.getElementById('importResultReloadBtn');

            if (ok) {
                icon.className = 'h-14 w-14 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 flex items-center justify-center text-2xl';
                icon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
                title.textContent = 'Import berhasil';
                msg.textContent = data.message || '';
                stats.innerHTML = `
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-center">
                        <p class="text-lg font-black text-emerald-600">${data.saved ?? 0}</p>
                        <p class="text-[10px] font-bold text-slate-500">Disimpan</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-center">
                        <p class="text-lg font-black text-slate-600 dark:text-slate-300">${data.skipped ?? 0}</p>
                        <p class="text-[10px] font-bold text-slate-500">Dilewati</p>
                    </div>`;
                if (data.error_preview) {
                    errBox.classList.remove('hidden');
                    errBox.textContent = data.error_preview;
                } else {
                    errBox.classList.add('hidden');
                    errBox.textContent = '';
                }
                reloadBtn.textContent = 'Lihat Data Pelanggan';
                reloadBtn.onclick = () => { window.location.href = data.redirect || window.location.href; };
            } else {
                icon.className = 'h-14 w-14 rounded-full bg-rose-100 dark:bg-rose-500/20 text-rose-600 flex items-center justify-center text-2xl';
                icon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
                title.textContent = 'Import gagal';
                msg.textContent = data.message || 'Terjadi kesalahan saat import.';
                stats.innerHTML = '';
                errBox.classList.add('hidden');
                reloadBtn.textContent = 'Coba Lagi';
                reloadBtn.onclick = () => resetImportUi();
            }
        }, 450);
    }

    form?.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (importing) return;

        const fileInput = document.getElementById('importFileInput');
        if (!fileInput?.files?.length) {
            await (window.mikpayAlert || MikPay.alert)?.({ title: 'File belum dipilih', message: 'Pilih file Excel terlebih dahulu.' });
            return;
        }

        importing = true;
        showProgressUi();
        startFakeProgress();

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            let data = {};
            try {
                data = await res.json();
            } catch (_) {
                data = { message: 'Respons server tidak valid.' };
            }

            if (!res.ok || data.ok === false) {
                const message = data.message
                    || (data.errors && Object.values(data.errors).flat().join(' '))
                    || 'Gagal mengimpor file.';
                showResult(false, { message });
                return;
            }

            showResult(true, data);
        } catch (err) {
            showResult(false, { message: err?.message || 'Koneksi gagal. Coba lagi.' });
        }
    });
})();
</script>
@endpush

