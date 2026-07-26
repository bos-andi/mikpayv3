@extends('layouts.panel')

@section('title', 'Peta GIS & ODP — MikPay')
@section('page', 'gis')

@section('content')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Pemetaan Jaringan Fiber Optik (GIS Infrastructure)</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pemetaan lokasi OLT, Kotak ODP, Jalur Kabel FO, dan Rumah Pelanggan.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="showToast('Menambahkan ODP Baru ke Koordinat Peta')" class="bg-indigo-600 hover:bg-indigo-500 text-xs px-3.5 py-2 rounded-xl font-semibold transition text-white shadow-sm">
                            <i class="fa-solid fa-plus mr-1"></i> Tambah ODP Baru
                        </button>
                    </div>
                </div>

                <!-- GIS Map Container with Sidebar Info -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 h-[650px]">
                    <!-- Interactive Leaflet Map (3 Cols) -->
                    <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden relative shadow-sm">
                        <div id="gisMap" class="w-full h-full"></div>
                        
                        <!-- Map Legend Overlay -->
                        <div class="absolute bottom-4 left-4 z-[1000] bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-slate-200 dark:border-slate-800 p-3 rounded-xl text-[11px] space-y-1.5 shadow-xl">
                            <p class="font-bold text-slate-800 dark:text-slate-200 mb-1">Legenda Peta:</p>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span>
                                <span class="text-slate-700 dark:text-slate-300">Server OLT Center</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-cyan-400 inline-block"></span>
                                <span class="text-slate-700 dark:text-slate-300">Kotak ODP (Fiber Splitter)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-emerald-400 inline-block"></span>
                                <span class="text-slate-700 dark:text-slate-300">Pelanggan Active (ONU)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
                                <span class="text-slate-700 dark:text-slate-300">Pelanggan Isolir / Redaman Jelek</span>
                            </div>
                        </div>
                    </div>

                    <!-- ODP & Port Inspector Sidepanel (1 Col) -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex flex-col justify-between overflow-y-auto space-y-4 shadow-sm">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 mb-2 flex items-center justify-between">
                                <span>Inspector ODP</span>
                                <span class="text-[10px] bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 px-2 py-0.5 rounded-full font-mono font-bold">ODP-BLOK-A1</span>
                            </h3>
                            
                            <!-- ODP Specs summary -->
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/50 space-y-2 text-xs mb-4">
                                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                                    <span>Tersambung ke PON:</span>
                                    <span class="text-slate-800 dark:text-slate-200 font-semibold">PON 1 (HSGQ OLT)</span>
                                </div>
                                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                                    <span>Kapasitas Splitter:</span>
                                    <span class="text-slate-800 dark:text-slate-200 font-semibold">1 : 8 Port</span>
                                </div>
                                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                                    <span>Rata-rata Redaman:</span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-mono font-semibold">-18.5 dBm (Bagus)</span>
                                </div>
                            </div>

                            <h4 class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Mapping 8 Port ODP:</h4>
                            <!-- 8 Port Grid Buttons -->
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300">
                                    <p class="font-bold text-[10px]">Port 1: Aktif</p>
                                    <p class="truncate font-semibold">Budi Santoso</p>
                                    <p class="text-[9px] text-slate-500 dark:text-slate-400 font-mono">-17.8 dBm</p>
                                </div>
                                <div class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300">
                                    <p class="font-bold text-[10px]">Port 2: Aktif</p>
                                    <p class="truncate font-semibold">Siti Aminah</p>
                                    <p class="text-[9px] text-slate-500 dark:text-slate-400 font-mono">-18.2 dBm</p>
                                </div>
                                <div class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300">
                                    <p class="font-bold text-[10px]">Port 3: Aktif</p>
                                    <p class="truncate font-semibold">Agus Setiawan</p>
                                    <p class="text-[9px] text-slate-500 dark:text-slate-400 font-mono">-19.1 dBm</p>
                                </div>
                                <div class="p-2 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300">
                                    <p class="font-bold text-[10px]">Port 4: Isolir</p>
                                    <p class="truncate font-semibold">Asep Saepudin</p>
                                    <p class="text-[9px] text-rose-600 dark:text-rose-400 font-mono font-bold">-28.9 dBm ⚠️</p>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400">
                                    <p class="font-bold text-[10px]">Port 5: KOSONG</p>
                                    <p class="text-[10px] italic">Siap Pakai</p>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400">
                                    <p class="font-bold text-[10px]">Port 6: KOSONG</p>
                                    <p class="text-[10px] italic">Siap Pakai</p>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400">
                                    <p class="font-bold text-[10px]">Port 7: KOSONG</p>
                                    <p class="text-[10px] italic">Siap Pakai</p>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400">
                                    <p class="font-bold text-[10px]">Port 8: KOSONG</p>
                                    <p class="text-[10px] italic">Siap Pakai</p>
                                </div>
                            </div>
                        </div>

                        <button onclick="showToast('Memindai ulang sinyal optik seluruh ODP...')" class="w-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs py-2 rounded-xl font-medium border border-slate-200 dark:border-slate-700 transition text-slate-800 dark:text-slate-200">
                            <i class="fa-solid fa-arrows-rotate mr-1"></i> Scan Ulang Redaman ODP
                        </button>
                    </div>
                </div>
@endsection

@push('scripts')
<script src="{{ asset('js/panel-gis.js') }}" defer></script>
@endpush
