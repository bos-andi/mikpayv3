@props([
    'id',
    'name',
    'label',
    'placeholder' => '',
    'required' => false,
    'minlength' => null,
    'autocomplete' => 'current-password',
    'value' => '',
])

<div {{ $attributes->merge(['class' => '']) }}>
    <label for="{{ $id }}" class="block text-xs font-bold text-slate-700 mb-1.5">{{ $label }}</label>
    <div class="relative">
        <input
            id="{{ $id }}"
            type="password"
            name="{{ $name }}"
            value="{{ $value }}"
            @if ($required) required @endif
            @if ($minlength) minlength="{{ $minlength }}" @endif
            autocomplete="{{ $autocomplete }}"
            placeholder="{{ $placeholder }}"
            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 pr-12 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white transition"
        >
        <button
            type="button"
            data-password-toggle="{{ $id }}"
            class="absolute inset-y-0 right-0 z-10 flex items-center justify-center w-11 text-slate-400 hover:text-indigo-600 transition cursor-pointer"
            aria-label="Tampilkan password"
            tabindex="-1"
        >
            <i class="fa-solid fa-eye-slash text-sm pointer-events-none" aria-hidden="true"></i>
        </button>
    </div>
</div>
