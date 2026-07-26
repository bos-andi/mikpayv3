<?php

namespace App\Http\Controllers;

use App\Models\PendingPayment;
use App\Models\Subscription;
use App\Models\SubscriptionPaymentHistory;
use App\Models\User;
use App\Services\SubscriptionPackageService;
use App\Support\SuperAdminCredentials;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminPaymentController extends Controller
{
    private function redirectIfProtectedUser(User $user): ?\Illuminate\Http\RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.users')->with('error', 'Akun super admin tidak dapat diubah dari sini.');
        }

        return null;
    }

    public function dashboard()
    {
        $stats = [
            'pending' => PendingPayment::where('status', 'pending')->count(),
            'approved' => PendingPayment::where('status', 'approved')->count(),
            'rejected' => PendingPayment::where('status', 'rejected')->count(),
        ];

        $tenantUserCount = User::query()->where('is_super_admin', false)->count();

        return view('superadmin.dashboard', [
            'stats' => $stats,
            'tenantUserCount' => $tenantUserCount,
        ]);
    }

    public function payments()
    {
        $payments = PendingPayment::with('user')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get();

        return view('superadmin.payments', [
            'payments' => $payments,
            'packages' => SubscriptionPackageService::all(),
        ]);
    }

    public function users()
    {
        $users = User::query()
            ->where('is_super_admin', false)
            ->with('mikpayRouters')
            ->orderBy('username')
            ->get();

        return view('superadmin.users', [
            'users' => $users,
        ]);
    }

    public function approve(Request $request, PendingPayment $payment)
    {
        if ($payment->status !== 'pending') {
            return redirect()->route('superadmin.payments')->with('error', 'Pembayaran ini sudah diproses.');
        }

        $packages = SubscriptionPackageService::all();
        $pkg = $packages[$payment->package] ?? null;
        if (! $pkg) {
            return redirect()->route('superadmin.payments')->with('error', 'Paket tidak dikenali.');
        }

        $approver = SuperAdminCredentials::actorName();

        $tenantUser = User::query()->whereKey((int) $payment->user_id)->first();
        if (! $tenantUser || $tenantUser->isSuperAdmin()) {
            return redirect()->route('superadmin.payments')->with('error', 'Pengguna untuk pembayaran ini tidak valid.');
        }

        DB::transaction(function () use ($payment, $pkg, $approver, $tenantUser) {
            // Baris global (legacy / riwayat FK) — tetap di-update agar subscription_payment_history valid.
            $subscription = Subscription::getGlobal();

            $now = Carbon::now();
            $endDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : null;
            $baseDate = ($endDate && $endDate->isFuture()) ? $endDate : $now;

            $subscription->status = 'active';
            $subscription->package = $payment->package;
            $subscription->start_date = $now->toDateString();
            $subscription->end_date = $baseDate->copy()->addDays((int) $pkg['duration'])->toDateString();
            $subscription->save();

            // Yang dipakai tenant: kolom di users (halaman Langganan, SubscriptionGate, layout).
            $userEnd = $tenantUser->subscription_end ? Carbon::parse($tenantUser->subscription_end) : null;
            $userBase = ($userEnd && $userEnd->isFuture()) ? $userEnd : $now;
            $tenantUser->subscription_package = $payment->package;
            $tenantUser->subscription_status = 'active';
            $tenantUser->subscription_start = $now->toDateString();
            $tenantUser->subscription_end = $userBase->copy()->addDays((int) $pkg['duration'])->toDateString();
            $tenantUser->save();

            SubscriptionPaymentHistory::create([
                'subscription_id' => $subscription->id,
                'date' => $now,
                'package' => $payment->package,
                'amount' => (int) $payment->amount,
                'transaction_id' => $payment->payment_id,
            ]);

            $payment->status = 'approved';
            $payment->approved_at = $now;
            $payment->approved_by = $approver;
            $payment->save();
        });

        return redirect()->route('superadmin.payments')->with('success', 'Pembayaran berhasil di-approve.');
    }

    public function reject(Request $request, PendingPayment $payment)
    {
        if ($payment->status !== 'pending') {
            return redirect()->route('superadmin.payments')->with('error', 'Pembayaran ini sudah diproses.');
        }

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:1000'],
        ]);

        $payment->status = 'rejected';
        $payment->rejected_at = now();
        $payment->rejected_by = SuperAdminCredentials::actorName();
        $payment->reject_reason = $validated['reject_reason'];
        $payment->save();

        return redirect()->route('superadmin.payments')->with('success', 'Pembayaran ditolak.');
    }

    public function expireSubscription(User $user)
    {
        if ($r = $this->redirectIfProtectedUser($user)) {
            return $r;
        }

        $yesterday = Carbon::yesterday()->toDateString();
        $user->subscription_status = 'expired';
        $user->subscription_end = $yesterday;
        if ($user->subscription_start === null) {
            $user->subscription_start = Carbon::today()->toDateString();
        }
        $user->save();

        return redirect()->route('superadmin.users')
            ->with('success', "Langganan user {$user->username} ditandai habis (expired).");
    }

    public function deactivateUser(User $user)
    {
        if ($r = $this->redirectIfProtectedUser($user)) {
            return $r;
        }

        $user->status = 'inactive';
        $user->save();

        return redirect()->route('superadmin.users')
            ->with('success', "Akun {$user->username} dinonaktifkan (tidak bisa login).");
    }

    public function activateUser(User $user)
    {
        if ($r = $this->redirectIfProtectedUser($user)) {
            return $r;
        }

        $user->status = 'active';
        $user->save();

        return redirect()->route('superadmin.users')
            ->with('success', "Akun {$user->username} diaktifkan kembali.");
    }

    public function setTrial(User $user)
    {
        if ($r = $this->redirectIfProtectedUser($user)) {
            return $r;
        }

        $start = Carbon::today();
        $end = $start->copy()->addDays(5);

        $user->subscription_package = 'trial_5_days';
        $user->subscription_status = 'trial';
        $user->subscription_start = $start->toDateString();
        $user->subscription_end = $end->toDateString();
        $user->status = 'active';
        $user->save();

        return redirect()->route('superadmin.users')
            ->with('success', "User {$user->username} berhasil diset trial 5 hari (sampai {$end->format('d M Y')}).");
    }
}

