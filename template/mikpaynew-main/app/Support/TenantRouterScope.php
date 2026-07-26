<?php

namespace App\Support;

use App\Models\MikpayRouter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scope query MikpayRouter ke tenant (user) login — defense in depth
 * selain global scope MikpayRouter (super_admin tidak dibatasi oleh global scope).
 */
final class TenantRouterScope
{
    public static function forUser(int $userId): Builder
    {
        return MikpayRouter::withoutGlobalScopes()->where('user_id', $userId);
    }
}
