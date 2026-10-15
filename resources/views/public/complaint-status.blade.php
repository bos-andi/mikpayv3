@extends('layouts.public-pay')

@section('title', 'Status Aduan '.$ticket->code)
@section('subtitle', 'Layanan aduan pelanggan')

@section('content')
@php
    $steps = [
        \App\Models\SupportTicket::STATUS_OPEN => ['Diterima', 'fa-inbox'],
        \App\Models\SupportTicket::STATUS_IN_PROGRESS => ['Dikerjakan', 'fa-screwdriver-wrench'],
        \App\Models\SupportTicket::STATUS_RESOLVED => ['Selesai', 'fa-circle-check'],
    ];
    $order = array_keys($steps);
    $status = $ticket->status === \App\Models\SupportTicket::STATUS_CLOSED ? \App\Models\SupportTicket::STATUS_RESOLVED : $ticket->status;
    $currentIndex = array_search($status, $order, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $hero = match ($status) {
        \App\Models\SupportTicket::STATUS_IN_PROGRESS => ['from-sky-500 to-indigo-600', 'Sedang dikerjakan', 'Teknisi kami sedang menangani aduan Anda.'],
        \App\Models\SupportTicket::STATUS_RESOLVED => ['from-emerald-500 to-teal-600', 'Aduan selesai', 'Terima kasih sudah melapor. Jika masih ada kendala, kirim aduan baru.'],
        default => ['from-orange-500 to-rose-500', 'Aduan diterima', 'Aduan Anda sudah masuk dan menunggu ditindaklanjuti.'],
    };
@endphp

<div class="space-y-4">
    <div class="rounded-3xl bg-gradient-to-br {{ $hero[0] }} text-white p-5 shadow-xl">
        <p class="text-[10px] font-bold uppercase tracking-widest text-white/80">Nomor tiket</p>
        <p class="text-2xl font-extrabold font-mono tracking-wide">{{ $ticket->code }}</p>
        <p class="mt-3 text-sm font-bold">{{ $hero[1] }}</p>
        <p class="text-[11px] text-white/85">{{ $hero[2] }}</p>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl shadow-violet-900/5 p-5 space-y-5">
        <div class="flex items-center justify-between">
            @foreach ($steps as $key => [$label, $icon])
                @php $idx = array_search($key, $order, true); $done = $idx <= $currentIndex; @endphp
                <div class="flex flex-col items-center gap-1.5 w-20">
                    <div class="h-10 w-10 rounded-2xl flex items-center justify-center {{ $done ? 'bg-violet-600 text-white shadow-md shadow-violet-600/25' : 'bg-slate-100 text-slate-400' }}">
                        <i class="fa-solid {{ $icon }} text-sm"></i>
                    </div>
                    <span class="text-[10px] font-bold {{ $done ? 'text-violet-700' : 'text-slate-400' }}">{{ $label }}</span>
                </div>
                @if (! $loop->last)
                    <div class="flex-1 h-1 rounded-full -mt-5 {{ $idx < $currentIndex ? 'bg-violet-500' : 'bg-slate-200' }}"></div>
                @endif
            @endforeach
        </div>

        <dl class="grid grid-cols-3 gap-y-2.5 text-[12px] border-t border-slate-100 pt-4">
            <dt class="text-slate-500">Keluhan</dt>
            <dd class="col-span-2 font-semibold text-slate-800">{{ $ticket->subject }}</dd>
            <dt class="text-slate-500">Pelanggan</dt>
            <dd class="col-span-2 font-semibold text-slate-800">{{ $ticket->customer_name }}</dd>
            @if ($ticket->location_label)
                <dt class="text-slate-500">Lokasi</dt>
                <dd class="col-span-2 text-slate-700">{{ $ticket->location_label }}</dd>
            @endif
            <dt class="text-slate-500">Dilaporkan</dt>
            <dd class="col-span-2 text-slate-700">{{ format_wib($ticket->created_at) }}</dd>
            @if ($ticket->resolved_at)
                <dt class="text-slate-500">Selesai</dt>
                <dd class="col-span-2 text-slate-700">{{ format_wib($ticket->resolved_at) }}</dd>
            @endif
        </dl>

        @if ($updates->isNotEmpty())
            <div class="border-t border-slate-100 pt-4 space-y-3">
                <p class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Riwayat</p>
                @foreach ($updates as $update)
                    <div class="flex gap-3">
                        <div class="h-7 w-7 rounded-full {{ $update->user_id ? 'bg-violet-100 text-violet-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center shrink-0 text-[10px]">
                            <i class="fa-solid {{ $update->user_id ? 'fa-headset' : 'fa-user' }}"></i>
                        </div>
                        <div class="min-w-0 flex-1 rounded-2xl bg-slate-50 border border-slate-100 px-3 py-2">
                            <p class="text-[10px] text-slate-400">
                                {{ $update->user_id ? 'Tim '.$tenant->name : 'Anda' }} · {{ format_wib($update->created_at) }}
                            </p>
                            <p class="text-[12px] text-slate-700 whitespace-pre-line">{{ $update->body }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-violet-100 bg-violet-50/60 px-4 py-3 text-[11px] text-violet-800 leading-relaxed">
        <i class="fa-solid fa-bookmark mr-1"></i> Simpan halaman ini untuk memantau status aduan. Anda juga bisa cek ulang dengan nomor tiket di halaman <a href="{{ url('/lapor') }}" class="font-bold underline">Lapor Gangguan</a>.
    </div>
</div>
@endsection
