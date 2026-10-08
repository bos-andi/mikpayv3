<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use App\Services\PartnerProgramService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Simpan kode referral dari ?ref=KODE ke cookie agar tetap terbaca saat calon pelanggan mendaftar.
 */
class CaptureReferral
{
    public function __construct(private PartnerProgramService $partners) {}

    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->query('ref');
        if (! is_string($raw) || $raw === '' || ! $request->isMethod('GET')) {
            return $next($request);
        }

        $partner = $this->partners->findActiveByCode($raw);
        if ($partner) {
            $current = PartnerProgramService::normalizeCode((string) $request->cookie(PartnerProgramService::COOKIE));
            if ($current !== $partner->code && ! $this->isBot($request)) {
                Partner::query()->whereKey($partner->id)->increment('clicks');
            }

            Cookie::queue(
                PartnerProgramService::COOKIE,
                $partner->code,
                $this->partners->settings()['cookie_days'] * 24 * 60
            );
        }

        return $next($request);
    }

    private function isBot(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram/i', (string) $request->userAgent());
    }
}
