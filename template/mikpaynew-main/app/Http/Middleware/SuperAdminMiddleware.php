<?php

namespace App\Http\Middleware;

use App\Support\SuperAdminCredentials;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get(SuperAdminCredentials::sessionKey())) {
            return $next($request);
        }

        if ($request->user() && $request->user()->isSuperAdmin()) {
            return $next($request);
        }

        return redirect()->guest(route('superadmin.login'));
    }
}
