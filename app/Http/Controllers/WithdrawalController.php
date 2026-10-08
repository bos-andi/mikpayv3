<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\TenantWithdrawal;
use App\Services\TenantPayoutService;
use App\Support\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WithdrawalController extends Controller
{
    public function __construct(private TenantPayoutService $payouts) {}

    public function index(): View
    {
        $tenant = $this->tenant();

        return view('panel.finance-withdrawals', [
            'tenant' => $tenant,
            'balance' => $this->payouts->balance($tenant),
            'account' => $this->payouts->currentAccount($tenant),
            'banks' => $this->payouts->banks(),
            'settings' => $this->payouts->settings(),
            'usesPlatform' => PaymentGateway::active($tenant) === PaymentGateway::ACTIVE_PLATFORM,
            'canManage' => $this->canManage(),
            'withdrawals' => TenantWithdrawal::query()
                ->where('tenant_id', $tenant->id)
                ->with('user')
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function saveBank(Request $request): RedirectResponse
    {
        $this->authorizeManage();
        $tenant = $this->tenant();

        $data = $request->validate([
            'bank_code' => ['required', 'string', Rule::in(array_keys($this->payouts->banks()))],
            'account_number' => ['required', 'regex:/^[0-9]{5,20}$/'],
            'account_holder' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\s\.\,\'\-]+$/u'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'current_password' => ['required', 'current_password'],
        ], [
            'account_number.regex' => 'Nomor rekening hanya angka (5–20 digit).',
            'account_holder.regex' => 'Nama pemilik rekening hanya boleh huruf.',
            'proof.mimes' => 'Foto buku tabungan harus JPG, PNG, WEBP, atau PDF.',
            'proof.max' => 'Ukuran file maksimal 5 MB.',
            'current_password.current_password' => 'Password akun salah.',
        ], [
            'bank_code' => 'Bank',
            'account_number' => 'Nomor rekening',
            'account_holder' => 'Nama pemilik rekening',
            'proof' => 'Foto buku tabungan',
            'current_password' => 'Password akun',
        ]);

        $this->payouts->submitAccount(
            $tenant,
            $request->user(),
            $data['bank_code'],
            $data['account_number'],
            strtoupper(trim(preg_replace('/\s+/', ' ', $data['account_holder']))),
            $request->file('proof')
        );

        return back()->with('success', 'Rekening & foto buku tabungan terkirim. Admin MikPay akan memverifikasi sebelum penarikan dapat diajukan.');
    }

    public function proof(TenantPayoutAccount $account): StreamedResponse
    {
        abort_unless((int) $account->tenant_id === (int) $this->tenant()->id, 404);
        abort_unless(Storage::disk('local')->exists($account->proof_path), 404);

        return Storage::disk('local')->response($account->proof_path);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();
        $tenant = $this->tenant();

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], [
            'amount' => 'Nominal',
            'note' => 'Catatan',
        ]);

        $result = $this->payouts->requestWithdrawal($tenant, $request->user(), (int) $data['amount'], $data['note'] ?? null);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function cancel(Request $request, TenantWithdrawal $withdrawal): RedirectResponse
    {
        $this->authorizeManage();
        abort_unless((int) $withdrawal->tenant_id === (int) $this->tenant()->id, 404);

        $result = $this->payouts->cancel($withdrawal, $request->user());

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function tenant(): Tenant
    {
        $tenant = current_tenant();
        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user && ($user->isTenantAdmin() || $user->isSuperAdmin());
    }

    private function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Hanya admin tenant yang dapat mengelola penarikan dana.');
    }
}
