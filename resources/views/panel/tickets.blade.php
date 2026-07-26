@extends('layouts.panel')

@section('title', 'Tiket Gangguan — MikPay')
@section('page', 'tickets')

@section('content')
<h2 class="text-lg font-bold text-slate-900 dark:text-white">Tiket Perbaikan Lapangan (Field Ops)</h2>
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-3">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-100 dark:bg-slate-800/40 flex justify-between items-center text-xs">
                        <div>
                            <p class="font-bold text-slate-900 dark:text-slate-100">Kabel Dropcore Putus Tertimpa Pohon</p>
                            <p class="text-slate-500 dark:text-slate-400">Lokasi: ODP-BLOK-B2 (Pelanggan Asep Saepudin) | Assigned: Teknisi Rian</p>
                        </div>
                        <span class="px-2 py-1 rounded bg-amber-500/10 text-amber-400 font-bold text-[10px]">IN PROGRESS</span>
                    </div>
                </div>
@endsection
