<?php

namespace App\Rules;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Turnstile implements ValidationRule
{
    /** Ikut dijalankan walau field tidak dikirim (bot biasanya tidak mengirimnya). */
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ok = app(TurnstileService::class)->verify(
            is_string($value) ? $value : null,
            request()->ip(),
        );

        if (! $ok) {
            $fail('Verifikasi keamanan gagal. Silakan selesaikan captcha lalu coba lagi.');
        }
    }
}
