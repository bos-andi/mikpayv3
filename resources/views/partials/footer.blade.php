@php
    $brand = config('mikpay.brand');
    $wa = config('mikpay.whatsapp');
    $home = route('home');
@endphp

<footer class="bg-white border-t border-slate-200 py-12 text-xs text-slate-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-3 flex-wrap justify-center md:justify-start">
            <img src="{{ asset($brand['logo'] ?? 'logo-mikpay.png') }}?v=2" alt="{{ $brand['name'] }}" class="w-8 h-8 object-contain shrink-0">
            <span class="font-bold text-sm text-slate-900">{{ $brand['domain'] }}</span>
            <span>© {{ date('Y') }} MikPay Management System. All rights reserved.</span>
        </div>
        <div class="flex items-center gap-6 text-slate-600 font-semibold">
            <a href="{{ $home }}#fitur" class="hover:text-indigo-600 transition">Fitur</a>
            <a href="{{ $home }}#remote-vpn" class="hover:text-indigo-600 transition">Free VPN Remote</a>
            <a href="{{ $home }}#harga" class="hover:text-indigo-600 transition">Harga</a>
            <a href="https://wa.me/{{ $wa['number'] }}" target="_blank" rel="noopener" class="hover:text-indigo-600 transition">Hubungi Kami</a>
        </div>
    </div>
</footer>
