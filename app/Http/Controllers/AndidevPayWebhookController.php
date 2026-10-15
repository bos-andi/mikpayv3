<?php

namespace App\Http\Controllers;

use App\Models\BillingPaymentLink;
use App\Models\PlatformPendingPayment;
use App\Models\VoucherOrder;
use App\Services\AndidevPayService;
use App\Services\PaymentLinkService;
use App\Services\PlatformSubscriptionPaymentService;
use App\Services\PlatformWalletService;
use App\Services\VoucherShopService;
use App\Support\PaymentGateway;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Andidev Pay (payment.succeeded / payment.expired). Signature wajib valid.
 * order_id: mpl_ (link tagihan), mps_ (langganan), mpd_ (deposit), mpv_ (toko voucher).
 */
class AndidevPayWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        AndidevPayService $andidev,
        PaymentLinkService $links,
        PlatformSubscriptionPaymentService $platformSubs,
        PlatformWalletService $wallet,
        VoucherShopService $voucherShop
    ): Response {
        $raw = $request->getContent();
        if (! $andidev->verifyWebhookSignature($raw, $request->header('X-Andidev-Signature'))) {
            Log::warning('andidev_pay.webhook.invalid_signature', ['delivery' => $request->header('X-Andidev-Delivery')]);

            return response('unauthorized', 401);
        }

        $payload = json_decode($raw, true);
        $event = (string) ($payload['event'] ?? '');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $orderId = (string) ($data['order_id'] ?? '');

        // LOG_LEVEL production sering "error" — catat di warning agar webhook terlihat.
        Log::warning('andidev_pay.webhook.hit', [
            'event' => $event,
            'order_id' => $orderId,
            'payment_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? null,
            'environment' => $payload['environment'] ?? null,
        ]);

        if ($orderId === '' || ! in_array($event, ['payment.succeeded', 'payment.expired'], true)) {
            return response('ignored', 200);
        }

        $paid = $event === 'payment.succeeded' && strtoupper((string) ($data['status'] ?? 'PAID')) === 'PAID';

        try {
            return $andidev->withOrderLock($orderId, fn () => match (true) {
                str_starts_with($orderId, 'mpv_') => $this->handleVoucherOrder($voucherShop, $orderId, $data, $paid),
                str_starts_with($orderId, 'mpd_') => $this->handlePlatformPayment($platformSubs, $wallet, $orderId, $data, $paid, true),
                str_starts_with($orderId, 'mps_') => $this->handlePlatformPayment($platformSubs, $wallet, $orderId, $data, $paid, false),
                str_starts_with($orderId, 'mpl_') => $this->handlePaymentLink($links, $orderId, $data, $paid),
                default => response('ignored', 200),
            });
        } catch (LockTimeoutException) {
            return response('busy', 503);
        }
    }

    protected function handlePaymentLink(PaymentLinkService $links, string $orderId, array $data, bool $paid): Response
    {
        $link = BillingPaymentLink::query()->with('tenant')->where('xendit_external_id', $orderId)->first();

        if (! $link && preg_match('/^mpl_(\d+)_(\d+)_/', $orderId, $m)) {
            $link = BillingPaymentLink::query()
                ->with('tenant')
                ->where('id', (int) $m[2])
                ->where('tenant_id', (int) $m[1])
                ->first();
        }

        if (! $link) {
            Log::warning('andidev_pay.webhook.link_not_found', ['order_id' => $orderId]);

            return response('not found', 200);
        }

        if ($paid) {
            $result = $links->markPaidFromAndidevPay($link, $data);
            Log::info('andidev_pay.webhook.link_processed', [
                'link_id' => $link->id,
                'ok' => $result['ok'],
                'message' => $result['message'],
            ]);

            return response('ok', 200);
        }

        if ($link->status === 'pending' && $link->xendit_external_id === $orderId) {
            $link->forceFill([
                'payment_checkout' => null,
                'xendit_invoice_url' => null,
            ])->save();
        }

        return response('ok', 200);
    }

    protected function handlePlatformPayment(
        PlatformSubscriptionPaymentService $platformSubs,
        PlatformWalletService $wallet,
        string $orderId,
        array $data,
        bool $paid,
        bool $isDeposit
    ): Response {
        $payment = PlatformPendingPayment::query()->with('tenant')->where('xendit_external_id', $orderId)->first();

        if (! $payment && preg_match('/^mp[sd]_(\d+)_(\d+)_/', $orderId, $m)) {
            $payment = PlatformPendingPayment::query()
                ->with('tenant')
                ->where('id', (int) $m[2])
                ->where('tenant_id', (int) $m[1])
                ->first();
        }

        if (! $payment) {
            Log::warning('andidev_pay.webhook.platform_not_found', ['order_id' => $orderId]);

            return response('not found', 200);
        }

        if ($paid) {
            $payload = ['id' => $data['id'] ?? null];
            $result = $isDeposit
                ? $wallet->markDepositPaidFromXendit($payment, $payload, PaymentGateway::PROVIDER_ANDIDEV_PAY)
                : $platformSubs->markPaidFromXendit($payment, $payload, PaymentGateway::PROVIDER_ANDIDEV_PAY);
            Log::info('andidev_pay.webhook.platform_processed', [
                'payment_id' => $payment->payment_id,
                'deposit' => $isDeposit,
                'ok' => $result['ok'],
                'message' => $result['message'],
            ]);

            return response('ok', 200);
        }

        if ($payment->isPending()) {
            $payment->forceFill([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => PaymentGateway::PROVIDER_ANDIDEV_PAY,
                'reject_reason' => 'Tagihan Andidev Pay kedaluwarsa.',
                'xendit_invoice_url' => null,
            ])->save();
        }

        return response('ok', 200);
    }

    protected function handleVoucherOrder(VoucherShopService $voucherShop, string $orderId, array $data, bool $paid): Response
    {
        $order = VoucherOrder::query()->where('xendit_reference_id', $orderId)->first();
        if (! $order) {
            Log::warning('andidev_pay.webhook.voucher_not_found', ['order_id' => $orderId]);

            return response('not found', 200);
        }

        if (! $paid) {
            return response('ignored', 200);
        }

        $applied = $voucherShop->markPaid($order, ['amount' => (int) ($data['amount'] ?? 0)]);
        Log::info('andidev_pay.webhook.voucher_processed', ['reference' => $order->reference, 'applied' => $applied]);

        return response('ok', 200);
    }
}
