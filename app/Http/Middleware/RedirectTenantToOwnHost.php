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

        // Bukan host tenant sendiri — tolak akses ke subdomain tenant lain.
        if ($currentHost !== $expectedHost) {
            $central = [
                strtolower((string) config('mikpay.central_domain', 'mikpay.link')),
                'www.'.strtolower((string) config('mikpay.central_domain', 'mikpay.link')),
            ];

            if (in_array($currentHost, $central, true)) {
                $target = tenant_panel_url($tenant, $request->getRequestUri());

                return redirect()->away($target);
            }

            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        return $next($request);
    }
}
