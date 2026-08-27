<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformPendingPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('superadmin.dashboard', [
            'tenantCount' => Tenant::query()->count(),
            'userCount' => User::query()->where('role', '!=', User::ROLE_SUPERADMIN)->count(),
            'activeTenantCount' => Tenant::query()->where('status', 'active')->count(),
            'paymentStats' => [
                'pending' => PlatformPendingPayment::query()->where('status', 'pending')->count(),
                'approved' => PlatformPendingPayment::query()->where('status', 'approved')->count(),
                'rejected' => PlatformPendingPayment::query()->where('status', 'rejected')->count(),
            ],
        ]);
    }
}
