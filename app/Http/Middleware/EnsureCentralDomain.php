<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paksa route platform (superadmin) hanya di domain utama mikpay.link.
 */
class EnsureCentralDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isCentralHost($request)) {
            return $next($request);
        }

        $central = strtolower((string) config('mikpay.central_domain', 'mikpay.link'));
        $scheme = $request->getScheme() ?: 'https';
        $target = $scheme.'://'.$central.$request->getRequestUri();

        return redirect()->away($target);
    }

    public function isCentralHost(Request $request): bool
    {
        $host = strtolower($request->getHost());
        $central = strtolower((string) config('mikpay.central_domain', 'mikpay.link'));

        return in_array($host, [$central, 'www.'.$central], true);
    }
}
