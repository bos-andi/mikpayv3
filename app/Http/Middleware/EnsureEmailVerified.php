<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->requiresEmailVerification()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Verifikasi email Anda terlebih dahulu.'], 403);
        }

        return redirect()->away(central_url('/email/verifikasi'));
    }
}
