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
    public function __construct(private readonly XenditService $xendit)
    {
    }

    public function depositReady(): bool
    {
        return PaymentGateway::platformXenditEnabled()
            && PaymentGateway::platformXenditSecretKey() !== '';
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

        $payment = PlatformPendingPayment::query()->create([
            'payment_id' => $paymentId,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'package' => 'deposit',
            'purpose' => PlatformPendingPayment::PURPOSE_DEPOSIT,
            'payment_channel' => 'xendit',
            'amount' => $amount,
            'status' => 'pending',
        ]);

        $invoice = $this->xendit->createPlatformDepositInvoice($tenant, $payment, [
            'success_url' => panel_route('subscription').'?status=deposit_success&pid='.$payment->payment_id,
            'failure_url' => panel_route('subscription').'?status=deposit_failed&pid='.$payment->payment_id,
        ]);

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
    public function markDepositPaidFromXendit(PlatformPendingPayment $payment, array $payload = []): array
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

        DB::transaction(function () use ($payment, $tenant, $payload) {
            $tenant->addPlatformBalance((int) $payment->amount, 'Isi saldo online '.$payment->payment_id);

            $payment->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => 'xendit',
                'xendit_invoice_id' => $payload['id'] ?? $payment->xendit_invoice_id,
            ])->save();
        });

        Log::info('platform.wallet.deposit_paid', [
            'payment_id' => $payment->payment_id,
            'tenant_id' => $tenant->id,
            'amount' => $payment->amount,
        ]);

        return ['ok' => true, 'message' => 'Saldo berhasil ditambahkan.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function payPackageFromBalance(Tenant $tenant, User $user, string $packageKey, array $package): array
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

        [$payment, $bonusUsed, $price, $discountPercent] = DB::transaction(function () use ($tenant, $user, $packageKey, $package, $normalPrice, $partners) {
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
                'approved_by' => 'balance:'.$user->id,
                'customer_note' => 'Perpanjang langganan '.($package['name'] ?? $packageKey).' dari saldo MikPay.'
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
