@php
    $brand = config('mikpay.brand');
    $size = $size ?? 'md';
    $showTagline = $showTagline ?? true;
    $showText = $showText ?? true;
    $logoUrl = asset(config('mikpay.brand.logo', 'logo-mikpay.png'));

    $box = $size === 'sm'
        ? 'w-8 h-8'
        : ($size === 'lg' ? 'w-12 h-12' : 'w-10 h-10');

    $domain = (string) ($brand['domain'] ?? 'mikpay.link');
    $tld = str_contains($domain, '.') ? substr($domain, strpos($domain, '.')) : '.link';
@endphp

<img src="{{ $logoUrl }}?v=2" alt="{{ $brand['name'] ?? 'MikPay' }}" class="{{ $box }} object-contain shrink-0">
@if ($showText)
<div>
    <span class="font-extrabold {{ $size === 'sm' ? 'text-sm' : 'text-xl' }} tracking-wider text-slate-900">
        {{ $brand['name'] }}<span class="text-violet-600">{{ $tld }}</span>
    </span>
    @if ($showTagline)
        <span class="block text-[9px] uppercase tracking-widest text-slate-500 font-semibold -mt-1">
            {{ $brand['tagline'] }}
        </span>
    @endif
</div>
@endif
