@extends('layouts.panel')

@section('title', 'Tiket Gangguan — MikPay')
@section('page', 'tickets')

@section('content')
@php
    $counts = $counts ?? ['open' => 0, 'resolved' => 0, 'closed' => 0];
    $status = $status ?? 'open';
    $q = $q ?? '';
@endphp

<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Tiket Perbaikan Lapangan</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Kelola gangguan jaringan, instalasi, dan tiket optik.
            <span class="font-semibold text-orange-600">{{ $counts['open'] ?? 0 }} tiket aktif</span>
        </p>
    </div>
    <button type="button" onclick="document.getElementById('ticketCreateModal').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-lg shadow-orange-600/20">
        <i class="fa-solid fa-plus"></i> Buat Tiket
    </button>
</div>

@if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs font-semibold px-4 py-3">{{ session('error') }}</div>
@endif

<div class="flex flex-col sm:flex-row sm:items-center gap-3">
    <div class="flex flex-wrap gap-2">
        @foreach (['open' => 'Aktif', 'in_progress' => 'Dikerjakan', 'resolved' => 'Selesai', 'closed' => 'Ditutup', 'all' => 'Semua'] as $key => $label)
            @php
                $params = array_filter(['status' => $key === 'open' ? null : $key, 'q' => $q ?: null], fn ($v) => $v !== null && $v !== '');
                $filterUrl = $params ? panel_route('tickets').'?'.http_build_query($params) : panel_route('tickets');
            @endphp
            <a href="{{ $filterUrl }}"
               class="text-[11px] font-bold px-3 py-1.5 rounded-lg border transition {{ $status === $key ? 'bg-orange-50 border-orange-200 text-orange-700' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    <form method="GET" action="{{ panel_route('tickets') }}" class="flex-1 sm:max-w-xs">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari kode, subjek, pelanggan..."
               class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs px-3 py-2">
    </form>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    @if ($tickets->isEmpty())
        <div class="p-10 text-center text-sm text-slate-500">
            <i class="fa-solid fa-headset text-3xl text-slate-300 mb-3"></i>
            <p>Belum ada tiket{{ $status !== 'all' ? ' dengan status ini' : '' }}.</p>
        </div>
    @else
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($tickets as $ticket)
                <div class="p-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/30 transition">
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-[10px] font-mono font-bold text-slate-400">{{ $ticket->code }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $ticket->statusBadgeClass() }}">{{ $ticket->statusLabel() }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">{{ $ticket->priorityLabel() }}</span>
                            </div>
                            <p class="font-bold text-sm text-slate-900 dark:text-white">{{ $ticket->subject }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                @if ($ticket->customer_name)
                                    <i class="fa-solid fa-user mr-1"></i>{{ $ticket->customer_name }}
                                @endif
                                @if ($ticket->location_label)
                                    · <i class="fa-solid fa-location-dot mr-1"></i>{{ $ticket->location_label }}
                                @endif
                                @if ($ticket->odp)
                                    · {{ $ticket->odp->code }}
                                @endif
                            </p>
                            @if ($ticket->description)
                                <p class="text-xs text-slate-600 dark:text-slate-300 mt-2 line-clamp-2">{{ $ticket->description }}</p>
                            @endif
                            <p class="text-[10px] text-slate-400 mt-2">
                                Dibuat {{ format_wib($ticket->created_at) }}
                                @if ($ticket->assignedTo)
                                    · Ditugaskan ke {{ $ticket->assignedTo->name }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <form method="POST" action="{{ panel_route('tickets.update', $ticket) }}" class="inline-flex items-center gap-1">
                                @csrf
                                @method('PUT')
                                <select name="status" class="text-[11px] rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5">
                                    <option value="open" @selected($ticket->status === 'open')>Baru</option>
                                    <option value="in_progress" @selected($ticket->status === 'in_progress')>Dikerjakan</option>
                                    <option value="resolved" @selected($ticket->status === 'resolved')>Selesai</option>
                                    <option value="closed" @selected($ticket->status === 'closed')>Ditutup</option>
                                </select>
                                <button type="submit" class="text-[11px] font-bold px-2.5 py-1.5 rounded-lg bg-indigo-600 text-white">Update</button>
                            </form>
                            <form method="POST" action="{{ panel_route('tickets.destroy', $ticket) }}" onsubmit="return MikPay.confirmForm(event, 'Hapus tiket {{ $ticket->code }}?', { title: 'Hapus Tiket', variant: 'danger', confirmText: 'Hapus' })">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] font-bold px-2.5 py-1.5 rounded-lg border border-rose-300 text-rose-600 hover:bg-rose-50">Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($tickets->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
                {{ $tickets->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Modal Buat Tiket --}}
<div id="ticketCreateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Buat Tiket Baru</h3>
            <button type="button" onclick="document.getElementById('ticketCreateModal').classList.add('hidden')" class="w-8 h-8 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ panel_route('tickets.store') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="text-[11px] font-bold text-slate-500 uppercase">Subjek</label>
                <input type="text" name="subject" required maxlength="200" placeholder="Contoh: Kabel dropcore putus"
                       class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2">
            </div>
            <div>
                <label class="text-[11px] font-bold text-slate-500 uppercase">Deskripsi</label>
                <textarea name="description" rows="3" placeholder="Detail gangguan..."
                          class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Kategori</label>
                    <select name="category" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2">
                        <option value="gangguan">Gangguan</option>
                        <option value="instalasi">Instalasi</option>
                        <option value="optical">Optical / OLT</option>
                        <option value="billing">Billing</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Prioritas</label>
                    <select name="priority" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2">
                        <option value="normal">Normal</option>
                        <option value="high">Tinggi</option>
                        <option value="urgent">Mendesak</option>
                        <option value="low">Rendah</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Nama Pelanggan</label>
                    <input type="text" name="customer_name" maxlength="120" placeholder="Opsional"
                           class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2">
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Lokasi / ODP</label>
                    <input type="text" name="location_label" maxlength="200" placeholder="ODP-BLOK-B2"
                           class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2">
                </div>
            </div>
            @if (($oltNodes ?? collect())->isNotEmpty())
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Node GIS (OLT)</label>
                    <select name="odp_id" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2">
                        <option value="">— Tidak terkait —</option>
                        @foreach ($oltNodes as $node)
                            <option value="{{ $node->id }}">{{ $node->code }} {{ $node->name ? '· '.$node->name : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if (($assignees ?? collect())->isNotEmpty())
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Assign ke Teknisi</label>
                    <select name="assigned_to_user_id" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2">
                        <option value="">— Belum ditugaskan —</option>
                        @foreach ($assignees as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('ticketCreateModal').classList.add('hidden')" class="text-xs font-bold px-4 py-2 rounded-xl border border-slate-200 text-slate-600">Batal</button>
                <button type="submit" class="text-xs font-bold px-4 py-2 rounded-xl bg-orange-600 text-white">Simpan Tiket</button>
            </div>
        </form>
    </div>
</div>
@endsection
