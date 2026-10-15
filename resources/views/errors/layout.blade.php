<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — MikPay</title>
    @include('partials.favicon')
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] } } } }</script>
</head>
<body class="h-full bg-slate-50 font-sans antialiased flex items-center justify-center p-6">
    <div class="w-full max-w-md bg-white border border-slate-200 rounded-3xl shadow-xl p-8 text-center">
        <div class="mx-auto w-16 h-16 rounded-2xl @yield('iconClass') flex items-center justify-center text-2xl mb-5">
            <i class="fa-solid @yield('icon')"></i>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Error @yield('code')</p>
        <h1 class="mt-1 text-xl font-extrabold text-slate-900">@yield('title')</h1>
        <p class="mt-3 text-sm text-slate-600 leading-relaxed">@yield('message')</p>
        <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-2">
            @auth
                <a href="/dashboard" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-indigo-600/20">
                    <i class="fa-solid fa-house"></i> Ke Dashboard
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">
                        <i class="fa-solid fa-right-from-bracket"></i> Keluar
                    </button>
                </form>
            @else
                <a href="/login" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl">Masuk</a>
            @endauth
        </div>
    </div>
</body>
</html>
