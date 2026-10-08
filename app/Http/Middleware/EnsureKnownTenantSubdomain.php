<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokir subdomain tenant yang sudah tidak terdaftar di database.
 * Tanpa ini, wildcard DNS (*.mikpay.link) masih menampilkan halaman login/daftar.
 */
class EnsureKnownTenantSubdomain
{
    /** @var list<string> */
    protected array $reserved = [
        'www', 'app', 'user', 'mail', 'api', 'admin', 'remote', 'tunnel', 'cdn', 'static',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $base = strtolower((string) config('mikpay.tenant_base_domain', 'mikpay.link'));

        if ($base === '' || ! str_ends_with($host, '.'.$base)) {
            return $next($request);
        }

        $subdomain = substr($host, 0, -strlen('.'.$base));
        if ($subdomain === '' || $this->isReservedSubdomain($subdomain)) {
            return $next($request);
        }

        $exists = Tenant::query()->where('subdomain', $subdomain)->exists();
        abort_unless($exists, 404, 'Tenant tidak ditemukan.');

        return $next($request);
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
