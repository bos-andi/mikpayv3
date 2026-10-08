<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\TenantPayoutAccount;
use App\Models\TenantWithdrawal;
use App\Models\VoucherOrder;
use App\Services\TenantPayoutService;
use App\Services\VoucherShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WithdrawalsController extends Controller
{
    public function __construct(private TenantPayoutService $payouts) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');

        $withdrawals = TenantWithdrawal::query()
            ->with(['tenant', 'user'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 ELSE 2 END")
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $balances = [];
        foreach ($withdrawals->getCollection()->pluck('tenant')->filter()->unique('id') as $tenant) {
            $balances[$tenant->id] = $this->payouts->balance($tenant);
        }

        $pendingAccounts = TenantPayoutAccount::query()
            ->with(['tenant', 'user'])
            ->where('status', TenantPayoutAccount::STATUS_PENDING)
            ->oldest()
            ->get();

        $recentAccounts = TenantPayoutAccount::query()
            ->with('tenant')
            ->where('status', '!=', TenantPayoutAccount::STATUS_PENDING)
            ->latest('reviewed_at')
            ->limit(10)
            ->get();

        $paidVouchers = VoucherOrder::query()->whereIn('status', VoucherOrder::PAID_STATUSES);

        $counts = TenantWithdrawal::query()
            ->selectRaw('status, COUNT(*) as c, SUM(amount) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('superadmin.withdrawals', [
            'withdrawals' => $withdrawals,
            'status' => $status,
            'counts' => $counts,
            'balances' => $balances,
            'pendingAccounts' => $pendingAccounts,
            'recentAccounts' => $recentAccounts,
            'settings' => $this->payouts->settings(),
            'xenditBalance' => $this->payouts->platformXenditBalance(),
            'voucherFee' => [
                'amount' => VoucherShopService::serviceFee(),
                'example_price' => 5000,
                'example_fee' => VoucherShopService::feeFor(5000),
                'today' => (int) (clone $paidVouchers)->where('paid_at', '>=', today())->sum('service_fee'),
                'month' => (int) (clone $paidVouchers)->where('paid_at', '>=', now()->startOfMonth())->sum('service_fee'),
                'total' => (int) (clone $paidVouchers)->sum('service_fee'),
                'count' => (clone $paidVouchers)->count(),
            ],
        ]);
    }

    public function updateVoucherFee(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'voucher_service_fee' => ['required', 'integer', 'min:0', 'max:100000'],
        ], [], ['voucher_service_fee' => 'Biaya layanan voucher']);

        VoucherShopService::saveServiceFee((int) $data['voucher_service_fee']);

        return back()->with('success', 'Biaya layanan toko voucher disimpan: Rp '.number_format((int) $data['voucher_service_fee'], 0, ',', '.').' per transaksi.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'min_amount' => ['required', 'integer', 'min:10000', 'max:100000000'],
            'fee_flat' => ['required', 'integer', 'min:0', 'max:1000000'],
            'fee_percent' => ['required', 'numeric', 'min:0', 'max:20'],
            'hold_days' => ['required', 'integer', 'min:0', 'max:30'],
        ], [], [
            'min_amount' => 'Minimal penarikan',
            'fee_flat' => 'Biaya tetap',
            'fee_percent' => 'Biaya persen',
            'hold_days' => 'Masa tahan',
        ]);
        $data['enabled'] = $request->boolean('enabled');

        $this->payouts->saveSettings($data);

        return back()->with('success', 'Pengaturan penarikan dana disimpan.');
    }

    public function approveXendit(TenantWithdrawal $withdrawal): RedirectResponse
    {
        $result = $this->payouts->approveViaXendit($withdrawal, $this->approver());

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function markManual(Request $request, TenantWithdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validate([
            'transfer_reference' => ['nullable', 'string', 'max:150'],
        ]);

        $result = $this->payouts->markManual($withdrawal, $this->approver(), $data['transfer_reference'] ?? null);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function reject(Request $request, TenantWithdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [], ['reason' => 'Alasan penolakan']);

        $result = $this->payouts->reject($withdrawal, $this->approver(), $data['reason']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function verifyAccount(TenantPayoutAccount $account): RedirectResponse
    {
        $result = $this->payouts->verifyAccount($account, $this->approver());

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function rejectAccount(Request $request, TenantPayoutAccount $account): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [], ['reason' => 'Alasan penolakan']);

        $result = $this->payouts->rejectAccount($account, $this->approver(), $data['reason']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function accountProof(TenantPayoutAccount $account): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($account->proof_path), 404);

        return Storage::disk('local')->response($account->proof_path);
    }

    public function refresh(TenantWithdrawal $withdrawal): RedirectResponse
    {
        $result = $this->payouts->refreshStatus($withdrawal);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function approver(): string
    {
        return auth()->user()?->email ?: 'superadmin';
    }
}
