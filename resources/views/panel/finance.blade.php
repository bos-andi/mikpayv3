@extends('layouts.panel')

@section('title', 'Laporan Keuangan — MikPay')
@section('page', 'finance')

@section('content')
<h2 class="text-lg font-bold text-slate-900 dark:text-white">Laporan Keuangan & Laba Rugi</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Total Pemasukan (Bulan Ini)</p>
                        <h4 class="text-xl font-bold text-emerald-400 mt-1">Rp 28.450.000</h4>
                    </div>
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pengeluaran Operasional</p>
                        <h4 class="text-xl font-bold text-rose-400 mt-1">Rp 8.200.000</h4>
                    </div>
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Estimasi Profit Bersih</p>
                        <h4 class="text-xl font-bold text-indigo-400 mt-1">Rp 20.250.000</h4>
                    </div>
                </div>
@endsection
