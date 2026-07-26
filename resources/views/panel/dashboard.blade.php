@extends('layouts.panel')

@section('title', 'Dashboard — MikPay')
@section('page', 'dashboard')

@section('content')
<!-- Welcome Banner -->
                <div class="p-6 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-cyan-600 dark:from-indigo-900/50 dark:via-slate-900 dark:to-slate-900 border border-indigo-200 dark:border-indigo-500/20 text-white shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold mb-1">Selamat Datang di MikPay Control Center 👋</h2>
                        <p class="text-xs opacity-90 dark:text-slate-400">Monitoring seluruh jaringan Fiber Optik, OLT, MikroTik, dan Billing secara real-time.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ panel_route('gis') }}" class="bg-white/10 hover:bg-white/20 dark:bg-slate-800 dark:hover:bg-slate-700 border border-white/20 dark:border-slate-700 text-xs px-3.5 py-2 rounded-xl font-medium transition text-white">
                            <i class="fa-solid fa-map-marked-alt text-cyan-300 dark:text-cyan-400 mr-1.5"></i> Buka Peta ODP
                        </a>
                        <a href="{{ panel_route('remote') }}" class="bg-white/10 hover:bg-white/20 dark:bg-slate-800 dark:hover:bg-slate-700 border border-white/20 dark:border-slate-700 text-xs px-3.5 py-2 rounded-xl font-medium transition text-white">
                            <i class="fa-solid fa-sliders text-amber-300 dark:text-amber-400 mr-1.5"></i> Control OLT
                        </a>
                    </div>
                </div>

                <!-- 4 Metrics Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Pemasukan -->
                    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition">
                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider">Omset Bulan Ini</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <i class="fa-solid fa-rupiah-sign"></i>
                            </div>
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">Rp 28.450.000</h3>
                        <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-2 flex items-center gap-1 font-medium">
                            <i class="fa-solid fa-arrow-trend-up"></i> +12.5% dari bulan lalu
                        </p>
                    </div>

                    <!-- Total Pelanggan Active/Isolir -->
                    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition">
                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider">Pelanggan PPPoE</span>
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <i class="fa-solid fa-users"></i>
                            </div>
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">184 <span class="text-xs font-normal text-slate-500 dark:text-slate-400">Client</span></h3>
                        <div class="flex items-center gap-3 mt-2 text-[11px]">
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold"><i class="fa-solid fa-circle text-[8px] mr-1"></i>172 Aktif</span>
                            <span class="text-rose-600 dark:text-rose-400 font-semibold"><i class="fa-solid fa-circle text-[8px] mr-1"></i>12 Isolir</span>
                        </div>
                    </div>

                    <!-- ODP Port Occupancy -->
                    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition">
                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider">Kapasitas ODP</span>
                            <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                                <i class="fa-solid fa-box-archive"></i>
                            </div>
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">142 / 192 <span class="text-xs font-normal text-slate-500 dark:text-slate-400">Port</span></h3>
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-cyan-500 h-full rounded-full" style="width: 74%;"></div>
                        </div>
                    </div>

                    <!-- OLT & Optical Status -->
                    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition">
                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider">Status OLT HSGQ</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <i class="fa-solid fa-tower-cell"></i>
                            </div>
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">4 PON <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">NORMAL</span></h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">
                            Avg Optical Power: <span class="text-emerald-600 dark:text-emerald-400 font-mono font-semibold">-19.4 dBm</span>
                        </p>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Realtime Bandwidth Graph (2 cols) -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">Live Bandwidth Traffic (MikroTik ROS)</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Interface ether1-WAN & sfp-sfpplus1-LAN</p>
                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium"><i class="fa-solid fa-circle text-[8px]"></i> Download (450 Mbps)</span>
                                <span class="flex items-center gap-1 text-indigo-600 dark:text-indigo-400 font-medium"><i class="fa-solid fa-circle text-[8px]"></i> Upload (180 Mbps)</span>
                            </div>
                        </div>
                        <div class="h-64 relative">
                            <canvas id="chartBandwidth"></canvas>
                        </div>
                    </div>

                    <!-- Revenue vs Expense Donut / Status Breakdown -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 mb-1">Status Penagihan Bulan Ini</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Progres pembayaran tagihan bulanan pelanggan</p>
                            <div class="h-48 relative">
                                <canvas id="chartBillingStatus"></canvas>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 text-center text-xs">
                            <div class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800/50">
                                <p class="text-slate-500 dark:text-slate-400">Lunas</p>
                                <p class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">152 User</p>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800/50">
                                <p class="text-slate-500 dark:text-slate-400">Belum Bayar</p>
                                <p class="font-bold text-rose-600 dark:text-rose-400 text-sm">32 User</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Network Alerts & Activity -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-bell text-amber-500"></i> Aktivitas Sistem & Alert Terbaru
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-slate-200">Redaman Tinggi Terdeteksi! (LOS Hazard)</p>
                                    <p class="text-slate-500 dark:text-slate-400">Pelanggan: Asep Saepudin (ODP-BLOK-B2 Port 4) - Redaman Drop ke <span class="text-rose-600 dark:text-rose-400 font-mono font-bold">-28.9 dBm</span></p>
                                </div>
                            </div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0">5 Menit lalu</span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-robot"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-slate-200">Auto-Isolir Sistem Dijalankan</p>
                                    <p class="text-slate-500 dark:text-slate-400">Sistem otomatis men-suspend 12 akun PPPoE yang melewati jatuh tempo tanggal 20.</p>
                                </div>
                            </div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0">1 Jam lalu</span>
                        </div>
                    </div>
                </div>
@endsection

@push('scripts')
<script src="{{ asset('js/panel-charts.js') }}" defer></script>
@endpush
