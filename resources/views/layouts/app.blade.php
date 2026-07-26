<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('mikpay.seo.title'))</title>
    <meta name="description" content="@yield('meta_description', config('mikpay.seo.description'))">

    @include('partials.head')

    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased selection:bg-indigo-500 selection:text-white">
    @unless ($hideNavbar ?? false)
        @include('partials.navbar')
    @endunless

    <main>
        @yield('content')
    </main>

    @unless ($hideFooter ?? false)
        @include('partials.footer')
    @endunless

    @include('components.toast')

    <script src="{{ asset('js/mikpay.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
