<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnerCommission;
use App\Models\PartnerWithdrawal;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\PartnerApprovedNotification;
use App\Services\PartnerProgramService;
use App\Services\TenantPayoutService;
use App\Support\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartnersController extends Controller
{
    public function __construct(private PartnerProgramService $partners) {}

    public function index(Request $request, TenantPayoutService $payouts): View
    {
        $status = (string) $request->query('status', '');

        $list = Partner::query()
            ->with('user')
            ->withCount('tenants')
            ->withSum(['commissions as commission_total' => fn ($q) => $q->where('status', PartnerCommission::STATUS_EARNED)], 'amount')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
            ->latest()
            ->get();

        $earned = PartnerCommission::query()->where('status', PartnerCommission::STATUS_EARNED);

        return view('superadmin.partners', [
            'partnersList' => $list,
            'status' => $status,
            'settings' => $this->partners->settings(),
            'balances' => $list->mapWithKeys(fn (Partner $p) => [$p->id => $this->partners->balance($p)]),
            'pendingBanks' => Partner::query()->with('user')->where('bank_status', Partner::BANK_PENDING)->get(),
            'pendingWithdrawals' => $pendingWithdrawals = PartnerWithdrawal::query()->with('partner.user')->where('status', PartnerWithdrawal::STATUS_PENDING)->oldest()->get(),
            'processingWithdrawals' => PartnerWithdrawal::query()->with('partner.user')->where('status', PartnerWithdrawal::STATUS_PROCESSING)->oldest()->get(),
            'recentWithdrawals' => PartnerWithdrawal::query()->with('partner.user')->whereNotIn('status', [PartnerWithdrawal::STATUS_PENDING, PartnerWithdrawal::STATUS_PROCESSING])->latest()->limit(15)->get(),
            'xenditBalance' => $pendingWithdrawals->isNotEmpty() ? $payouts->platformXenditBalance() : null,
            'xenditReady' => PaymentGateway::platformXenditSecretKey() !== '',
            'nextPayoutDate' => $this->partners->nextPayoutDate(),
            'recentCommissions' => PartnerCommission::query()->with('partner.user')->latest()->limit(20)->get(),
            'summary' => [
                'active' => Partner::query()->where('status', Partner::STATUS_ACTIVE)->count(),
                'pending' => Partner::query()->where('status', Partner::STATUS_PENDING)->count(),
                'referrals' => Tenant::query()->whereNotNull('referred_by_partner_id')->count(),
                'commission_total' => (int) (clone $earned)->sum('amount'),
                'paid_out' => (int) PartnerWithdrawal::query()->where('status', PartnerWithdrawal::STATUS_COMPLETED)->sum('amount'),
                'owed' => (int) (clone $earned)->sum('amount')
                    - (int) PartnerWithdrawal::query()->whereIn('status', [PartnerWithdrawal::STATUS_COMPLETED])->sum('amount'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'password' => ['required', Password::min(8)],
            'code' => ['nullable', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
        ], [], ['name' => 'Nama', 'phone' => 'WhatsApp', 'city' => 'Kota', 'code' => 'Kode referral']);

        $code = filled($data['code'] ?? null) ? PartnerProgramService::normalizeCode($data['code']) : null;
        if ($code && Partner::query()->where('code', $code)->exists()) {
            return back()->withInput()->withErrors(['code' => "Kode {$code} sudah dipakai affiliate lain."]);
        }

        $partner = DB::transaction(function () use ($data, $code) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => User::ROLE_PARTNER,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $partner = Partner::query()->create([
                'user_id' => $user->id,
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'status' => Partner::STATUS_ACTIVE,
                'code' => $code ?? $this->partners->generateCode($data['name']),
            ]);
            $partner->forceFill(['approved_at' => now(), 'reviewed_by' => auth()->user()?->email])->save();

            return $partner;
        });

        return back()->with('success', "Affiliate {$data['name']} dibuat dengan kode {$partner->code}.");
    }

    public function approve(Request $request, Partner $partner): RedirectResponse
    {
        if (! in_array($partner->status, [Partner::STATUS_PENDING, Partner::STATUS_REJECTED], true)) {
            return back()->with('error', 'Affiliate ini sudah diproses.');
        }

        if (! $partner->user?->hasVerifiedEmail()) {
            return back()->with('error', 'Email affiliate ini belum diverifikasi. Minta yang bersangkutan klik tautan verifikasi di emailnya.');
        }

        $data = $request->validate([
            'code' => ['nullable', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
        ], [], ['code' => 'Kode referral']);

        $code = filled($data['code'] ?? null)
            ? PartnerProgramService::normalizeCode($data['code'])
            : ($partner->code ?: $this->partners->generateCode((string) $partner->user?->name));

        if (Partner::query()->where('code', $code)->whereKeyNot($partner->id)->exists()) {
            return back()->with('error', "Kode {$code} sudah dipakai affiliate lain.");
        }

        $partner->forceFill([
            'status' => Partner::STATUS_ACTIVE,
            'code' => $code,
            'reject_reason' => null,
            'approved_at' => now(),
            'reviewed_by' => auth()->user()?->email,
        ])->save();

        try {
            $partner->user?->notify(new PartnerApprovedNotification($partner));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', "Affiliate {$partner->user?->name} disetujui dengan kode {$code}.");
    }

    public function reject(Request $request, Partner $partner): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], [], ['reason' => 'Alasan']);

        if (! $partner->isPending()) {
            return back()->with('error', 'Hanya pendaftaran yang menunggu persetujuan yang bisa ditolak.');
        }

        $partner->forceFill([
            'status' => Partner::STATUS_REJECTED,
            'reject_reason' => $data['reason'],
            'reviewed_by' => auth()->user()?->email,
        ])->save();

        return back()->with('success', 'Pendaftaran affiliate ditolak.');
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'status' => ['required', Rule::in([Partner::STATUS_ACTIVE, Partner::STATUS_SUSPENDED])],
            'first_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'recurring_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'code' => 'Kode referral',
            'first_percent' => 'Komisi pertama',
            'recurring_percent' => 'Komisi perpanjangan',
        ]);

        $code = PartnerProgramService::normalizeCode($data['code']);
        if (Partner::query()->where('code', $code)->whereKeyNot($partner->id)->exists()) {
            return back()->with('error', "Kode {$code} sudah dipakai affiliate lain.");
        }

        if ($partner->isPending() || $partner->status === Partner::STATUS_REJECTED) {
            return back()->with('error', 'Setujui pendaftaran affiliate terlebih dahulu.');
        }

        $partner->forceFill([
            'code' => $code,
            'status' => $data['status'],
            'first_percent' => $data['first_percent'] !== null && $data['first_percent'] !== '' ? (float) $data['first_percent'] : null,
            'recurring_percent' => $data['recurring_percent'] !== null && $data['recurring_percent'] !== '' ? (float) $data['recurring_percent'] : null,
            'phone' => $data['phone'] ?? $partner->phone,
            'admin_note' => $data['admin_note'] ?? null,
        ])->save();

        return back()->with('success', "Data affiliate {$partner->user?->name} diperbarui.");
    }

    public function bankProof(Partner $partner): StreamedResponse
    {
        abort_unless($partner->bank_proof_path && Storage::disk('local')->exists($partner->bank_proof_path), 404);

        return Storage::disk('local')->response($partner->bank_proof_path);
    }

    public function ktp(Partner $partner): StreamedResponse
    {
        abort_unless($partner->ktp_path && Storage::disk('local')->exists($partner->ktp_path), 404);

        return Storage::disk('local')->response($partner->ktp_path);
    }

    public function verifyBank(Partner $partner): RedirectResponse
    {
        $result = $this->partners->reviewBank($partner, true);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function rejectBank(Request $request, Partner $partner): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], [], ['reason' => 'Alasan']);
        $result = $this->partners->reviewBank($partner, false, $data['reason']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function completeWithdrawal(Request $request, PartnerWithdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validate(['transfer_reference' => ['nullable', 'string', 'max:120']]);
        $result = $this->partners->completeWithdrawal($withdrawal, auth()->user()?->email ?: 'superadmin', $data['transfer_reference'] ?? null);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function sendXendit(PartnerWithdrawal $withdrawal): RedirectResponse
    {
        $result = $this->partners->approveViaXendit($withdrawal, auth()->user()?->email ?: 'superadmin');

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function sendAllXendit(): RedirectResponse
    {
        $result = $this->partners->sendAllPendingViaXendit(auth()->user()?->email ?: 'superadmin');

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function refreshWithdrawal(PartnerWithdrawal $withdrawal): RedirectResponse
    {
        $result = $this->partners->refreshXenditStatus($withdrawal);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function rejectWithdrawal(Request $request, PartnerWithdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], [], ['reason' => 'Alasan']);
        $result = $this->partners->rejectWithdrawal($withdrawal, auth()->user()?->email ?: 'superadmin', $data['reason']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function cancelCommission(Request $request, PartnerCommission $commission): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], [], ['reason' => 'Alasan']);
        $result = $this->partners->cancelCommission($commission, $data['reason']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'first_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'recurring_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'recurring_months' => ['required', 'integer', 'min:0', 'max:120'],
            'hold_days' => ['required', 'integer', 'min:0', 'max:60'],
            'min_withdrawal' => ['required', 'integer', 'min:10000', 'max:100000000'],
            'cookie_days' => ['required', 'integer', 'min:1', 'max:365'],
            'payout_day' => ['required', 'integer', 'min:0', 'max:28'],
            'referral_discount_percent' => ['required', 'numeric', 'min:0', 'max:90'],
        ]);

        $data['enabled'] = $request->boolean('enabled');
        $this->partners->saveSettings($data);

        return back()->with('success', 'Pengaturan Program Affiliate disimpan.');
    }
}
