@extends('layouts.panel')

@section('title', 'Langganan — MikPay')
@section('page', 'subscription')

@section('content')
@if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs font-semibold px-4 py-3">{{ session('error') }}</div>
@endif
@if (($returnStatus ?? '') === 'success')
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3">
        Pembayaran diterima. Jika status belum berubah, tunggu beberapa detik (webhook Xendit) lalu refresh halaman.
    </div>
@elseif (($returnStatus ?? '') === 'failed')
    <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs font-semibold px-4 py-3">
        Pembayaran dibatalkan / gagal. Silakan pilih paket lagi untuk mencoba ulang.
    </div>
@endif

<div class="rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-lg shadow-indigo-600/20">
    <div>
        <h2 class="text-base sm:text-lg font-extrabold">Status Langganan</h2>
        <p class="text-xs sm:text-sm text-indigo-100 mt-1">
            Paket: <strong class="text-white">{{ strtoupper($tenant->plan ?: 'trial') }}</strong>
            @if ($subscriptionUntil)
                · aktif sampai <strong class="text-white">{{ $subscriptionUntil }}</strong>
                @if ($remainingDays !== null)
                    ({{ $remainingDays }} hari)
                @endif
            @endif
        </p>
    </div>
    <span class="inline-flex self-start px-3 py-1.5 rounded-full text-xs font-extrabold border {{ $isActive ? 'bg-emerald-500/20 border-emerald-300/40 text-emerald-100' : 'bg-rose-500/20 border-rose-300/40 text-rose-100' }}">
        {{ $isActive ? 'AKTIF' : 'HABIS / NONAKTIF' }}
    </span>
</div>

<div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
    <p class="font-bold text-slate-800 dark:text-slate-100 mb-1.5">Cara bayar</p>
    @if ($xenditReady)
        <ol class="list-decimal pl-4 space-y-1">
            <li>Pilih paket → langsung diarahkan ke <strong>Xendit</strong> (QRIS / VA / e-wallet).</li>
            <li>Bayar di halaman Xendit.</li>
            <li>Setelah sukses, webhook mengaktifkan langganan <strong>otomatis</strong> (tanpa konfirmasi manual).</li>
        </ol>
    @else
        <p>Xendit platform belum siap. Saat ini pembayaran memakai unggah bukti manual.</p>
    @endif
</div>

@if ($hasPending && $pendingLatest)
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900 font-medium flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <span>
            <i class="fa-solid fa-hourglass-half mr-1"></i>
            Pembayaran <code class="font-mono">{{ $pendingLatest->payment_id }}</code> masih pending
            ({{ $pendingLatest->isXendit() ? 'Xendit' : 'manual' }}).
        </span>
        <div class="flex flex-wrap gap-2">
            @if ($pendingLatest->xendit_invoice_url)
                <a href="{{ $pendingLatest->xendit_invoice_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-bold text-indigo-700 hover:underline">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Lanjutkan bayar Xendit
                </a>
            @endif
            @if ($subscriptionVerifyWaUrl)
                <a href="{{ $subscriptionVerifyWaUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-bold text-emerald-700 hover:underline">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp
                </a>
            @endif
            <form method="POST" action="{{ panel_route('subscription.cancel-pending') }}" onsubmit="return confirm('Batalkan pembayaran pending ini?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 font-bold text-rose-700 hover:underline">Batalkan</button>
            </form>
        </div>
    </div>
@endif

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
    <div>
        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Pilih paket</h3>
        <p class="text-xs text-slate-500 mt-1">
            @if ($xenditReady)
                Pembayaran via Xendit — aktif otomatis setelah lunas.
            @else
                Setelah transfer, unggah bukti untuk diverifikasi Superadmin.
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($packages as $key => $pkg)
            @php
                $cta = $pkg['cta_type'] ?? 'checkout';
                $panelBtn = $cta === 'whatsapp'
                    ? ($pkg['button_label'] ?? 'Hubungi Sales')
                    : ($xenditReady
                        ? ($isActive ? 'Bayar via Xendit' : 'Aktifkan via Xendit')
                        : ($isActive ? 'Perpanjang paket ini' : 'Bayar & aktifkan'));
            @endphp
            <div class="rounded-2xl border {{ !empty($pkg['featured']) ? 'border-indigo-400 shadow-md' : 'border-slate-200 dark:border-slate-700' }} bg-slate-50/80 dark:bg-slate-800/40 p-5 flex flex-col">
                @if (!empty($pkg['featured_ribbon']))
                    <span class="self-start text-[10px] font-extrabold uppercase tracking-wider text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-full mb-2">{{ $pkg['featured_ribbon'] }}</span>
                @endif
                <h4 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $pkg['name'] }}</h4>
                <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-2">
                    Rp {{ number_format((int) $pkg['price'], 0, ',', '.') }}
                    <span class="text-xs font-normal text-slate-500">{{ $pkg['duration_label'] ?? '' }}</span>
                </p>
                <p class="text-[11px] text-slate-500 mt-1">{{ $pkg['subtitle'] ?? '' }} · {{ (int) ($pkg['duration'] ?? 30) }} hari</p>
                <ul class="mt-3 space-y-1.5 text-[11px] text-slate-600 dark:text-slate-300 flex-1">
                    @foreach (($pkg['features'] ?? []) as $f)
                        <li class="flex gap-1.5"><i class="fa-solid fa-check text-emerald-600 mt-0.5"></i> {{ $f }}</li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ panel_route('subscription.select') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="package" value="{{ $key }}">
                    <button type="submit" @disabled($hasPending && $cta !== 'whatsapp' && !($pendingLatest?->xendit_invoice_url && $pendingLatest?->package === $key)) class="w-full py-2.5 rounded-xl text-xs font-bold {{ !empty($pkg['featured']) ? 'gradient-bg text-white' : 'border border-slate-300 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100' }} disabled:opacity-50">
                        {{ $panelBtn }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Riwayat pembayaran</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3 font-bold">ID</th>
                    <th class="text-left px-4 py-3 font-bold">Paket</th>
                    <th class="text-left px-4 py-3 font-bold">Channel</th>
                    <th class="text-left px-4 py-3 font-bold">Nominal</th>
                    <th class="text-left px-4 py-3 font-bold">Status</th>
                    <th class="text-left px-4 py-3 font-bold">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($pendingPayments as $p)
                    <tr>
                        <td class="px-4 py-3 font-mono">{{ $p->payment_id }}</td>
                        <td class="px-4 py-3">{{ $packages[$p->package]['name'] ?? $p->package }}</td>
                        <td class="px-4 py-3">{{ $p->payment_channel ?: '—' }}</td>
                        <td class="px-4 py-3 font-bold">Rp {{ number_format((int) $p->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if ($p->status === 'pending')
                                <span class="text-amber-600 font-bold">pending</span>
                            @elseif ($p->status === 'approved')
                                <span class="text-emerald-600 font-bold">approved</span>
                            @else
                                <span class="text-rose-600 font-bold">rejected</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ format_wib($p->created_at) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada riwayat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
