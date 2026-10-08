<?php

namespace App\Http\Controllers;

use App\Models\BillingPaymentLink;
use App\Models\PlatformPendingPayment;
use App\Models\Tenant;
use App\Models\VoucherOrder;
use App\Services\PartnerProgramService;
use App\Services\PaymentLinkService;
use App\Services\PlatformSubscriptionPaymentService;
use App\Services\PlatformWalletService;
use App\Services\TenantPayoutService;
use App\Services\VoucherShopService;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        XenditService $xendit,
        PaymentLinkService $links,
        PlatformSubscriptionPaymentService $platformSubs,
        PlatformWalletService $wallet
    ): Response {
        $payload = $request->all();
        $externalId = $this->extractExternalId($payload);
        $status = strtoupper((string) ($payload['status'] ?? ''));
        $event = strtolower((string) ($payload['event'] ?? ''));

        Log::info('xendit.webhook.received', [
            'external_id' => $externalId,
            'status' => $status,
            'event' => $event,
            'id' => $payload['id'] ?? null,
        ]);

        // LOG_LEVEL production sering "error" — catat juga di warning agar webhook terlihat.
        Log::warning('xendit.webhook.hit', [
            'external_id' => $externalId,
            'status' => $status,
            'event' => $event,
        ]);

        if ($externalId === '') {
            return response('ignored', 200);
        }

        // Disbursement penarikan dana tenant: external_id mpw_{tenantId}_{withdrawalId}_...
        if (str_starts_with($externalId, 'mpw_')) {
            return $this->handleWithdrawal($request, $xendit, $payload, $externalId, $status);
        }

        // Disbursement pencairan komisi affiliate: external_id mpp_{partnerId}_{withdrawalId}_...
        if (str_starts_with($externalId, 'mpp_')) {
            return $this->handlePartnerWithdrawal($request, $xendit, $payload, $externalId, $status);
        }

        // Toko voucher hotspot (QRIS platform): reference_id mpv_{tenantId}_{orderId}_...
        if (str_starts_with($externalId, 'mpv_')) {
            return $this->handleVoucherOrder($request, $xendit, $payload, $externalId, $event);
        }

        // Deposit saldo platform: external_id mpd_{tenantId}_{paymentRowId}_...
        if (
            str_starts_with($externalId, 'mpd_')
            || ($payload['metadata']['type'] ?? null) === 'platform_deposit'
        ) {
            return $this->handlePlatformDeposit($request, $xendit, $wallet, $payload, $externalId, $status, $event);
        }

        // Langganan SaaS platform (legacy langsung bayar paket): external_id mps_{tenantId}_{paymentRowId}_...
        if (str_starts_with($externalId, 'mps_') || ($payload['metadata']['type'] ?? null) === 'platform_subscription') {
            return $this->handlePlatformSubscription($request, $xendit, $platformSubs, $payload, $externalId, $status, $event);
        }

        $link = BillingPaymentLink::query()
            ->with('tenant')
            ->where('xendit_external_id', $externalId)
            ->first();

        if (! $link && preg_match('/^mpl_(\d+)_(\d+)_/', $externalId, $m)) {
            $link = BillingPaymentLink::query()
                ->with('tenant')
                ->where('id', (int) $m[2])
                ->where('tenant_id', (int) $m[1])
                ->first();
        }

        if (! $link) {
            Log::warning('xendit.webhook.link_not_found', ['external_id' => $externalId]);

            return response('not found', 200);
        }

        /** @var Tenant|null $tenant */
        $tenant = $link->tenant;
        if (! $tenant) {
            return response('no tenant', 200);
        }

        $callbackHeader = $request->header('x-callback-token')
            ?? $request->header('X-CALLBACK-TOKEN');

        if (! $xendit->verifyCallbackToken($tenant, $callbackHeader)) {
            Log::warning('xendit.webhook.invalid_token', ['tenant_id' => $tenant->id]);

            return response('unauthorized', 401);
        }

        if ($this->isPaidPayload($payload, $status, $event)) {
            if (! isset($payload['paid_amount']) && isset($payload['amount'])) {
                $payload['paid_amount'] = $payload['amount'];
            }

            $result = $links->markPaidFromXendit($link, $payload);
            Log::info('xendit.webhook.processed', [
                'link_id' => $link->id,
                'ok' => $result['ok'],
                'message' => $result['message'],
            ]);

            return response('ok', 200);
        }

        if (in_array($status, ['EXPIRED', 'INACTIVE'], true) && $link->status === 'pending') {
            $link->forceFill([
                'payment_checkout' => null,
                'xendit_invoice_url' => null,
            ])->save();
        }

        return response('ok', 200);
    }

    protected function handlePlatformSubscription(
        Request $request,
        XenditService $xendit,
        PlatformSubscriptionPaymentService $platformSubs,
        array $payload,
        string $externalId,
        string $status,
        string $event
    ): Response {
        $callbackHeader = $request->header('x-callback-token')
            ?? $request->header('X-CALLBACK-TOKEN');

        if (! $xendit->verifyPlatformCallbackToken($callbackHeader)) {
            Log::warning('xendit.webhook.platform_sub.invalid_token', ['external_id' => $externalId]);

            return response('unauthorized', 401);
        }

        $payment = PlatformPendingPayment::query()
            ->with('tenant')
            ->where('xendit_external_id', $externalId)
            ->first();

        if (! $payment && preg_match('/^mps_(\d+)_(\d+)_/', $externalId, $m)) {
            $payment = PlatformPendingPayment::query()
                ->with('tenant')
                ->where('id', (int) $m[2])
                ->where('tenant_id', (int) $m[1])
                ->first();
        }

        if (! $payment && ! empty($payload['metadata']['payment_id'])) {
            $payment = PlatformPendingPayment::query()
                ->with('tenant')
                ->where('payment_id', (string) $payload['metadata']['payment_id'])
                ->first();
        }

        if (! $payment) {
            Log::warning('xendit.webhook.platform_sub.not_found', ['external_id' => $externalId]);

            return response('not found', 200);
        }

        if ($this->isPaidPayload($payload, $status, $event)) {
            $result = $platformSubs->markPaidFromXendit($payment, $payload);
            Log::info('xendit.webhook.platform_sub.processed', [
                'payment_id' => $payment->payment_id,
                'ok' => $result['ok'],
                'message' => $result['message'],
            ]);

            return response('ok', 200);
        }

        if (in_array($status, ['EXPIRED', 'INACTIVE'], true) && $payment->isPending()) {
            $payment->forceFill([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => 'xendit',
                'reject_reason' => 'Invoice Xendit kedaluwarsa / inactive.',
                'xendit_invoice_url' => null,
            ])->save();
        }

        return response('ok', 200);
    }

    protected function handlePlatformDeposit(
        Request $request,
        XenditService $xendit,
        PlatformWalletService $wallet,
        array $payload,
        string $externalId,
        string $status,
        string $event
    ): Response {
        $callbackHeader = $request->header('x-callback-token')
            ?? $request->header('X-CALLBACK-TOKEN');

        if (! $xendit->verifyPlatformCallbackToken($callbackHeader)) {
            Log::warning('xendit.webhook.platform_deposit.invalid_token', ['external_id' => $externalId]);

            return response('unauthorized', 401);
        }

        $payment = PlatformPendingPayment::query()
            ->with('tenant')
            ->where('xendit_external_id', $externalId)
            ->first();

        if (! $payment && preg_match('/^mpd_(\d+)_(\d+)_/', $externalId, $m)) {
            $payment = PlatformPendingPayment::query()
                ->with('tenant')
                ->where('id', (int) $m[2])
                ->where('tenant_id', (int) $m[1])
                ->first();
        }

        if (! $payment && ! empty($payload['metadata']['payment_id'])) {
            $payment = PlatformPendingPayment::query()
                ->with('tenant')
                ->where('payment_id', (string) $payload['metadata']['payment_id'])
                ->first();
        }

        if (! $payment) {
            Log::warning('xendit.webhook.platform_deposit.not_found', ['external_id' => $externalId]);

            return response('not found', 200);
        }

        if ($this->isPaidPayload($payload, $status, $event)) {
            $result = $wallet->markDepositPaidFromXendit($payment, $payload);
            Log::info('xendit.webhook.platform_deposit.processed', [
                'payment_id' => $payment->payment_id,
                'ok' => $result['ok'],
                'message' => $result['message'],
            ]);

            return response('ok', 200);
        }

        if (in_array($status, ['EXPIRED', 'INACTIVE'], true) && $payment->isPending()) {
            $payment->forceFill([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => 'xendit',
                'reject_reason' => 'Invoice deposit Xendit kedaluwarsa / inactive.',
                'xendit_invoice_url' => null,
            ])->save();
        }

        return response('ok', 200);
    }

    protected function handleWithdrawal(
        Request $request,
        XenditService $xendit,
        array $payload,
        string $externalId,
        string $status
    ): Response {
        $callbackHeader = $request->header('x-callback-token')
            ?? $request->header('X-CALLBACK-TOKEN');

        if (! $xendit->verifyPlatformCallbackToken($callbackHeader)) {
            Log::warning('xendit.webhook.withdrawal.invalid_token', ['external_id' => $externalId]);

            return response('unauthorized', 401);
        }

        $payouts = app(TenantPayoutService::class);
        $withdrawal = $payouts->findByExternalId($externalId);
        if (! $withdrawal) {
            Log::warning('xendit.webhook.withdrawal.not_found', ['external_id' => $externalId]);

            return response('not found', 200);
        }

        $payouts->applyXenditPayload($withdrawal, $payload);
        Log::info('xendit.webhook.withdrawal.processed', [
            'reference' => $withdrawal->reference,
            'xendit_status' => $status,
            'status' => $withdrawal->status,
        ]);

        return response('ok', 200);
    }

    protected function handlePartnerWithdrawal(
        Request $request,
        XenditService $xendit,
        array $payload,
        string $externalId,
        string $status
    ): Response {
        $callbackHeader = $request->header('x-callback-token')
            ?? $request->header('X-CALLBACK-TOKEN');

        if (! $xendit->verifyPlatformCallbackToken($callbackHeader)) {
            Log::warning('xendit.webhook.partner_withdrawal.invalid_token', ['external_id' => $externalId]);

            return response('unauthorized', 401);
        }

        $partners = app(PartnerProgramService::class);
        $withdrawal = $partners->findByExternalId($externalId);
        if (! $withdrawal) {
            Log::warning('xendit.webhook.partner_withdrawal.not_found', ['external_id' => $externalId]);

            return response('not found', 200);
        }

        $partners->applyXenditPayload($withdrawal, $payload);
        Log::info('xendit.webhook.partner_withdrawal.processed', [
            'reference' => $withdrawal->reference,
            'xendit_status' => $status,
            'status' => $withdrawal->status,
        ]);

        return response('ok', 200);
    }

    protected function handleVoucherOrder(
        Request $request,
        XenditService $xendit,
        array $payload,
        string $externalId,
        string $event
    ): Response {
        $callbackHeader = $request->header('x-callback-token')
            ?? $request->header('X-CALLBACK-TOKEN');

        if (! $xendit->verifyPlatformCallbackToken($callbackHeader)) {
            Log::warning('xendit.webhook.voucher.invalid_token', ['external_id' => $externalId]);

            return response('unauthorized', 401);
        }

        $order = VoucherOrder::query()->where('xendit_reference_id', $externalId)->first();
        if (! $order) {
            Log::warning('xendit.webhook.voucher.not_found', ['external_id' => $externalId]);

            return response('not found', 200);
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $paymentStatus = strtoupper((string) ($data['status'] ?? ''));
        $isPayment = $event === '' || str_contains($event, 'qr.payment');
        if (! $isPayment || ! in_array($paymentStatus, ['SUCCEEDED', 'COMPLETED', 'PAID'], true)) {
            Log::info('xendit.webhook.voucher.ignored', ['reference' => $order->reference, 'event' => $event, 'status' => $paymentStatus]);

            return response('ignored', 200);
        }

        $applied = app(VoucherShopService::class)->markPaid($order, ['amount' => (int) ($data['amount'] ?? 0)]);
        Log::info('xendit.webhook.voucher.processed', ['reference' => $order->reference, 'applied' => $applied]);

        return response('ok', 200);
    }

    protected function isPaidPayload(array $payload, string $status, string $event): bool
    {
        if (in_array($status, ['PAID', 'SETTLED', 'COMPLETED', 'SUCCESS'], true)) {
            return true;
        }

        if (isset($payload['callback_virtual_account_id'], $payload['amount'])) {
            return true;
        }

        if ($event === 'qr.payment' || str_contains($event, 'qr.payment')) {
            return in_array($status, ['COMPLETED', 'PAID', 'SETTLED', ''], true);
        }

        return false;
    }

    protected function extractExternalId(array $payload): string
    {
        $candidates = [
            $payload['external_id'] ?? null,
            $payload['qr_code']['external_id'] ?? null,
            $payload['data']['external_id'] ?? null,
            $payload['data']['reference_id'] ?? null,
            $payload['reference_id'] ?? null,
        ];

        foreach ($candidates as $id) {
            if (is_string($id) && $id !== '') {
                return $id;
            }
        }

        return '';
    }
}
