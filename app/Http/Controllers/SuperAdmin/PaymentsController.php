<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformPendingPayment;
use App\Services\SubscriptionPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function approve(PlatformPendingPayment $payment): RedirectResponse
    {
        if (! $payment->isPending()) {
            return back()->with('error', 'Pembayaran ini sudah diproses.');
        }

        $pkg = SubscriptionPackageService::get((string) $payment->package);
        if (! $pkg) {
            return back()->with('error', 'Paket tidak dikenali.');
        }

        $tenant = $payment->tenant;
        if (! $tenant) {
            return back()->with('error', 'Tenant untuk pembayaran ini tidak valid.');
        }

        $approver = auth()->user()?->email ?: 'superadmin';

        DB::transaction(function () use ($payment, $pkg, $tenant, $approver) {
            $tenant->extendPlatformSubscription(
                (string) $payment->package,
                (int) ($pkg['duration'] ?? 30)
            );

            $payment->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approver,
            ])->save();
        });

        return back()->with('success', "Pembayaran {$payment->payment_id} disetujui. Langganan {$tenant->name} diperpanjang.");
    }

    public function reject(Request $request, PlatformPendingPayment $payment): RedirectResponse
    {
        if (! $payment->isPending()) {
            return back()->with('error', 'Pembayaran ini sudah diproses.');
        }

        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'max:1000'],
        ], [], [
            'reject_reason' => 'Alasan penolakan',
        ]);

        $payment->forceFill([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->user()?->email ?: 'superadmin',
            'reject_reason' => $data['reject_reason'],
        ])->save();

        return back()->with('success', "Pembayaran {$payment->payment_id} ditolak.");
    }
}
