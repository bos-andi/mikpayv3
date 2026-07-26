@extends('layouts.app')

@section('title', 'Login — MikPay')

@section('content')
<section class="min-h-screen flex items-center justify-center px-4 py-16 bg-gradient-to-b from-indigo-50/70 via-slate-50 to-white">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-3">
                @include('components.logo')
            </a>
            <p class="mt-4 text-sm text-slate-600 font-medium">Masuk ke Control Center</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-xl shadow-indigo-900/5 p-6 sm:p-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 font-medium">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
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

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition"
                        placeholder="••••••••"
                    >
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-600 font-medium">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Ingat saya
                </label>

                <button type="submit" class="w-full gradient-bg text-white text-sm font-bold py-3 rounded-xl shadow-lg shadow-indigo-500/20 hover:opacity-95 transition">
                    Masuk
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6 space-y-2">
            @if (request()->getHost() === config('mikpay.tunnel_domain', 'tunnel.mikpay.link'))
                <span class="block">Belum punya akun Tunnel?
                    <a href="{{ route('tunnel.register') }}" class="text-violet-600 font-bold hover:underline">Daftar di sini</a>
                </span>
            @else
                <span class="block">Belum punya akun MikPay?
                    <a href="{{ route('register') }}" class="text-indigo-600 font-bold hover:underline">Daftar trial gratis</a>
                </span>
            @endif
            <a href="{{ request()->getHost() === config('mikpay.tunnel_domain') ? route('tunnel.home') : route('home') }}" class="hover:text-indigo-600 font-semibold transition">← Kembali ke beranda</a>
        </p>
    </div>
</section>
@endsection
