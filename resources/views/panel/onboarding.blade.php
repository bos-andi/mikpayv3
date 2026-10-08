@extends('layouts.panel')

@section('title', 'Mulai Setup — MikPay')
@section('page', 'onboarding')

@section('content')
@php
    $checklist = $checklist ?? ['steps' => [], 'progress' => 0, 'required_completed' => 0, 'required_total' => 0];
    $base = config('mikpay.tenant_base_domain', 'mikpay.link');
    $fqdn = $tenant->subdomain ? $tenant->subdomain.'.'.$base : null;
    $trialDays = (int) config('mikpay.trial_days', 3);
    $trialEnd = $tenant->subscriptionEndsAt();
@endphp

<div class="max-w-3xl mx-auto space-y-6">
    <div class="text-center space-y-2">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-500/10 text-indigo-600 mb-2">
            <i class="fa-solid fa-rocket text-2xl"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Selamat datang di MikPay!</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">
            Ikuti langkah berikut setelah daftar agar panel, remote MikroTik, dan billing bisa digunakan penuh.
            @if ($fqdn)
                Panel Anda: <span class="font-mono font-semibold text-indigo-600">{{ $fqdn }}</span>
            @endif
        </p>
        @if ($trialEnd)
            <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 inline-block px-3 py-1 rounded-full font-semibold">
                Trial {{ $trialDays }} hari aktif sampai {{ format_wib($trialEnd) }}
            </p>
        @endif
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3">{{ session('success') }}</div>
    @endif

    {{-- Progress --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Progress setup wajib</span>
            <span class="text-xs font-bold text-indigo-600">{{ $checklist['required_completed'] }}/{{ $checklist['required_total'] }} selesai</span>
        </div>
        <div class="w-full bg-slate-200 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
            <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ $checklist['progress'] }}%;"></div>
        </div>
    </div>

    {{-- Steps --}}
    <div class="space-y-3">
        @foreach ($checklist['steps'] as $index => $step)
            <div class="bg-white dark:bg-slate-900 border rounded-2xl p-4 shadow-sm flex gap-4 {{ $step['done'] ? 'border-emerald-200 dark:border-emerald-500/30' : 'border-slate-200 dark:border-slate-800' }}">
                <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $step['done'] ? 'bg-emerald-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' }}">
                    @if ($step['done'])
                        <i class="fa-solid fa-check"></i>
                    @else
                        {{ $index + 1 }}
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $step['title'] }}</h3>
                        @if (! ($step['required'] ?? true))
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">Opsional</span>
                        @endif
                        @if ($step['done'])
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $step['description'] }}</p>
                    <div class="flex flex-wrap gap-2 mt-3">
                        @if ($step['url'])
                            <a href="{{ $step['url'] }}" class="inline-flex items-center gap-1.5 text-[11px] font-bold px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition">
                                {{ $step['action_label'] ?? 'Buka' }} <i class="fa-solid fa-arrow-right text-[9px]"></i>
                            </a>
                        @endif
                        @if ($step['key'] === 'script' && ! $step['done'])
                            <form method="POST" action="{{ panel_route('onboarding.script') }}">
                                @csrf
                                <button type="submit" class="text-[11px] font-bold px-3 py-1.5 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50 transition">
                                    <i class="fa-solid fa-check mr-1"></i> Saya sudah paste script
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Ringkasan alur --}}
    <div class="bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 text-xs text-slate-600 dark:text-slate-400 space-y-2">
        <p class="font-bold text-slate-800 dark:text-slate-200 text-sm">Ringkasan alur setelah daftar</p>
        <ol class="list-decimal ml-4 space-y-1.5">
            <li><strong>Daftar</strong> → dapat subdomain <span class="font-mono">{{ $fqdn ?: 'anda.mikpay.link' }}</span> + akun tunnel OpenVPN otomatis</li>
            <li><strong>Remote & OpenVPN</strong> → salin script, paste di Terminal MikroTik (sekali saja)</li>
            <li><strong>Settings → Router</strong> → isi host API tunnel (contoh: <span class="font-mono">an-1.mikpay.link:1001</span>), bukan IP lokal</li>
            <li><strong>Test Koneksi</strong> → jika OK, dashboard PPPoE & billing aktif</li>
            <li><strong>Remote</strong> → Winbox/OLT bisa diakses dari luar via port tunnel</li>
        </ol>
        <p class="text-[11px] text-slate-500 pt-1 border-t border-slate-200 dark:border-slate-700">
            <i class="fa-solid fa-shield-halved text-indigo-500 mr-1"></i>
            Data Anda terisolasi — setiap tenant hanya bisa melihat pelanggan & router milik sendiri di subdomain masing-masing.
        </p>
    </div>

    <div class="flex flex-wrap justify-center gap-3 pb-4">
        <a href="{{ panel_route('dashboard') }}" class="text-xs font-bold px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white transition">
            Ke Dashboard
        </a>
        @if ($checklist['all_required_done'] ?? false)
            <form method="POST" action="{{ panel_route('onboarding.dismiss') }}">
                @csrf
                <button type="submit" class="text-xs font-bold px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Sembunyikan panduan
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
