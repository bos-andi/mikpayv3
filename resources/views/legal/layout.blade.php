@extends('layouts.app')

@php
    $company = config('mikpay.company');
    $legalPages = [
        'legal.about' => ['Tentang Kami', 'fa-building'],
        'legal.terms' => ['Syarat & Ketentuan', 'fa-file-contract'],
        'legal.privacy' => ['Kebijakan Privasi', 'fa-user-shield'],
        'legal.refund' => ['Kebijakan Pengembalian Dana', 'fa-rotate-left'],
    ];
@endphp

@push('styles')
<style>
    .legal-prose { color: #334155; font-size: 0.95rem; line-height: 1.75; }
    .legal-prose h2 { color: #0f172a; font-weight: 800; font-size: 1.15rem; margin: 2.25rem 0 0.75rem; scroll-margin-top: 6rem; }
    .legal-prose h2:first-child { margin-top: 0; }
    .legal-prose h3 { color: #1e293b; font-weight: 700; font-size: 1rem; margin: 1.5rem 0 0.5rem; }
    .legal-prose p { margin: 0.75rem 0; }
    .legal-prose ul, .legal-prose ol { margin: 0.75rem 0; padding-left: 1.4rem; }
    .legal-prose ul { list-style: disc; }
    .legal-prose ol { list-style: decimal; }
    .legal-prose li { margin: 0.35rem 0; }
    .legal-prose a { color: #4f46e5; font-weight: 600; text-decoration: underline; text-underline-offset: 2px; }
    .legal-prose strong { color: #0f172a; }
    .legal-prose table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.875rem; }
    .legal-prose th, .legal-prose td { border: 1px solid #e2e8f0; padding: 0.6rem 0.75rem; text-align: left; vertical-align: top; }
    .legal-prose th { background: #f8fafc; color: #0f172a; font-weight: 700; }
</style>
@endpush

@section('content')
<section class="relative overflow-hidden bg-gradient-to-b from-indigo-50 via-white to-slate-50 pt-32 pb-12 border-b border-slate-200/70">
    <div class="absolute inset-0 bg-grid opacity-60"></div>
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[640px] h-[320px] rounded-full bg-cyan-300/20 blur-3xl"></div>
    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">@yield('legal_kicker', 'Legal')</p>
        <h1 class="mt-3 text-3xl sm:text-4xl font-extrabold text-slate-900">@yield('legal_title')</h1>
        <p class="mt-3 text-sm text-slate-600 max-w-2xl">@yield('legal_subtitle')</p>
        <p class="mt-4 text-xs text-slate-500"><i class="fa-regular fa-calendar mr-1"></i>Terakhir diperbarui: {{ $company['legal_updated_at'] }}</p>
    </div>
</section>

<section class="py-12 sm:py-16">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-4 gap-8">
        <aside class="lg:col-span-1">
            <nav class="lg:sticky lg:top-24 bg-white border border-slate-200 rounded-2xl p-2 shadow-sm">
                @foreach ($legalPages as $routeName => [$label, $icon])
                    <a href="{{ route($routeName) }}"
                       class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm transition {{ request()->routeIs($routeName) ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-semibold' }}">
                        <i class="fa-solid {{ $icon }} w-4 text-center text-xs"></i>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <article class="lg:col-span-3 bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 shadow-sm">
            <div class="legal-prose">
                @yield('legal_body')
            </div>

            <div class="mt-10 rounded-2xl bg-slate-50 border border-slate-200 p-5 text-sm text-slate-600">
                <p class="font-bold text-slate-900">{{ $company['name'] }}</p>
                <p class="mt-1">{{ $company['address'] }}</p>
                <p class="mt-1">NIB: {{ $company['nib'] }}</p>
                <p class="mt-2 flex flex-wrap gap-x-5 gap-y-1">
                    <a href="mailto:{{ $company['email'] }}" class="text-indigo-600 font-semibold"><i class="fa-regular fa-envelope mr-1"></i>{{ $company['email'] }}</a>
                    <a href="https://wa.me/{{ config('mikpay.whatsapp.number') }}" target="_blank" rel="noopener" class="text-indigo-600 font-semibold"><i class="fa-brands fa-whatsapp mr-1"></i>{{ $company['phone'] }}</a>
                </p>
            </div>
        </article>
    </div>
</section>
@endsection
