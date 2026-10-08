@extends('layouts.app')

@section('title', 'Buat Password Baru — MikPay')

@section('content')
<section class="min-h-screen flex items-center justify-center px-4 py-16 bg-gradient-to-b from-indigo-50/70 via-slate-50 to-white">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-3">
                @include('components.logo')
            </a>
            <p class="mt-4 text-sm text-slate-600 font-medium">Buat password baru</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-xl shadow-indigo-900/5 p-6 sm:p-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-medium">
                    {{ $errors->first() }}
                    @if ($errors->has('email'))
                        <a href="{{ route('password.request') }}" class="block mt-1 font-bold underline">Minta tautan baru</a>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $email) }}"
                        required
                        autocomplete="username"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition"
                    >
                </div>

                <x-password-input
                    id="password"
                    name="password"
                    label="Password baru"
                    placeholder="Minimal 8 karakter"
                    autocomplete="new-password"
                    minlength="8"
                    required
                />

                <x-password-input
                    id="password_confirmation"
                    name="password_confirmation"
                    label="Ulangi password baru"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    minlength="8"
                    required
                />

                <button type="submit" class="w-full gradient-bg text-white text-sm font-bold py-3 rounded-xl shadow-lg shadow-indigo-500/20 hover:opacity-95 transition">
                    Simpan Password Baru
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            <a href="{{ route('login') }}" class="hover:text-indigo-600 font-semibold transition">← Kembali ke halaman masuk</a>
        </p>
    </div>
</section>
@endsection
