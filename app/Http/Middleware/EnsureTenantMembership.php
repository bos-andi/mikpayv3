<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pastikan user tenant hanya mengakses data tenant miliknya sendiri.
 */
class EnsureTenantMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->isPartner()) {
            return redirect()->away(central_url('/affiliate/dashboard'));
        }

        $tenant = current_tenant();
        if (! $tenant?->id) {
            return $next($request);
        }

        if ((int) $user->tenant_id !== (int) $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Tenant nonaktif / langganan habis: jangan 403.
        // Biarkan EnsureTenantSubscription mengarahkan ke halaman bayar perpanjang.

        return $next($request);
    }
}
