<?php

use App\Models\Router;
use App\Support\ActiveRouter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

if (! function_exists('app_timezone')) {
    /** Zona waktu aplikasi (WIB = Asia/Jakarta). */
    function app_timezone(): string
    {
        return (string) config('app.timezone', 'Asia/Jakarta');
    }
}

if (! function_exists('wib_now')) {
    function wib_now(): Carbon
    {
        return Carbon::now(app_timezone());
    }
}

if (! function_exists('format_wib')) {
    /**
     * Format tanggal/waktu ke WIB, contoh: 11 Agu 2026 07:15 WIB
     */
    function format_wib(null|Carbon|string $value, string $format = 'd M Y H:i'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $dt = $value instanceof Carbon
                ? $value->copy()->timezone(app_timezone())
                : Carbon::parse($value, app_timezone())->timezone(app_timezone());
        } catch (\Throwable) {
            return null;
        }

        return $dt->translatedFormat($format).' WIB';
    }
}

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

if (! function_exists('tenant_panel_url')) {
    /**
     * URL panel di host subdomain tenant.
     * Contoh: https://sarpannet.mikpay.link/dashboard
     */
    function tenant_panel_url(?\App\Models\Tenant $tenant, string $path = '/dashboard'): string
    {
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $path = '/'.ltrim($path, '/');
        if (! $tenant?->subdomain) {
            return rtrim((string) config('app.url'), '/').$path;
        }

        return $scheme.'://'.$tenant->panelHost().$path;
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
