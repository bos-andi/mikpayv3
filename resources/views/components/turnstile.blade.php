@if (\App\Services\TurnstileService::enabled())
    <div {{ $attributes->merge(['class' => 'min-h-[65px]']) }}>
        <div class="cf-turnstile"
             data-sitekey="{{ \App\Services\TurnstileService::siteKey() }}"
             data-theme="light"
             data-size="flexible"
             data-language="id"></div>
    </div>

    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
