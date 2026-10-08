@extends('layouts.app')

@section('title', 'Lupa Password — MikPay')

@section('content')
<section class="min-h-screen flex items-center justify-center px-4 py-16 bg-gradient-to-b from-indigo-50/70 via-slate-50 to-white">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-3">
                @include('components.logo')
            </a>
            <p class="mt-4 text-sm text-slate-600 font-medium">Lupa password</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-xl shadow-indigo-900/5 p-6 sm:p-8">
            @if (session('status'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700 font-medium">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-medium">
                    {{ $errors->first() }}
                </div>
            @endif

            <p class="mb-4 text-xs text-slate-600 leading-relaxed">
                Masukkan email akun MikPay Anda. Kami akan mengirim tautan untuk membuat password baru.
            </p>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition"
                        placeholder="nama@bisnisanda.com"
                    >
                </div>

                <x-turnstile />

                <button type="submit" class="w-full gradient-bg text-white text-sm font-bold py-3 rounded-xl shadow-lg shadow-indigo-500/20 hover:opacity-95 transition">
                    Kirim Tautan Reset
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            <a href="{{ route('login') }}" class="hover:text-indigo-600 font-semibold transition">← Kembali ke halaman masuk</a>
        </p>
    </div>
</section>
@endsection
