<?php

namespace App\Services;

use App\Models\BillingPaymentLink;
use App\Models\PlatformPendingPayment;
use App\Models\Tenant;
use App\Support\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Andidev Pay (https://payment.andidev.id/docs) — payment hub QRIS / VA / minimarket.
 * Dipakai untuk penerimaan pembayaran platform MikPay; kredensial di .env.
 */
class AndidevPayService
{
    /** @var list<string> */
    public const VA_BANKS = ['BCA', 'BRI', 'BNI', 'MANDIRI', 'PERMATA', 'BSI', 'CIMB'];

    public const MIN_AMOUNT = 1000;

    public const MAX_AMOUNT = 10000000;

    public function __construct(private readonly XenditService $xendit)
    {
    }

    public static function isPaymentId(?string $id): bool
    {
        return is_string($id) && str_starts_with($id, 'pay_');
    }

    /**
     * Invoice langganan SaaS MikPay (pelanggan memilih metode di checkout_url).
     *
     * @param  array<string, mixed>  $package
     * @param  array{success_url?: string, failure_url?: string}  $options
     * @return array{ok: bool, message: string, invoice_id?: string, invoice_url?: string, external_id?: string, raw?: array}
     */
    public function createPlatformSubscriptionInvoice(
        Tenant $tenant,
        PlatformPendingPayment $payment,
        array $package,
        array $options = []
    ): array {
        $packageName = (string) ($package['name'] ?? $payment->package);

        return $this->createPlatformInvoice($tenant, $payment, 'mps', [
            'description' => 'Langganan MikPay '.$packageName.' — '.$tenant->name,
            'success_return_url' => (string) ($options['success_url'] ?? url('/langganan?status=success')),
            'cancel_return_url' => (string) ($options['failure_url'] ?? url('/langganan?status=failed')),
            'metadata' => [
                'type' => 'platform_subscription',
                'tenant_id' => $tenant->id,
                'payment_id' => $payment->payment_id,
                'package' => $payment->package,
            ],
        ]);
    }

    /**
     * Invoice isi saldo MikPay.
     *
     * @param  array{success_url?: string, failure_url?: string}  $options
     * @return array{ok: bool, message: string, invoice_id?: string, invoice_url?: string, external_id?: string, raw?: array}
     */
    public function createPlatformDepositInvoice(Tenant $tenant, PlatformPendingPayment $payment, array $options = []): array
    {
        return $this->createPlatformInvoice($tenant, $payment, 'mpd', [
            'description' => 'Isi saldo MikPay — '.$tenant->name,
            'success_return_url' => (string) ($options['success_url'] ?? url('/langganan?status=deposit_success')),
            'cancel_return_url' => (string) ($options['failure_url'] ?? url('/langganan?status=deposit_failed')),
            'metadata' => [
                'type' => 'platform_deposit',
                'tenant_id' => $tenant->id,
                'payment_id' => $payment->payment_id,
            ],
        ]);
    }

    /**
     * QRIS / VA untuk link tagihan pelanggan, ditampilkan di halaman MikPay (tanpa redirect).
     *
     * @param  string  $channel  QRIS | VA_BCA | VA_BRI | ...
     * @return array{ok: bool, message: string, checkout?: array, raw?: array}
     */
    public function createLinkCheckout(BillingPaymentLink $link, string $channel): array
    {
        if (! $link->isPayable()) {
            return ['ok' => false, 'message' => 'Link pembayaran tidak berlaku.'];
        }

        $isQris = $channel === 'QRIS';
        $bank = $isQris ? '' : substr($channel, 3);
        if (! $isQris && ! in_array($bank, self::VA_BANKS, true)) {
            return ['ok' => false, 'message' => 'Bank VA tidak didukung.'];
        }

        $orderId = $this->xendit->makeExternalId($link, true);
        $name = $this->xendit->sanitizeVaName($link->display_name ?: $link->customer_name ?: 'Pelanggan MikPay');
        $periods = is_array($link->periods) ? $link->periods : [];

        $result = $this->createPayment([
            'order_id' => $orderId,
            'amount' => (int) $link->amount,
            'payment_channel' => $channel,
            'expires_in_hours' => 24,
            'description' => 'Tagihan '.($periods !== [] ? implode(', ', $periods) : 'internet').' — '.$name,
            'customer' => ['name' => $name],
            'metadata' => [
                'type' => 'payment_link',
                'tenant_id' => $link->tenant_id,
                'payment_link_id' => $link->id,
            ],
        ]);
        if (! $result['ok']) {
            return $result;
        }

        $payment = $result['payment'];
        $qrString = (string) ($payment['qr_string'] ?? '');
        $vaNumber = (string) ($payment['va_number'] ?? '');
        if (($isQris && $qrString === '') || (! $isQris && $vaNumber === '')) {
            return ['ok' => false, 'message' => 'Metode '.($isQris ? 'QRIS' : 'VA '.$bank).' sedang tidak tersedia. Coba metode lain.', 'raw' => $result['raw'] ?? []];
        }

        $checkout = [
            'provider' => PaymentGateway::PROVIDER_ANDIDEV_PAY,
            'method' => $isQris ? 'qris' : 'va',
            'channel' => $isQris ? 'QRIS' : $bank,
            'label' => $isQris ? 'QRIS' : 'Transfer VA '.$bank,
            'payment_id' => (string) $payment['id'],
            'external_id' => $orderId,
            'amount' => (int) $link->amount,
            'expires_at' => $this->parseTime($payment['expires_at'] ?? null, now()->addHours(24))->toIso8601String(),
        ];
        if ($isQris) {
            $checkout['qr_string'] = $qrString;
        } else {
            $checkout['account_number'] = $vaNumber;
            $checkout['bank_code'] = $bank;
            $checkout['account_name'] = $name;
        }

        $link->forceFill([
            'xendit_invoice_id' => (string) $payment['id'],
            'xendit_external_id' => $orderId,
            'xendit_invoice_url' => null,
            'payment_checkout' => $checkout,
        ])->save();

        return ['ok' => true, 'message' => 'Pembayaran siap.', 'checkout' => $checkout, 'raw' => $result['raw'] ?? []];
    }

    /**
     * QRIS dinamis untuk toko voucher. Batas waktu lokal tetap dari $expiresAt; tagihan di Andidev Pay minimal 1 jam.
     *
     * @return array{ok: bool, message: string, qr_id?: string, qr_string?: string, raw?: array}
     */
    public function createPlatformQris(string $referenceId, int $amount, Carbon $expiresAt): array
    {
        $result = $this->createPayment([
            'order_id' => $referenceId,
            'amount' => $amount,
            'payment_channel' => 'QRIS',
            'expires_in_hours' => max(1, min(24, (int) ceil(now()->diffInMinutes($expiresAt) / 60))),
            'description' => 'Voucher WiFi',
            'metadata' => ['type' => 'voucher_order'],
        ]);
        if (! $result['ok']) {
            return $result;
        }

        $qrString = (string) ($result['payment']['qr_string'] ?? '');
        if ($qrString === '') {
            return ['ok' => false, 'message' => 'QRIS sedang tidak tersedia.', 'raw' => $result['raw'] ?? []];
        }

        return [
            'ok' => true,
            'message' => 'QRIS siap.',
            'qr_id' => (string) $result['payment']['id'],
            'qr_string' => $qrString,
            'raw' => $result['raw'] ?? [],
        ];
    }

    /**
     * POST /payments — idempoten per order_id (order_id + nominal sama mengembalikan tagihan yang ada).
     *
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, message: string, payment?: array, raw?: array}
     */
    public function createPayment(array $payload): array
    {
        if (! PaymentGateway::platformAndidevPayEnabled()) {
            return ['ok' => false, 'message' => 'Andidev Pay belum dikonfigurasi di .env.'];
        }

        $amount = (int) ($payload['amount'] ?? 0);
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            return ['ok' => false, 'message' => 'Nominal pembayaran online harus antara Rp 1.000 dan Rp 10.000.000.'];
        }

        $payload['currency'] = 'IDR';

        try {
            $response = $this->http(45)->post($this->apiUrl('/payments'), $payload);
        } catch (\Throwable $e) {
            Log::warning('andidev_pay.create.http_error', ['order_id' => $payload['order_id'] ?? null, 'message' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Gagal menghubungi Andidev Pay. Coba lagi sebentar.'];
        }

        $data = $response->json() ?? [];
        $payment = is_array($data['data'] ?? null) ? $data['data'] : null;
        if (! $response->successful() || ! $payment || empty($payment['id'])) {
            Log::warning('andidev_pay.create.failed', [
                'order_id' => $payload['order_id'] ?? null,
                'status' => $response->status(),
                'body' => $data ?: $response->body(),
            ]);

            return ['ok' => false, 'message' => $this->errorMessage($data, $response->status()), 'raw' => $data];
        }

        return ['ok' => true, 'message' => 'Tagihan dibuat.', 'payment' => $payment, 'raw' => $data];
    }

    /**
     * GET /payments/{id} — $id boleh pay_... atau order_id.
     *
     * @return array{ok: bool, message?: string, payment?: array}
     */
    public function getPayment(string $id): array
    {
        if ($id === '' || ! PaymentGateway::platformAndidevPayEnabled()) {
            return ['ok' => false, 'message' => 'Andidev Pay belum siap.'];
        }

        try {
            $response = $this->http(15)->get($this->apiUrl('/payments/'.rawurlencode($id)));
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $data = $response->json() ?? [];
        if (! $response->successful() || ! is_array($data['data'] ?? null)) {
            return ['ok' => false, 'message' => $this->errorMessage($data, $response->status())];
        }

        return ['ok' => true, 'payment' => $data['data']];
    }

    /**
     * Fallback bila webhook belum masuk. Dibatasi 1x per 15 detik per tagihan
     * karena rate limit API (120 request/menit per key) juga dipakai untuk membuat tagihan.
     *
     * @return array{ok: bool, paid: bool, payment?: array}
     */
    public function pollPayment(string $id): array
    {
        if ($id === '' || ! Cache::add('andidev_pay.poll.'.$id, 1, now()->addSeconds(15))) {
            return ['ok' => false, 'paid' => false];
        }

        $res = $this->getPayment($id);
        $payment = $res['payment'] ?? [];

        return [
            'ok' => $res['ok'],
            'paid' => strtoupper((string) ($payment['status'] ?? '')) === 'PAID',
            'payment' => $payment,
        ];
    }

    /**
     * @return array{ok: bool, paid: bool, payment?: array}
     */
    public function fetchPlatformQrPayment(string $qrId): array
    {
        return $this->pollPayment($qrId);
    }

    /**
     * Header X-Andidev-Signature: t={unix},v1=HMAC-SHA256(secret, t + "." + raw_body).
     */
    public function verifyWebhookSignature(string $rawBody, ?string $header): bool
    {
        $secret = PaymentGateway::platformAndidevPayWebhookSecret();
        if ($secret === '' || ! is_string($header) || $header === '') {
            return false;
        }

        parse_str(str_replace(',', '&', $header), $sig);
        $t = is_string($sig['t'] ?? null) ? $sig['t'] : '';
        $v1 = is_string($sig['v1'] ?? null) ? $sig['v1'] : '';
        if (! ctype_digit($t) || $v1 === '' || abs(time() - (int) $t) > 300) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $t.'.'.$rawBody, $secret), $v1);
    }

    /**
     * Serialisasi pemrosesan satu order (webhook vs polling) agar pembayaran tidak tercatat dua kali.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws LockTimeoutException
     */
    public function withOrderLock(string $orderId, callable $callback): mixed
    {
        return Cache::lock('andidev_pay.order.'.$orderId, 30)->block(10, $callback);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array{ok: bool, message: string, invoice_id?: string, invoice_url?: string, external_id?: string, raw?: array}
     */
    protected function createPlatformInvoice(Tenant $tenant, PlatformPendingPayment $payment, string $prefix, array $fields): array
    {
        $orderId = $payment->xendit_external_id
            ?: ($prefix.'_'.$tenant->id.'_'.$payment->id.'_'.substr(md5($payment->payment_id.microtime(true)), 0, 8));

        $result = $this->createPayment(array_merge($fields, [
            'order_id' => $orderId,
            'amount' => (int) $payment->amount,
            'expires_in_hours' => 24,
        ]));
        if (! $result['ok']) {
            return $result;
        }

        $invoiceId = (string) $result['payment']['id'];
        $invoiceUrl = (string) ($result['payment']['checkout_url'] ?? '');
        if ($invoiceUrl === '') {
            return ['ok' => false, 'message' => 'Respons Andidev Pay tidak lengkap.', 'raw' => $result['raw'] ?? []];
        }

        $payment->forceFill([
            'xendit_invoice_id' => $invoiceId,
            'xendit_external_id' => $orderId,
            'xendit_invoice_url' => $invoiceUrl,
            'payment_channel' => PaymentGateway::PROVIDER_ANDIDEV_PAY,
        ])->save();

        return [
            'ok' => true,
            'message' => 'Tagihan Andidev Pay dibuat.',
            'invoice_id' => $invoiceId,
            'invoice_url' => $invoiceUrl,
            'external_id' => $orderId,
            'raw' => $result['raw'] ?? [],
        ];
    }

    protected function http(int $timeout): PendingRequest
    {
        return Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders(['X-Api-Key' => PaymentGateway::platformAndidevPayKey()]);
    }

    protected function apiUrl(string $path): string
    {
        return config('mikpay.payment.andidev_pay.url', 'https://payment.andidev.id').'/api/v1'.$path;
    }

    protected function parseTime(mixed $value, Carbon $fallback): Carbon
    {
        try {
            return is_string($value) && $value !== '' ? Carbon::parse($value) : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    protected function errorMessage(array $data, int $status): string
    {
        $message = (string) ($data['error']['message'] ?? '');
        $details = $data['error']['details'] ?? null;
        if (is_array($details) && $details !== []) {
            $flat = [];
            foreach ($details as $field => $errors) {
                $flat[] = $field.': '.implode(' ', array_map('strval', (array) $errors));
            }
            $message = trim($message.' ('.implode('; ', $flat).')');
        }

        if ($message !== '') {
            return $message;
        }

        return match ($status) {
            401 => 'API key Andidev Pay tidak valid.',
            429 => 'Terlalu banyak permintaan ke Andidev Pay. Coba lagi sebentar.',
            default => 'Andidev Pay menolak permintaan (HTTP '.$status.').',
        };
    }
}
