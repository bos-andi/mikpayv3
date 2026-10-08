<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnerCommission;
use App\Models\PartnerWithdrawal;
use App\Models\Tenant;
use App\Services\PartnerProgramService;
use App\Services\SubscriptionPackageService;
use App\Services\TenantPayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartnerPanelController extends Controller
{
    public function __construct(private PartnerProgramService $partners) {}

    public function dashboard(): View
    {
        $partner = $this->partner();

        return view('partner.dashboard', [
            'partner' => $partner,
            'settings' => $this->partners->settings(),
            'rates' => $this->partners->ratesFor($partner),
            'stats' => $this->partners->stats($partner),
            'balance' => $this->partners->balance($partner),
            'nextPayoutDate' => $this->partners->nextPayoutDate(),
            'packages' => SubscriptionPackageService::all(),
            'recentReferrals' => Tenant::query()
                ->where('referred_by_partner_id', $partner->id)
                ->latest()
                ->limit(5)
                ->get(),
            'recentCommissions' => PartnerCommission::query()
                ->where('partner_id', $partner->id)
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function referrals(): View
    {
        $partner = $this->partner();
        $tenants = Tenant::query()
            ->where('referred_by_partner_id', $partner->id)
            ->latest()
            ->paginate(25);

        $commissionByTenant = PartnerCommission::query()
            ->where('partner_id', $partner->id)
            ->where('status', PartnerCommission::STATUS_EARNED)
            ->whereIn('tenant_id', $tenants->pluck('id'))
            ->selectRaw('tenant_id, SUM(amount) as total, COUNT(*) as payments')
            ->groupBy('tenant_id')
            ->get()
            ->keyBy('tenant_id');

        return view('partner.referrals', [
            'partner' => $partner,
            'stats' => $this->partners->stats($partner),
            'tenants' => $tenants,
            'commissionByTenant' => $commissionByTenant,
        ]);
    }

    public function commissions(): View
    {
        $partner = $this->partner();

        return view('partner.commissions', [
            'partner' => $partner,
            'balance' => $this->partners->balance($partner),
            'settings' => $this->partners->settings(),
            'rates' => $this->partners->ratesFor($partner),
            'commissions' => PartnerCommission::query()
                ->where('partner_id', $partner->id)
                ->latest()
                ->paginate(25),
        ]);
    }

    public function withdrawals(TenantPayoutService $payouts): View
    {
        $partner = $this->partner();

        return view('partner.withdrawals', [
            'partner' => $partner,
            'balance' => $this->partners->balance($partner),
            'settings' => $this->partners->settings(),
            'banks' => $payouts->banks(),
            'nextPayoutDate' => $this->partners->nextPayoutDate(),
            'withdrawals' => PartnerWithdrawal::query()
                ->where('partner_id', $partner->id)
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function saveBank(Request $request, TenantPayoutService $payouts): RedirectResponse
    {
        $partner = $this->partner();
        $banks = $payouts->banks();

        $data = $request->validate([
            'bank_code' => ['required', 'string', Rule::in(array_keys($banks))],
            'account_number' => ['required', 'regex:/^[0-9]{5,20}$/'],
            'account_holder' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\s\.\,\'\-]+$/u'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'ktp' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'current_password' => ['required', 'current_password'],
        ], [
            'account_number.regex' => 'Nomor rekening hanya angka (5–20 digit).',
            'account_holder.regex' => 'Nama pemilik rekening hanya boleh huruf.',
            'proof.required' => 'Foto buku tabungan wajib diunggah.',
            'proof.mimes' => 'Foto buku tabungan harus JPG, PNG, WEBP, atau PDF.',
            'proof.max' => 'Ukuran foto buku tabungan maksimal 5 MB.',
            'ktp.required' => 'Foto KTP wajib diunggah.',
            'ktp.mimes' => 'Foto KTP harus JPG, PNG, WEBP, atau PDF.',
            'ktp.max' => 'Ukuran foto KTP maksimal 5 MB.',
            'current_password.current_password' => 'Password akun salah.',
        ], [
            'bank_code' => 'Bank',
            'account_number' => 'Nomor rekening',
            'account_holder' => 'Nama pemilik rekening',
            'proof' => 'Foto buku tabungan',
            'ktp' => 'Foto KTP',
            'current_password' => 'Password akun',
        ]);

        $this->partners->submitBank(
            $partner,
            $data['bank_code'],
            $banks[$data['bank_code']] ?? $data['bank_code'],
            $data['account_number'],
            strtoupper(trim(preg_replace('/\s+/', ' ', $data['account_holder']))),
            $request->file('proof'),
            $request->file('ktp')
        );

        return back()->with('success', 'Rekening & KTP terkirim. Admin MikPay akan memverifikasi sebelum Anda bisa menarik komisi.');
    }

    public function bankProof(): StreamedResponse
    {
        $partner = $this->partner();
        abort_unless($partner->bank_proof_path && Storage::disk('local')->exists($partner->bank_proof_path), 404);

        return Storage::disk('local')->response($partner->bank_proof_path);
    }

    public function ktp(): StreamedResponse
    {
        $partner = $this->partner();
        abort_unless($partner->ktp_path && Storage::disk('local')->exists($partner->ktp_path), 404);

        return Storage::disk('local')->response($partner->ktp_path);
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['amount' => 'Nominal', 'note' => 'Catatan']);

        $result = $this->partners->requestWithdrawal($this->partner(), (int) $data['amount'], $data['note'] ?? null);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function cancelWithdrawal(PartnerWithdrawal $withdrawal): RedirectResponse
    {
        abort_unless((int) $withdrawal->partner_id === (int) $this->partner()->id, 404);

        $result = $this->partners->cancelWithdrawal($withdrawal);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function partner(): Partner
    {
        return auth()->user()->partner;
    }
}
