@extends('layouts.public-pay')

@section('title', 'Lapor Gangguan')
@section('subtitle', 'Layanan aduan pelanggan')

@section('content')
@php
    $field = 'w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20';
    $needLast4 = session('need_last4') || filled(old('phone_last4'));
    $selectedIssue = old('issue', 'mati');
@endphp

<div class="space-y-4">
    <div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl shadow-violet-900/5 p-5 space-y-5">
        <div class="flex items-start gap-3">
            <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-orange-500 to-rose-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-orange-500/25">
                <i class="fa-solid fa-headset text-lg"></i>
            </div>
            <div>
                <h1 class="text-base font-extrabold text-slate-900">Lapor gangguan</h1>
                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">Internet bermasalah? Kirim aduan di sini, tim {{ $tenant->name }} akan segera menindaklanjuti.</p>
            </div>
        </div>

        <form method="POST" action="{{ url('/lapor') }}" class="space-y-4" id="complaintForm">
            @csrf
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

            <div class="rounded-2xl border border-violet-100 bg-violet-50/50 p-3.5 space-y-3">
                <p class="text-[11px] font-extrabold text-violet-700 uppercase tracking-wider">1 · Data pelanggan</p>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Kode pelanggan atau nomor HP terdaftar</label>
                    <input type="text" name="identifier" id="identifierInput" value="{{ old('identifier') }}" required maxlength="32" autocomplete="off"
                           class="{{ $field }} bg-white" placeholder="Contoh: AG7K2M atau 0812xxxxxxx">
                    @error('identifier')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div id="last4Wrap" class="{{ $needLast4 ? '' : 'hidden' }}">
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">4 digit akhir HP terdaftar</label>
                    <input type="text" name="phone_last4" value="{{ old('phone_last4') }}" maxlength="4" pattern="\d{4}" inputmode="numeric"
                           class="{{ $field }} bg-white font-mono tracking-widest" placeholder="1234">
                    <p class="text-[10px] text-slate-400 mt-1">Untuk memastikan aduan benar dari pemilik akun.</p>
                    @error('phone_last4')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-[11px] font-extrabold text-violet-700 uppercase tracking-wider">2 · Jenis keluhan</p>
                <div class="grid grid-cols-1 gap-2">
                    @foreach ($issues as $key => $issue)
                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3.5 py-3 cursor-pointer transition hover:border-violet-300 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50 has-[:checked]:ring-2 has-[:checked]:ring-violet-500/15">
                            <input type="radio" name="issue" value="{{ $key }}" class="accent-violet-600" @checked($selectedIssue === $key)>
                            <span class="h-8 w-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                                <i class="fa-solid {{ $issue['icon'] }} text-xs"></i>
                            </span>
                            <span class="text-sm font-semibold text-slate-800">{{ $issue['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('issue')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="space-y-3">
                <p class="text-[11px] font-extrabold text-violet-700 uppercase tracking-wider">3 · Keterangan</p>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Ceritakan masalahnya <span class="font-normal text-slate-400">(opsional)</span></label>
                    <textarea name="description" rows="3" maxlength="1000" class="{{ $field }}"
                              placeholder="Contoh: Lampu LOS di modem merah sejak tadi pagi">{{ old('description') }}</textarea>
                    @error('description')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Lokasi / patokan <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="text" name="location" value="{{ old('location') }}" maxlength="200" class="{{ $field }}" placeholder="RT 02, depan masjid">
                        @error('location')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Nomor WA aktif <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="tel" name="contact_phone" value="{{ old('contact_phone') }}" maxlength="20" inputmode="tel" class="{{ $field }}" placeholder="Jika beda dari yang terdaftar">
                        @error('contact_phone')<p class="text-[10px] text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-rose-500 hover:from-orange-400 hover:to-rose-400 text-white text-sm font-bold py-3 rounded-2xl shadow-lg shadow-orange-500/25 transition">
                <i class="fa-solid fa-paper-plane"></i> Kirim aduan
            </button>
        </form>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-3xl shadow-sm p-5 space-y-3">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-magnifying-glass text-violet-500"></i>
            <h2 class="text-sm font-extrabold text-slate-900">Cek status aduan</h2>
        </div>
        @if (session('check_error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-[11px] font-semibold text-rose-700">{{ session('check_error') }}</div>
        @endif
        <form method="POST" action="{{ url('/lapor/cek') }}" class="grid grid-cols-5 gap-2">
            @csrf
            <input type="text" name="code" value="{{ old('code') }}" required maxlength="32" placeholder="TKT-2026-0001"
                   class="col-span-3 {{ $field }} font-mono uppercase text-xs">
            <input type="text" name="check_last4" required maxlength="4" pattern="\d{4}" inputmode="numeric" placeholder="4 digit HP"
                   class="col-span-2 {{ $field }} font-mono text-xs">
            <button type="submit" class="col-span-5 text-xs font-bold py-2.5 rounded-2xl border border-violet-200 text-violet-700 hover:bg-violet-50 transition">
                Cek status
            </button>
        </form>
    </div>

    <p class="text-center text-[11px] text-slate-500">
        Ingin bayar tagihan? <a href="{{ url('/bayar') }}" class="font-bold text-violet-600 hover:underline">Cek tagihan di sini</a>
    </p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('identifierInput');
    var wrap = document.getElementById('last4Wrap');
    var last4 = wrap.querySelector('input');
    function sync() {
        var v = (input.value || '').trim();
        var digits = v.replace(/\D+/g, '');
        var isPhone = digits.length >= 9 && /^[\d\s+\-().]+$/.test(v);
        var show = v !== '' && !isPhone;
        wrap.classList.toggle('hidden', !show);
        last4.required = show;
    }
    input.addEventListener('input', sync);
    sync();
})();
</script>
@endpush
