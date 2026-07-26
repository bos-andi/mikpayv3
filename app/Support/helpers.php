<?php

use App\Models\Router;
use App\Support\ActiveRouter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

if (! function_exists('panel_route')) {
    /**
     * Panel URL helper:
     * - Central: mikpay.link/dashboard
     * - Tenant:  sarpannet.mikpay.link/dashboard
     */
    function panel_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $prefix = app()->bound('panelRoutePrefix')
            ? app('panelRoutePrefix')
            : 'panel';

        $routeName = $prefix.'.'.$name;

        if ($prefix === 'tenant') {
            $tenant = current_tenant();
            $base = ['tenant' => $tenant?->subdomain];

            if ($parameters instanceof \Illuminate\Database\Eloquent\Model) {
                $parameters = array_merge($base, [$parameters]);
            } elseif (is_array($parameters)) {
                $parameters = array_merge($base, $parameters);
            } elseif ($parameters !== [] && $parameters !== null) {
                $parameters = array_merge($base, [$parameters]);
            } else {
                $parameters = $base;
            }
        }

        if (Route::has($routeName)) {
            return route($routeName, $parameters, $absolute);
        }

        return route('panel.'.$name, $parameters, $absolute);
    }
}

if (! function_exists('current_tenant')) {
    function current_tenant(): ?\App\Models\Tenant
    {
        return app()->bound('currentTenant') ? app('currentTenant') : null;
    }
}

if (! function_exists('current_router')) {
    /**
     * Router MikroTik yang sedang aktif di session panel.
     * Semua data dinamis (PPP, isolir, dll.) harus mengikuti router ini.
     */
    function current_router(): ?Router
    {
        return ActiveRouter::current();
    }
}

if (! function_exists('tenant_routers')) {
    /**
     * @return Collection<int, Router>
     */
    function tenant_routers(): Collection
    {
        return ActiveRouter::forTenant();
    }
}

if (! function_exists('tenant_pay_url')) {
    /**
     * URL publik bayar tanpa login.
     * Default: https://mikpay.link/bayar/{token} (stabil, tanpa butuh wildcard DNS).
     * Opsional subdomain: set MIKPAY_PAY_LINK_USE_SUBDOMAIN=true jika *.{tenant_base} sudah resolve.
     */
    function tenant_pay_url(\App\Models\Tenant $tenant, string $token): string
    {
        $token = ltrim($token, '/');
        $useSubdomain = (bool) config('mikpay.payment.pay_link_use_subdomain', false);

        if ($useSubdomain && $tenant->subdomain) {
            $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

            return $scheme.'://'.$tenant->panelHost().'/bayar/'.$token;
        }

        return rtrim((string) config('app.url'), '/').'/bayar/'.$token;
    }
}
