<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformPendingPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformStatusService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Kanal yang berarti uang masuk lewat payment gateway (bukan potong saldo / input manual). */
    private const GATEWAY_CHANNELS = ['xendit', 'andidev_pay'];

    public function __invoke(Request $request, PlatformStatusService $status): View
    {
        $monthStart = wib_now()->startOfMonth();
        $gatewayPaid = PlatformPendingPayment::query()
            ->where('status', 'approved')
            ->whereIn('payment_channel', self::GATEWAY_CHANNELS);

        return view('superadmin.dashboard', [
            'tenantCount' => Tenant::query()->count(),
            'userCount' => User::query()->where('role', '!=', User::ROLE_SUPERADMIN)->count(),
            'activeTenantCount' => Tenant::query()->where('status', 'active')->count(),
            'newTenantsThisMonth' => Tenant::query()->where('created_at', '>=', $monthStart)->count(),
            'paymentStats' => [
                'pending' => PlatformPendingPayment::query()->where('status', 'pending')->count(),
                'approved' => PlatformPendingPayment::query()->where('status', 'approved')->count(),
                'rejected' => PlatformPendingPayment::query()->where('status', 'rejected')->count(),
            ],
            'revenue' => [
                'month' => (int) (clone $gatewayPaid)->where('approved_at', '>=', $monthStart)->sum('amount'),
                'month_count' => (clone $gatewayPaid)->where('approved_at', '>=', $monthStart)->count(),
                'total' => (int) (clone $gatewayPaid)->sum('amount'),
            ],
            'recentPayments' => PlatformPendingPayment::query()
                ->with('tenant:id,name,subdomain')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),
            'status' => $status->snapshot($request->boolean('refresh')),
        ]);
    }
}
