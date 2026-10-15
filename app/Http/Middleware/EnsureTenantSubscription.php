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

        // Panel terkunci jika nonaktif, trial/langganan habis, atau status inactive.
        // Tenant legacy tanpa tanggal berakhir tetap diizinkan selama status aktif/trial.
        if ($tenant->subscriptionIsActive()) {
            return $next($request);
        }

        if ($this->isSubscriptionRoute($request)) {
            return $next($request);
        }

        if (! $user->canAccessModule(\App\Support\TenantAccess::ADMIN)) {
            abort(402, 'Langganan MikPay ISP ini sudah berakhir. Hubungi admin ISP Anda untuk perpanjangan.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akun nonaktif atau langganan berakhir. Silakan bayar paket di halaman Langganan MikPay.',
            ], 402);
        }

        return redirect()
            ->to(panel_route('subscription'))
            ->with('error', 'Akun nonaktif atau langganan berakhir. Bayar paket di halaman ini untuk mengaktifkan panel kembali.');
    }

    protected function isSubscriptionRoute(Request $request): bool
    {
        return $request->routeIs([
            'panel.subscription',
            'panel.subscription.*',
            'tenant.subscription',
            'tenant.subscription.*',
        ]);
    }
}
