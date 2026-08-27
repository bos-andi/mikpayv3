<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscription
{
    /**
     * Soft-gate: jika trial/langganan habis, panel dikunci kecuali halaman Langganan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $tenant = current_tenant();
        if (! $tenant || ($tenant->plan ?? '') === 'tunnel') {
            return $next($request);
        }

        // Tenant tanpa tanggal berakhir (legacy) tetap diizinkan.
        if ($tenant->subscriptionEndsAt() === null) {
            return $next($request);
        }

        if ($tenant->subscriptionIsActive()) {
            return $next($request);
        }

        if ($request->routeIs([
            'panel.subscription',
            'panel.subscription.*',
            'tenant.subscription',
            'tenant.subscription.*',
        ])) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Langganan tidak aktif atau sudah berakhir. Silakan perpanjang di menu Langganan MikPay.',
            ], 402);
        }

        return redirect()
            ->to(panel_route('subscription'))
            ->with('error', 'Trial/langganan sudah berakhir. Silakan bayar paket di halaman ini untuk mengaktifkan panel kembali.');
    }
}
