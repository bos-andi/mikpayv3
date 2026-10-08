@extends('layouts.app')

@section('title', 'Daftar Affiliate — MikPay')

@php
    $pct = fn (float $v) => rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
    $hasRecurring = $settings['recurring_percent'] > 0 && $settings['recurring_months'] > 0;
    $input = 'w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition';
@endphp

@section('content')
<section class="min-h-screen grid lg:grid-cols-2">
    {{-- Left: value proposition --}}
    <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-slate-950 text-white p-12 xl:p-16">
        <div class="absolute inset-0 bg-grid-dark opacity-60"></div>
        <div class="glow-blob w-[460px] h-[460px] bg-indigo-600/30 -top-40 -left-32"></div>
        <div class="glow-blob w-[380px] h-[380px] bg-emerald-500/20 bottom-0 -right-32"></div>

        <a href="{{ route('partner.landing') }}" class="relative inline-flex items-center gap-3">
            <img src="{{ asset(config('mikpay.brand.logo', 'logo-mikpay.png')) }}?v=2" alt="MikPay" class="w-10 h-10 object-contain">
            <div>
                <p class="font-extrabold tracking-wide">{{ config('mikpay.brand.name', 'MikPay') }}<span class="text-indigo-300">.link</span></p>
                <p class="text-[10px] uppercase tracking-widest text-emerald-300 font-bold">Program Affiliate</p>
            </div>
        </a>

        <div class="relative">
            <h1 class="text-4xl xl:text-5xl font-extrabold tracking-tight leading-tight">
                Ubah relasi Anda menjadi
                <span class="bg-gradient-to-r from-emerald-300 to-cyan-300 bg-clip-text text-transparent">penghasilan.</span>
            </h1>
            <p class="mt-5 text-slate-300 leading-relaxed max-w-md">Bergabung gratis dan dapatkan komisi setiap ISP rujukan Anda berlangganan MikPay.</p>

            <div class="mt-10 space-y-4 max-w-md">
                @foreach (array_values(array_filter([
                    ['fa-percent', $pct($settings['first_percent']).'% komisi pembayaran pertama', 'Langsung tercatat begitu pelanggan membayar.'],
                    $hasRecurring ? ['fa-rotate', $pct($settings['recurring_percent']).'% dari setiap perpanjangan', 'Selama '.$settings['recurring_months'].' bulan sejak pembayaran pertama.'] : null,
                    ['fa-building-columns', 'Cair ke rekening / e-wallet', 'Minimal Rp'.number_format($settings['min_withdrawal'], 0, ',', '.').' per pencairan.'],
                ])) as $item)
                    <div class="flex gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                        <span class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-400 text-slate-950 flex items-center justify-center"><i class="fa-solid {{ $item[0] }}"></i></span>
                        <div>
                            <p class="font-bold">{{ $item[1] }}</p>
                            <p class="text-sm text-slate-400">{{ $item[2] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="relative text-xs text-slate-500">© {{ date('Y') }} {{ config('mikpay.brand.name', 'MikPay') }}. Semua hak dilindungi.</p>
    </div>

    {{-- Right: form --}}
    <div class="flex items-center justify-center px-4 py-12 sm:px-8 bg-gradient-to-b from-indigo-50/60 via-slate-50 to-white">
        <div class="w-full max-w-lg">
            <div class="lg:hidden text-center mb-6">
                <a href="{{ route('partner.landing') }}" class="inline-flex items-center justify-center gap-3">@include('components.logo')</a>
            </div>

            <div class="mb-6">
                <span class="inline-flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-widest text-indigo-600"><i class="fa-solid fa-handshake"></i> Program Affiliate</span>
                <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Daftar jadi Affiliate MikPay</h2>
                <p class="mt-2 text-sm text-slate-600">Isi data di bawah. Akun Anda aktif setelah ditinjau tim MikPay (biasanya kurang dari 1×24 jam).</p>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-xl shadow-indigo-900/5 p-6 sm:p-8">
                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-medium space-y-1">
                        @foreach ($errors->all() as $err)
                            <p>{{ $err }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('partner.register.store') }}" class="space-y-4" id="partnerRegisterForm">
                    @csrf
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">Nama lengkap</label>
                            <input id="name" name="name" value="{{ old('name') }}" required maxlength="120" class="{{ $input }}" placeholder="Sesuai KTP / rekening">
                        </div>
                        <div>
                            <label for="phone" class="block text-xs font-bold text-slate-700 mb-1.5">WhatsApp</label>
                            <input id="phone" name="phone" value="{{ old('phone') }}" required maxlength="20" inputmode="tel" class="{{ $input }}" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email login</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="190" class="{{ $input }}" placeholder="nama@email.com">
                        </div>
                        <div>
                            <label for="city" class="block text-xs font-bold text-slate-700 mb-1.5">Kota / Kabupaten</label>
                            <input id="city" name="city" value="{{ old('city') }}" required maxlength="100" class="{{ $input }}" placeholder="Contoh: Pemalang">
                        </div>
                    </div>
                    <div>
                        <label for="promotion_plan" class="block text-xs font-bold text-slate-700 mb-1.5">Bagaimana Anda akan mempromosikan MikPay? <span class="font-normal text-slate-400">(opsional)</span></label>
                        <textarea id="promotion_plan" name="promotion_plan" rows="3" maxlength="1000" class="{{ $input }}" placeholder="Contoh: Saya teknisi MikroTik, punya 20+ klien RT RW Net di sekitar Jawa Tengah.">{{ old('promotion_plan') }}</textarea>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <x-password-input id="password" name="password" label="Password" placeholder="Min. 8 karakter" autocomplete="new-password" :minlength="8" required />
                        <x-password-input id="password_confirmation" name="password_confirmation" label="Ulangi password" autocomplete="new-password" :minlength="8" required />
                    </div>

                    <label class="flex items-start gap-2.5 text-xs text-slate-600 leading-relaxed cursor-pointer">
                        <input type="checkbox" name="agree" value="1" @checked(old('agree')) required class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>
                            Saya setuju dengan <a href="{{ route('legal.terms') }}" target="_blank" class="font-semibold text-indigo-600 hover:underline">Syarat &amp; Ketentuan</a>
                            MikPay, tidak melakukan spam, dan tidak memakai kode referral untuk akun sendiri.
                        </span>
                    </label>

                    <x-turnstile />

                    <button type="submit" id="partnerSubmitBtn" class="w-full gradient-bg text-white text-sm font-bold py-3 rounded-xl shadow-lg shadow-indigo-500/20 hover:opacity-95 transition disabled:opacity-60">
                        <i class="fa-solid fa-paper-plane mr-1.5"></i> Kirim Pendaftaran Affiliate
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-slate-500 mt-6 space-y-2">
                <span class="block">Sudah punya akun affiliate? <a href="{{ route('login') }}" class="text-indigo-600 font-bold hover:underline">Masuk</a></span>
                <a href="{{ route('partner.landing') }}" class="hover:text-indigo-600 font-semibold transition">← Kembali ke Program Affiliate</a>
            </p>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.getElementById('partnerRegisterForm')?.addEventListener('submit', function () {
    const btn = document.getElementById('partnerSubmitBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-1.5"></i> Mengirim…';
    }
});
</script>
@endpush
