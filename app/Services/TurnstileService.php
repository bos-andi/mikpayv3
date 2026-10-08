<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    public const FIELD = 'cf-turnstile-response';

    protected const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function enabled(): bool
    {
        return (bool) config('services.turnstile.enabled')
            && filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'));
    }

    public static function siteKey(): string
    {
        return (string) config('services.turnstile.site_key', '');
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! self::enabled()) {
            return true;
        }

        $token = trim((string) $token);
        if ($token === '' || strlen($token) > 2048) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => (string) config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (\Throwable $e) {
            // Cloudflare tidak terjangkau: jangan kunci semua user keluar.
            Log::warning('Turnstile verify unreachable', ['error' => $e->getMessage()]);

            return true;
        }

        if (! $response->successful()) {
            Log::warning('Turnstile verify HTTP error', ['status' => $response->status()]);

            return true;
        }

        if ($response->json('success') === true) {
            return true;
        }

        Log::info('Turnstile verify rejected', [
            'ip' => $ip,
            'errors' => $response->json('error-codes'),
        ]);

        return false;
    }
}
