<?php

namespace App\Services;

use App\Models\PlatformPendingPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlatformWalletService
{
    public function __construct(
        private readonly XenditService $xendit,
        private readonly AndidevPayService $andidev,
    ) {
    }

    public function depositReady(): bool
    {
        return PaymentGateway::platformPaymentReady();
    }

    /** @deprecated Gunakan depositReady() */
    public function xenditReady(): bool
    {
        return $this->depositReady();
    }

    /**
     * @return array{ok: bool, message: string, payment?: PlatformPendingPayment, invoice_url?: string}
     */
    public function startXenditDeposit(Tenant $tenant, User $user, int $amount): array
    {
        if (! $this->depositReady()) {
            return ['ok' => false, 'message' => 'Pembayaran online belum tersedia. Hubungi admin MikPay.'];
        }

        $min = (int) config('mikpay.subscription.deposit_min', 10000);
        if ($amount < $min) {
            return ['ok' => false, 'message' => 'Minimal isi saldo Rp '.number_format($min, 0, ',', '.').'.'];
        }

        $existing = PlatformPendingPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('purpose', PlatformPendingPayment::PURPOSE_DEPOSIT)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            if ($existing->amount === $amount && filled($existing->xendit_invoice_url)) {
                return [
                    'ok' => true,
                    'message' => 'Lanjutkan pembayaran deposit yang sudah dibuat.',
                    'payment' => $existing,
                    'invoice_url' => $existing->xendit_invoice_url,
                ];
            }

            return [
                'ok' => false,
                'message' => 'Masih ada deposit pending. Selesaikan atau batalkan dulu.',
                'payment' => $existing,
                'invoice_url' => $existing->xendit_invoice_url ?: null,
            ];
        }

        do {
            $paymentId = 'MPD'.strtoupper(bin2hex(random_bytes(4))).now()->format('ymd');
        } while (PlatformPendingPayment::query()->where('payment_id', $paymentId)->exists());

        $provider = PaymentGateway::platformProvider();

        $payment = PlatformPendingPayment::query()->create([
            'payment_id' => $paymentId,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'package' => 'deposit',
            'purpose' => PlatformPendingPayment::PURPOSE_DEPOSIT,
            'payment_channel' => $provider,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        $options = [
            'success_url' => panel_route('subscription').'?status=deposit_success&pid='.$payment->payment_id,
            'failure_url' => panel_route('subscription').'?status=deposit_failed&pid='.$payment->payment_id,
        ];
        $invoice = $provider === PaymentGateway::PROVIDER_ANDIDEV_PAY
            ? $this->andidev->createPlatformDepositInvoice($tenant, $payment, $options)
            : $this->xendit->createPlatformDepositInvoice($tenant, $payment, $options);

        if (! ($invoice['ok'] ?? false)) {
            $payment->forceFill([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => 'system',
                'reject_reason' => 'Gagal membuat link pembayaran: '.($invoice['message'] ?? 'unknown'),
            ])->save();

            return [
                'ok' => false,
                'message' => (string) ($invoice['message'] ?? 'Gagal memproses isi saldo.'),
            ];
        }

        return [
            'ok' => true,
            'message' => 'Link pembayaran siap.',
            'payment' => $payment->fresh(),
            'invoice_url' => (string) $invoice['invoice_url'],
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function markDepositPaidFromXendit(PlatformPendingPayment $payment, array $payload = [], string $approvedBy = PaymentGateway::PROVIDER_XENDIT): array
    {
        if ($payment->status === 'approved') {
            return ['ok' => true, 'message' => 'Deposit sudah diproses sebelumnya.'];
        }

        if (! $payment->isPending() || ! $payment->isDeposit()) {
            return ['ok' => false, 'message' => 'Deposit tidak valid atau sudah diproses.'];
        }

        $tenant = $payment->tenant;
        if (! $tenant) {
            return ['ok' => false, 'message' => 'Tenant tidak ditemukan.'];
        }

        DB::transaction(function () use ($payment, $tenant, $payload, $approvedBy) {
            $tenant->addPlatformBalance((int) $payment->amount, 'Isi saldo online '.$payment->payment_id);

            $payment->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
                'xendit_invoice_id' => $payload['id'] ?? $payment->xendit_invoice_id,
            ])->save();
        });

        Log::info('platform.wallet.deposit_paid', [
            'payment_id' => $payment->payment_id,
            'tenant_id' => $tenant->id,
            'amount' => $payment->amount,
        ]);

        try {
            $this->attemptAutoRenew($tenant->fresh());
        } catch (\Throwable $e) {
            Log::warning('platform.wallet.auto_renew_after_deposit_failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }

        return ['ok' => true, 'message' => 'Saldo berhasil ditambahkan.'];
    }

    /**
     * Pengaturan perpanjang otomatis tenant (disimpan di meta).
     *
     * @return array{enabled: bool, package: string, last: array<string, mixed>|null}
     */
    public function autoRenewSettings(Tenant $tenant): array
    {
        $packages = SubscriptionPackageService::all();
        $package = (string) $tenant->metaGet('auto_renew.package', '');
        $last = $tenant->metaGet('auto_renew.last');

        return [
            'enabled' => (bool) $tenant->metaGet('auto_renew.enabled', false),
            'package' => match (true) {
                isset($packages[$package]) => $package,
                isset($packages[(string) $tenant->plan]) => (string) $tenant->plan,
                default => (string) array_key_first($packages),
            },
            'last' => is_array($last) ? $last : null,
        ];
    }

    public function setAutoRenew(Tenant $tenant, bool $enabled, string $package): void
    {
        $tenant->metaSet('auto_renew.enabled', $enabled);
        $tenant->metaSet('auto_renew.package', $package);
        $tenant->save();
    }

    /** Waktu perpanjang otomatis dicoba: N jam sebelum masa aktif habis (lihat mikpay.subscription.auto_renew_hours). */
    public function autoRenewAt(Tenant $tenant): ?\Carbon\Carbon
    {
        return $tenant->subscriptionEndsAt()?->copy()->subHours($this->autoRenewWindowHours());
    }

    public function autoRenewWindowHours(): int
    {
        return max(1, (int) config('mikpay.subscription.auto_renew_hours', 24));
    }

    /**
     * Perpanjang dari saldo bila perpanjang otomatis aktif dan masa aktif sudah masuk jendela perpanjangan.
     *
     * @return array{ok: bool, message: string}|null Null bila belum waktunya / tidak berlaku.
     */
    public function attemptAutoRenew(Tenant $tenant, bool $dryRun = false): ?array
    {
        $settings = $this->autoRenewSettings($tenant);
        if (! $settings['enabled']
            || ($tenant->plan ?? '') === 'tunnel'
            || $tenant->status === 'inactive'
            || $tenant->awaitingEmailVerification()) {
            return null;
        }

        $renewAt = $this->autoRenewAt($tenant);
        if ($renewAt === null || $renewAt->greaterThan(wib_now())) {
            return null;
        }

        $package = SubscriptionPackageService::get($settings['package']);
        $owner = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', User::ROLE_TENANT_ADMIN)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
        if (! $package || ! $owner) {
            return null;
        }

        if ($dryRun) {
            return ['ok' => true, 'message' => 'Akan diperpanjang '.$package['name'].' dari saldo.'];
        }

        $result = $this->payPackageFromBalance($tenant, $owner, $settings['package'], $package, true);

        $tenant->refresh();
        $tenant->metaSet('auto_renew.last', [
            'at' => wib_now()->toIso8601String(),
            'ok' => (bool) $result['ok'],
            'message' => (string) $result['message'],
        ]);
        $tenant->save();

        if ($result['ok']) {
            Log::info('platform.wallet.auto_renewed', ['tenant_id' => $tenant->id, 'package' => $settings['package']]);
        }

        return $result;
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function payPackageFromBalance(Tenant $tenant, User $user, string $packageKey, array $package, bool $automatic = false): array
    {
        $normalPrice = (int) ($package['price'] ?? 0);
        if ($normalPrice < 1) {
            return ['ok' => false, 'message' => 'Harga paket tidak valid.'];
        }

        $partners = app(PartnerProgramService::class);
        $price = PartnerProgramService::applyDiscount($normalPrice, $partners->firstPaymentDiscountFor($tenant));

        if ($tenant->platformBalance() < $price) {
            $short = $price - $tenant->platformBalance();

            return [
                'ok' => false,
                'message' => 'Saldo tidak cukup. Kurang Rp '.number_format(max(0, $short), 0, ',', '.').'. Isi saldo terlebih dahulu.',
            ];
        }

        [$payment, $bonusUsed, $price, $discountPercent] = DB::transaction(function () use ($tenant, $user, $packageKey, $package, $normalPrice, $partners, $automatic) {
            Tenant::query()->whereKey($tenant->id)->lockForUpdate()->first();
            $tenant->refresh();

            $discountPercent = $partners->firstPaymentDiscountFor($tenant);
            $price = PartnerProgramService::applyDiscount($normalPrice, $discountPercent);

            $bonusUsed = $tenant->consumeBonusPlatformBalance($price);
            if (! $tenant->deductPlatformBalance($price)) {
                throw new \RuntimeException('Saldo tidak cukup.');
            }

            $tenant->extendPlatformSubscription($packageKey, (int) ($package['duration'] ?? 30));

            do {
                $paymentId = 'MPS'.strtoupper(bin2hex(random_bytes(4))).now()->format('ymd');
            } while (PlatformPendingPayment::query()->where('payment_id', $paymentId)->exists());

            $payment = PlatformPendingPayment::query()->create([
                'payment_id' => $paymentId,
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'package' => $packageKey,
                'purpose' => PlatformPendingPayment::PURPOSE_SUBSCRIPTION,
                'payment_channel' => 'balance',
                'amount' => $price,
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => ($automatic ? 'auto-renew:' : 'balance:').$user->id,
                'customer_note' => ($automatic ? 'Perpanjang otomatis ' : 'Perpanjang langganan ').($package['name'] ?? $packageKey).' dari saldo MikPay.'
                    .($discountPercent > 0
                        ? ' Diskon referral '.rtrim(rtrim(number_format($discountPercent, 2, ',', ''), '0'), ',').'% (harga normal Rp '.number_format($normalPrice, 0, ',', '.').').'
                        : ''),
            ]);

            return [$payment, $bonusUsed, $price, $discountPercent];
        });

        $partners->recordForPayment($payment, $price - $bonusUsed);

        $tenant->refresh();

        $discountNote = $discountPercent > 0
            ? ' Diskon referral hemat Rp '.number_format($normalPrice - $price, 0, ',', '.').'.'
            : '';

        return [
            'ok' => true,
            'message' => 'Langganan MikPay '.($package['name'] ?? $packageKey).' berhasil. Panel aktif sampai '
                .format_wib($tenant->subscriptionEndsAt()).'.'.$discountNote,
        ];
    }

    public function cancelPendingDeposit(PlatformPendingPayment $payment): void
    {
        if (! $payment->isPending() || ! $payment->isDeposit()) {
            return;
        }

        $payment->forceFill([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => 'user',
            'reject_reason' => 'Deposit dibatalkan pengguna.',
            'xendit_invoice_url' => null,
        ])->save();
    }
}
