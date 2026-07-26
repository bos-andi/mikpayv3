<?php

namespace App\Support;

use App\Models\Router;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class ActiveRouter
{
    public const SESSION_KEY = 'selected_router_id';

    /**
     * @return Collection<int, Router>
     */
    public static function forTenant(?Tenant $tenant = null): Collection
    {
        $tenant ??= current_tenant();

        if (! $tenant?->id) {
            return collect();
        }

        return Router::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public static function current(?Tenant $tenant = null): ?Router
    {
        $tenant ??= current_tenant();

        if (! $tenant?->id) {
            return null;
        }

        $routers = self::forTenant($tenant);
        if ($routers->isEmpty()) {
            return null;
        }

        $selectedId = (int) Session::get(self::SESSION_KEY);
        if ($selectedId > 0) {
            $match = $routers->firstWhere('id', $selectedId);
            if ($match) {
                return $match;
            }
        }

        $fallback = $routers->firstWhere('is_default', true)
            ?? $routers->first(fn (Router $r) => $r->isEnabled())
            ?? $routers->first();

        if ($fallback) {
            Session::put(self::SESSION_KEY, $fallback->id);
        }

        return $fallback;
    }

    public static function select(Router $router): void
    {
        Session::put(self::SESSION_KEY, $router->id);
    }

    public static function clearIf(Router $router): void
    {
        if ((int) Session::get(self::SESSION_KEY) === (int) $router->id) {
            Session::forget(self::SESSION_KEY);
        }
    }

    public static function label(?Router $router = null): string
    {
        $router ??= self::current();

        if (! $router) {
            return 'Belum pilih router';
        }

        $title = $router->displayName();

        return $title.' ('.$router->endpointLabel().')';
    }
}
