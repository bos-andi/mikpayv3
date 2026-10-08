<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /** Subdomains that belong to the platform, not ISP clients. */
    protected array $reserved = [
        'www',
        'app',
        'user',
        'mail',
        'api',
        'admin',
        'remote',
        'tunnel',
        'cdn',
        'static',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            $user = Auth::user();
            if ($user?->tenant_id && ! $user->isSuperAdmin()) {
                $tenant = Tenant::query()->find($user->tenant_id);
            } elseif ($user?->isSuperAdmin() && ! $this->isTenantHost($request)) {
                $slug = Str::lower(trim((string) config('mikpay.superadmin_panel_tenant', '')));
                if ($slug !== '') {
                    $tenant = Tenant::query()->where('subdomain', $slug)->first();
                }
            }
        }

        $prefix = $this->isTenantHost($request) ? 'tenant' : 'panel';

        if ($tenant) {
            app()->instance('currentTenant', $tenant);
        }

        // The {tenant} domain segment would otherwise be passed positionally to controller actions.
        $request->route()?->forgetParameter('tenant');

        app()->instance('panelRoutePrefix', $prefix);
        view()->share('tenant', $tenant);
        view()->share('panelRoutePrefix', $prefix);

        return $next($request);
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $base = config('mikpay.tenant_base_domain');

        // {subdomain}.{tenant_base_domain}  e.g. sarpannet.mikpay.link
        if ($base && str_ends_with($host, '.'.$base)) {
            $subdomain = substr($host, 0, -strlen('.'.$base));

            if ($subdomain && ! $this->isReservedSubdomain($subdomain)) {
                $tenant = Tenant::query()->where('subdomain', $subdomain)->first();

                abort_unless($tenant, 404, 'Tenant tidak ditemukan.');

                return $tenant;
            }
        }

        return null;
    }

    protected function isTenantHost(Request $request): bool
    {
        $host = $request->getHost();
        $base = config('mikpay.tenant_base_domain');

        if (! $base || ! str_ends_with($host, '.'.$base)) {
            return false;
        }

        $subdomain = substr($host, 0, -strlen('.'.$base));

        return $subdomain && ! $this->isReservedSubdomain($subdomain);
    }

    protected function isReservedSubdomain(string $subdomain): bool
    {
        if (in_array($subdomain, $this->reserved, true)) {
            return true;
        }

        $pattern = (string) config('mikpay.tunnel.node_sub_pattern', '^(an|cgk)-[0-9]+$');

        return $pattern !== '' && (bool) preg_match('/'.$pattern.'/i', $subdomain);
    }
}
