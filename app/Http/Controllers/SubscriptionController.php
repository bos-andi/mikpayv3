<?php

namespace App\Http\Controllers;

use App\Models\PlatformPendingPayment;
use App\Services\PartnerProgramService;
use App\Services\PlatformWalletService;
use App\Services\SubscriptionPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly PlatformWalletService $wallet,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant) {
            return redirect()->route('panel.dashboard')->with('error', 'Tenant tidak ditemukan.');
        }

        $packages = SubscriptionPackageService::all();
        $end = $tenant->subscriptionEndsAt();
        $now = wib_now();
        $remainingDays = null;
        if ($end) {
            $secs = $now->diffInSeconds($end, false);
            $remainingDays = $secs > 0 ? (int) ceil($secs / 86400) : 0;
        }

        $transactions = PlatformPendingPayment::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $pendingDeposit = $transactions->first(fn (PlatformPendingPayment $p) => $p->isPending() && $p->isDeposit());

        $status = (string) $request->query('status', '');
        if (in_array($status, ['deposit_success', 'success'], true) && $pendingDeposit?->isOnlineGateway()) {
            $pendingDeposit->refresh();
            $tenant->refresh();
        }

        return view('panel.subscription.index', [
            'tenant' => $tenant,
            'packages' => $packages,
            'features' => SubscriptionPackageService::sharedFeatures(),
            'planLabel' => SubscriptionPackageService::planLabel($tenant->plan ?: 'trial'),
            'balance' => $tenant->platformBalance(),
            'depositPresets' => config('mikpay.subscription.deposit_presets', [50000, 100000, 199000, 399000]),
            'depositMin' => (int) config('mikpay.subscription.deposit_min', 10000),
            'depositReady' => $this->wallet->depositReady(),
            'isActive' => $tenant->subscriptionIsActive(),
            'needsRenewal' => $tenant->needsRenewal(),
            'remainingDays' => $remainingDays,
            'subscriptionUntil' => format_wib($end),
            'transactions' => $transactions,
            'pendingDeposit' => $pendingDeposit,
            'returnStatus' => $status,
            'referralDiscount' => app(PartnerProgramService::class)->firstPaymentDiscountFor($tenant),
            'autoRenew' => $this->wallet->autoRenewSettings($tenant),
            'autoRenewAt' => $this->wallet->autoRenewAt($tenant),
            'autoRenewHours' => $this->wallet->autoRenewWindowHours(),
        ]);
    }

    public function autoRenew(Request $request): RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'package' => ['nullable', 'string', Rule::in(array_keys(SubscriptionPackageService::all()))],
        ]);

        $before = $this->wallet->autoRenewSettings($tenant);
        $enabled = (bool) $data['enabled'];
        $package = $data['package'] ?? $before['package'];
        $this->wallet->setAutoRenew($tenant, $enabled, $package);

        $message = match (true) {
            $package !== $before['package'] => 'Paket perpanjang otomatis diganti ke '.(SubscriptionPackageService::get($package)['name'] ?? $package).'.',
            ! $enabled => 'Perpanjang otomatis dimatikan.',
            default => 'Perpanjang otomatis aktif.',
        };
        if (! $enabled) {
            return back()->with('success', $message);
        }

        $result = $this->wallet->attemptAutoRenew($tenant->fresh());
        if ($result !== null) {
            return back()->with($result['ok'] ? 'success' : 'error', $message.' '.$result['message']);
        }

        return back()->with('success', $message.' Paket diperpanjang dari saldo menjelang masa aktif habis.');
    }

    public function deposit(Request $request): RedirectResponse
    {
        $tenant = current_tenant();
        $user = $request->user();
        if (! $tenant || ! $user) {
            return back()->with('error', 'Sesi tidak valid.');
        }

        $min = (int) config('mikpay.subscription.deposit_min', 10000);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:'.$min, 'max:50000000'],
        ], [], [
            'amount' => 'Nominal isi saldo',
        ]);

        $result = $this->wallet->startXenditDeposit($tenant, $user, (int) $data['amount']);

        if (! empty($result['invoice_url'])) {
            return redirect()->away($result['invoice_url']);
        }

        return back()->with('error', $result['message'] ?? 'Gagal memproses isi saldo.');
    }

    public function selectPackage(Request $request): RedirectResponse
    {
        $tenant = current_tenant();
        $user = $request->user();
        if (! $tenant || ! $user) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $packages = SubscriptionPackageService::all();
        $data = $request->validate([
            'package' => ['required', 'string', Rule::in(array_keys($packages))],
        ]);

        $result = $this->wallet->payPackageFromBalance($tenant, $user, $data['package'], $packages[$data['package']]);

        return back()->with(
            ($result['ok'] ?? false) ? 'success' : 'error',
            $result['message'] ?? 'Gagal memperpanjang langganan.'
        );
    }

    public function cancelPending(Request $request): RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $pending = PlatformPendingPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('purpose', PlatformPendingPayment::PURPOSE_DEPOSIT)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->first();

        if (! $pending) {
            return back()->with('error', 'Tidak ada deposit pending.');
        }

        $this->wallet->cancelPendingDeposit($pending);

        return back()->with('success', 'Deposit pending dibatalkan.');
    }
}
