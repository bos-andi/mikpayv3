<?php

namespace App\Http\Controllers;

use App\Models\BillingPaymentLink;
use App\Models\PlatformPendingPayment;
use App\Models\Tenant;
use App\Services\PaymentLinkService;
use App\Services\PlatformSubscriptionPaymentService;
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
        PlatformSubscriptionPaymentService $platformSubs
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

        // Langganan SaaS platform: external_id mps_{tenantId}_{paymentRowId}_...
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
