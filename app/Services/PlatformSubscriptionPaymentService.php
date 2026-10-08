<?php

namespace App\Services;

use App\Models\PlatformPendingPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlatformSubscriptionPaymentService
{
    public function __construct(private readonly XenditService $xendit)
    {
    }

    public function xenditReady(): bool
    {
        return PaymentGateway::platformXenditEnabled();
    }

    /**
     * Buat pending payment + invoice Xendit (platform keys dari .env).
     *
     * @return array{ok: bool, message: string, payment?: PlatformPendingPayment, invoice_url?: string}
     */
    public function startXenditCheckout(Tenant $tenant, User $user, string $packageKey, array $package): array
    {
        if (! $this->xenditReady()) {
            return ['ok' => false, 'message' => 'Xendit platform belum dikonfigurasi di .env.'];
        }

        $existing = PlatformPendingPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            if (
                $existing->package === $packageKey
                && filled($existing->xendit_invoice_url)
                && $existing->payment_channel === 'xendit'
            ) {
                return [
                    'ok' => true,
                    'message' => 'Lanjutkan pembayaran yang sudah dibuat.',
                    'payment' => $existing,
                    'invoice_url' => $existing->xendit_invoice_url,
                ];
            }

            return [
                'ok' => false,
                'message' => 'Masih ada pembayaran pending. Selesaikan atau batalkan dulu.',
                'payment' => $existing,
                'invoice_url' => $existing->xendit_invoice_url ?: null,
            ];
        }

        $amount = (int) ($package['price'] ?? 0);
        if ($amount < 1) {
            return ['ok' => false, 'message' => 'Harga paket tidak valid.'];
        }

        do {
            $paymentId = 'MPS'.strtoupper(bin2hex(random_bytes(4))).now()->format('ymd');
        } while (PlatformPendingPayment::query()->where('payment_id', $paymentId)->exists());

        $payment = PlatformPendingPayment::query()->create([
            'payment_id' => $paymentId,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'package' => $packageKey,
            'payment_channel' => 'xendit',
            'amount' => $amount,
            'status' => 'pending',
        ]);

        $invoice = $this->xendit->createPlatformSubscriptionInvoice($tenant, $payment, $package, [
            'success_url' => panel_route('subscription').'?status=success&pid='.$payment->payment_id,
            'failure_url' => panel_route('subscription').'?status=failed&pid='.$payment->payment_id,
        ]);

        if (! ($invoice['ok'] ?? false)) {
            $payment->forceFill([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => 'system',
                'reject_reason' => 'Gagal membuat invoice Xendit: '.($invoice['message'] ?? 'unknown'),
            ])->save();

            return [
                'ok' => false,
                'message' => (string) ($invoice['message'] ?? 'Gagal membuat invoice Xendit.'),
            ];
        }

        return [
            'ok' => true,
            'message' => 'Invoice Xendit siap.',
            'payment' => $payment->fresh(),
            'invoice_url' => (string) $invoice['invoice_url'],
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function markPaidFromXendit(PlatformPendingPayment $payment, array $payload = []): array
    {
        if ($payment->status === 'approved') {
            return ['ok' => true, 'message' => 'Sudah diapprove sebelumnya.'];
        }

        if ($payment->status !== 'pending') {
            return ['ok' => false, 'message' => 'Pembayaran tidak dalam status pending.'];
        }

        $pkg = SubscriptionPackageService::get((string) $payment->package);
        if (! $pkg) {
            return ['ok' => false, 'message' => 'Paket tidak dikenali.'];
        }

        $tenant = $payment->tenant;
        if (! $tenant) {
            return ['ok' => false, 'message' => 'Tenant tidak ditemukan.'];
        }

        DB::transaction(function () use ($payment, $pkg, $tenant, $payload) {
            $tenant->extendPlatformSubscription(
                (string) $payment->package,
                (int) ($pkg['duration'] ?? 30)
            );

            $payment->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => 'xendit',
                'xendit_invoice_id' => $payload['id'] ?? $payment->xendit_invoice_id,
            ])->save();
        });

        Log::info('platform.subscription.xendit_paid', [
            'payment_id' => $payment->payment_id,
            'tenant_id' => $tenant->id,
            'package' => $payment->package,
        ]);

        app(PartnerProgramService::class)->recordForPayment($payment->fresh());

        return ['ok' => true, 'message' => 'Langganan diaktifkan via Xendit.'];
    }

    public function cancelPending(PlatformPendingPayment $payment): void
    {
        if (! $payment->isPending()) {
            return;
        }

        $payment->forceFill([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => 'user',
            'reject_reason' => 'Dibatalkan pengguna sebelum bayar.',
            'xendit_invoice_url' => null,
        ])->save();
    }
}
