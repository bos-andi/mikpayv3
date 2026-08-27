<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paksa user tenant memakai host subdomain mereka
 * agar UI panel sama persis di {subdomain}.mikpay.link
 */
class RedirectTenantToOwnHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $tenant = $user->tenant ?? current_tenant();
        if (! $tenant?->subdomain) {
            return $next($request);
        }

        $base = (string) config('mikpay.tenant_base_domain', 'mikpay.link');
        $expectedHost = strtolower($tenant->subdomain.'.'.$base);
        $currentHost = strtolower($request->getHost());

        // Sudah di host tenant sendiri.
        if ($currentHost === $expectedHost) {
            return $next($request);
        }

        // Hanya redirect dari apex / www (central panel), bukan dari host lain.
        $central = [
            strtolower((string) config('mikpay.central_domain', 'mikpay.link')),
            'www.'.strtolower((string) config('mikpay.central_domain', 'mikpay.link')),
        ];
        if (! in_array($currentHost, $central, true)) {
            return $next($request);
        }

        $target = tenant_panel_url($tenant, $request->getRequestUri());

        return redirect()->away($target);
    }
}
