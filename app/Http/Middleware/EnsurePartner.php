<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->isPartner() || ! $user->partner) {
            abort(403, 'Halaman ini khusus akun Affiliate MikPay.');
        }

        return $next($request);
    }
}
