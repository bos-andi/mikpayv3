<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformPendingPayment;
use App\Services\SubscriptionPackageService;
use Illuminate\View\View;

class PaymentsController extends Controller
{
    public function index(): View
    {
        $payments = PlatformPendingPayment::query()
            ->with(['tenant', 'user'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get();

        return view('superadmin.payments', [
            'payments' => $payments,
            'packages' => SubscriptionPackageService::all(),
            'stats' => [
                'pending' => PlatformPendingPayment::query()->where('status', 'pending')->count(),
                'approved' => PlatformPendingPayment::query()->where('status', 'approved')->count(),
                'rejected' => PlatformPendingPayment::query()->where('status', 'rejected')->count(),
            ],
        ]);
    }
}
