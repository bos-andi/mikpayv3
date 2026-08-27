@extends('layouts.superadmin')

@section('title', 'Pembayaran Platform — MikPay')
@section('heading', 'Inbox Pembayaran Langganan')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border border-amber-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Pending</p>
        <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $stats['pending'] ?? 0 }}</p>
    </div>
    <div class="bg-white border border-emerald-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Approved</p>
        <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $stats['approved'] ?? 0 }}</p>
    </div>
    <div class="bg-white border border-rose-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Rejected</p>
        <p class="text-2xl font-extrabold text-rose-600 mt-1">{{ $stats['rejected'] ?? 0 }}</p>
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3 font-bold">ID</th>
                    <th class="text-left px-4 py-3 font-bold">Tenant</th>
                    <th class="text-left px-4 py-3 font-bold">Paket</th>
                    <th class="text-left px-4 py-3 font-bold">Channel</th>
                    <th class="text-left px-4 py-3 font-bold">Nominal</th>
                    <th class="text-left px-4 py-3 font-bold">Status</th>
                    <th class="text-left px-4 py-3 font-bold">Catatan</th>
                    <th class="text-left px-4 py-3 font-bold">Bukti</th>
                    <th class="text-left px-4 py-3 font-bold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $p)
                    @php
                        $channelLabels = [
                            'xendit' => 'Xendit (otomatis)',
                            'bank_transfer' => 'Transfer Bank',
                            'ewallet' => 'E-Wallet',
                            'qris' => 'QRIS',
                        ];
                    @endphp
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <code class="font-mono text-[11px] text-slate-800">{{ $p->payment_id }}</code>
                            <p class="text-[10px] text-slate-400 mt-1">{{ optional($p->created_at)->format('d M Y H:i') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800">{{ $p->tenant->name ?? '—' }}</p>
                            <p class="text-[10px] text-slate-500">{{ $p->tenant->subdomain ?? '' }} · {{ $p->user->email ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ $packages[$p->package]['name'] ?? ucfirst($p->package) }}</td>
                        <td class="px-4 py-3">{{ $channelLabels[$p->payment_channel] ?? ($p->payment_channel ?: '—') }}</td>
                        <td class="px-4 py-3 font-bold">Rp {{ number_format((int) $p->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if ($p->status === 'pending')
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-bold">pending</span>
                            @elseif ($p->status === 'approved')
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold">approved</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-bold">rejected</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-[180px] text-slate-600">{{ $p->customer_note ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($p->xendit_invoice_url)
                                <a href="{{ $p->xendit_invoice_url }}" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 font-bold hover:underline">
                                    <i class="fa-solid fa-link"></i> Invoice
                                </a>
                            @elseif ($p->proof_file)
                                <a href="{{ asset('storage/'.$p->proof_file) }}" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 font-bold hover:underline">
                                    <i class="fa-solid fa-image"></i> Lihat
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 min-w-[220px]">
                            @if ($p->status === 'pending')
                                <form method="POST" action="{{ route('superadmin.payments.approve', $p) }}" class="inline-block mb-2">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700">
                                        <i class="fa-solid fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.payments.reject', $p) }}" class="flex flex-wrap items-center gap-1">
                                    @csrf
                                    <input type="text" name="reject_reason" required placeholder="Alasan tolak" class="rounded-lg border border-slate-200 px-2 py-1.5 w-36">
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-600 text-white font-bold hover:bg-rose-700">
                                        <i class="fa-solid fa-xmark"></i> Reject
                                    </button>
                                </form>
                            @else
                                <span class="text-slate-400">Diproses</span>
                                @if ($p->reject_reason)
                                    <p class="text-[10px] text-rose-600 mt-1">{{ $p->reject_reason }}</p>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada pembayaran langganan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
