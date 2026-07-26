@extends('layouts.panel')

@section('title', 'Hotspot & Voucher — MikPay')
@section('page', 'vouchers')

@section('content')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Hotspot & Voucher Generator</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Generate kode voucher massal untuk jualan internet koin / harian.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Form Generator -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 space-y-4">
                        <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">Buat Voucher Baru</h3>
                        <div class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-500 dark:text-slate-400 mb-1">Pilih Paket Profile</label>
                                <select id="vProfile" class="w-full bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-2 text-slate-800 dark:text-slate-200">
                                    <option value="5000">1 Hari / Rp 5.000 (Speed 5 Mbps)</option>
                                    <option value="10000">3 Hari / Rp 10.000 (Speed 5 Mbps)</option>
                                    <option value="50000">30 Hari / Rp 50.000 (Speed 10 Mbps)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-500 dark:text-slate-400 mb-1">Jumlah Voucher</label>
                                <input type="number" id="vQty" value="6" class="w-full bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-2 text-slate-800 dark:text-slate-200">
                            </div>
                            <button onclick="generateVouchers()" class="w-full bg-indigo-600 hover:bg-indigo-500 text-slate-900 dark:text-white font-bold py-2.5 rounded-xl transition">
                                <i class="fa-solid fa-bolt mr-1"></i> Generate Voucher Massal
                            </button>
                        </div>
                    </div>

                    <!-- Voucher Card Preview Area (2 Cols) -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">Preview Layout Cetak Voucher</h3>
                            <button onclick="window.print()" class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-700 text-xs px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200">
                                <i class="fa-solid fa-print mr-1"></i> Cetak Ke Paper/Printer
                            </button>
                        </div>

                        <!-- Dynamic Vouchers Container -->
                        <div id="voucherContainer" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <!-- Voucher item preview generated via JS -->
                        </div>
                    </div>
                </div>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => { if (typeof generateVouchers === 'function') generateVouchers(); });</script>
@endpush
