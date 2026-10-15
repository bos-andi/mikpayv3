<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi menu panel tenant sesuai hak akses staf.
 * Pemakaian: ->middleware('tenant.can:customers') atau 'tenant.can:admin'.
 */
class EnsureTenantPermission
{
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $user = $request->user();

        if ($user && $user->canAccessModule(...$modules)) {
            return $next($request);
        }

        $message = 'Anda tidak punya akses ke menu ini. Hubungi admin ISP Anda untuk meminta akses.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        abort(403, $message);
    }
}
