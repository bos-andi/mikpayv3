<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('superadmin.dashboard', [
            'tenantCount' => Tenant::query()->count(),
            'userCount' => User::query()->count(),
            'activeTenantCount' => Tenant::query()->where('status', 'active')->count(),
        ]);
    }
}
