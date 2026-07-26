<?php

namespace App\Http\Middleware;

use App\Support\SubscriptionGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (SubscriptionGate::allowsAppAccess($user)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Langganan tidak aktif atau sudah berakhir. Silakan perpanjang di menu Langganan.',
            ], 402);
        }

        return redirect()
            ->route('langganan')
            ->with('error', 'Langganan tidak aktif atau sudah berakhir. Silakan perpanjang di halaman Langganan.');
    }
}
