<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            // Tunnel / central: ikuti tenant user yang login (pelanggan daftar sendiri).
            $user = Auth::user();
            if ($user?->tenant_id) {
                $tenant = Tenant::query()->find($user->tenant_id);
            }
        }

        if (! $tenant) {
            // Central domain (/dashboard) uses demo/owner tenant until auth is ready.
            $tenant = Tenant::query()->where('subdomain', 'annetwifi')->first()
                ?? Tenant::query()->where('subdomain', 'demo')->first()
                ?? Tenant::query()->where('subdomain', 'cl45')->first()
                ?? $this->demoTenant();
        }

        $prefix = $this->isTenantHost($request) ? 'tenant' : 'panel';

        app()->instance('currentTenant', $tenant);
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

        // Explicit override: /dashboard?tenant=sarpannet
        if ($slug = $request->query('tenant')) {
            return Tenant::query()->where('subdomain', $slug)->first();
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

    protected function demoTenant(): Tenant
    {
        return new Tenant([
            'name' => 'ANNET WIFI',
            'subdomain' => 'annetwifi',
            'status' => 'active',
            'plan' => 'pro',
            'vpn_status' => 'online',
            'remote_host' => config('mikpay.tunnel.default_node_host', 'an-1.mikpay.link'),
            'router_label' => 'RB4011-iGS+ (10.10.0.1)',
        ]);
    }
}
