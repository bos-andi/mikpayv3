<?php

namespace App\Services;

use App\Models\BillingPaymentLink;
use App\Models\PlatformPendingPayment;
use App\Models\Tenant;
use App\Support\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditService
{
    private const INVOICE_URL = 'https://api.xendit.co/v2/invoices';

    private const QR_URL = 'https://api.xendit.co/qr_codes';

    private const VA_URL = 'https://api.xendit.co/callback_virtual_accounts';

    /** @var list<string> Bank VA yang umum aktif di akun Xendit Indonesia */
    public const VA_BANKS = ['BNI', 'BRI', 'MANDIRI'];

    /**
     * Invoice langganan SaaS MikPay — selalu memakai Xendit platform (.env), bukan gateway tenant.
     *
     * @param  array<string, mixed>  $package
     * @param  array{success_url?: string, failure_url?: string, duration?: int}  $options
     * @return array{ok: bool, message: string, invoice_id?: string, invoice_url?: string, external_id?: string, raw?: array}
     */
    public function createPlatformSubscriptionInvoice(
        Tenant $tenant,
        PlatformPendingPayment $payment,
        array $package,
        array $options = []
    ): array {
        $secret = PaymentGateway::platformXenditSecretKey();
        if ($secret === '' || ! PaymentGateway::platformXenditEnabled()) {
            return ['ok' => false, 'message' => 'Xendit platform belum dikonfigurasi di .env.'];
        }

        $externalId = $payment->xendit_external_id
            ?: ('mps_'.$tenant->id.'_'.$payment->id.'_'.substr(md5($payment->payment_id.microtime(true)), 0, 8));

        $amount = (int) $payment->amount;
        if ($amount < 1) {
            return ['ok' => false, 'message' => 'Nominal pembayaran tidak valid.'];
        }

        $packageName = (string) ($package['name'] ?? $payment->package);
        $successUrl = (string) ($options['success_url'] ?? url('/langganan?status=success'));
        $failureUrl = (string) ($options['failure_url'] ?? url('/langganan?status=failed'));

        $payload = [
            'external_id' => $externalId,
            'amount' => $amount,
            'description' => 'Langganan MikPay '.$packageName.' — '.$tenant->name,
            'invoice_duration' => (int) ($options['duration'] ?? 86400),
            'currency' => 'IDR',
            'success_redirect_url' => $successUrl,
            'failure_redirect_url' => $failureUrl,
            'metadata' => [
                'type' => 'platform_subscription',
                'tenant_id' => $tenant->id,
                'payment_id' => $payment->payment_id,
                'package' => $payment->package,
            ],
        ];

        try {
            $response = Http::timeout(45)
                ->withBasicAuth($secret, '')
                ->acceptJson()
                ->post(self::INVOICE_URL, $payload);
        } catch (\Throwable $e) {
            Log::warning('xendit.platform_sub.http_error', ['message' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Gagal menghubungi Xendit: '.$e->getMessage()];
        }

        $data = $response->json() ?? [];
        if (! $response->successful()) {
            $msg = (string) ($data['message'] ?? $response->body() ?: 'Xendit menolak permintaan.');
            Log::warning('xendit.platform_sub.failed', ['status' => $response->status(), 'body' => $data]);

            return ['ok' => false, 'message' => $msg, 'raw' => $data];
        }

        $invoiceId = (string) ($data['id'] ?? '');
        $invoiceUrl = (string) ($data['invoice_url'] ?? '');
        if ($invoiceId === '' || $invoiceUrl === '') {
            return ['ok' => false, 'message' => 'Respons Xendit tidak lengkap.', 'raw' => $data];
        }

        $payment->forceFill([
            'xendit_invoice_id' => $invoiceId,
            'xendit_external_id' => $externalId,
            'xendit_invoice_url' => $invoiceUrl,
            'payment_channel' => 'xendit',
        ])->save();

        return [
            'ok' => true,
            'message' => 'Invoice Xendit dibuat.',
            'invoice_id' => $invoiceId,
            'invoice_url' => $invoiceUrl,
            'external_id' => $externalId,
            'raw' => $data,
        ];
    }

    /**
     * Verifikasi callback khusus pembayaran platform (langganan SaaS).
     */
    public function verifyPlatformCallbackToken(?string $headerToken): bool
    {
        if (! is_string($headerToken) || $headerToken === '') {
            return false;
        }

        $platform = PaymentGateway::platformXenditCallbackToken();
        if ($platform !== '' && hash_equals($platform, $headerToken)) {
            return true;
        }

        // Tanpa token di .env: izinkan (dev), production sebaiknya selalu diisi.
        return $platform === '';
    }

    /**
     * @return array{ok: bool, message: string, invoice_id?: string, invoice_url?: string, external_id?: string, raw?: array}
     */
    public function createInvoice(Tenant $tenant, BillingPaymentLink $link, array $options = []): array
    {
        $secret = \App\Support\PaymentGateway::resolveXenditSecretKey($tenant);
        if ($secret === '') {
            return ['ok' => false, 'message' => 'Xendit belum dikonfigurasi (platform default / gateway tenant).'];
        }

        if (! $link->isPayable()) {
            return ['ok' => false, 'message' => 'Link pembayaran tidak berlaku atau sudah kadaluarsa.'];
        }

        $forceNew = (bool) ($options['force_new_external_id'] ?? false);
        $externalId = (! $forceNew && $link->xendit_external_id)
            ? $link->xendit_external_id
            : $this->makeExternalId($link, true);
        $successUrl = $options['success_url'] ?? $link->publicUrl().'?status=success';
        $failureUrl = $options['failure_url'] ?? $link->publicUrl().'?status=failed';

        $periods = is_array($options['periods'] ?? null)
            ? $options['periods']
            : (is_array($link->periods) ? $link->periods : []);
        $amount = (int) ($options['amount'] ?? $link->amount);
        $periodLabel = $periods !== []
            ? implode(', ', array_map(fn ($p) => (string) $p, $periods))
            : 'tagihan';

        $payload = [
            'external_id' => $externalId,
            'amount' => $amount,
            'description' => 'Tagihan '.$periodLabel.' — '.($link->display_name ?: $link->customer_name),
            'invoice_duration' => (int) ($options['duration'] ?? 86400 * 2),
            'currency' => 'IDR',
            'success_redirect_url' => $successUrl,
            'failure_redirect_url' => $failureUrl,
            'metadata' => [
                'tenant_id' => $link->tenant_id,
                'router_id' => $link->router_id,
                'payment_link_id' => $link->id,
                'customer_name' => $link->customer_name,
                'periods' => implode(',', $periods),
            ],
        ];

        $phone = $this->normalizePhone($link->phone);
        if ($phone !== '') {
            $payload['customer'] = [
                'given_names' => $link->display_name ?: $link->customer_name,
                'mobile_number' => $phone,
            ];
        }

        try {
            $response = Http::timeout(45)
                ->withBasicAuth($secret, '')
                ->acceptJson()
                ->post(self::INVOICE_URL, $payload);
        } catch (\Throwable $e) {
            Log::warning('xendit.invoice.http_error', ['message' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Gagal menghubungi Xendit: '.$e->getMessage()];
        }

        $data = $response->json() ?? [];
        if (! $response->successful()) {
            $msg = (string) ($data['message'] ?? $response->body() ?: 'Xendit menolak permintaan.');
            Log::warning('xendit.invoice.failed', ['status' => $response->status(), 'body' => $data]);

            return ['ok' => false, 'message' => $msg, 'raw' => $data];
        }

        $invoiceId = (string) ($data['id'] ?? '');
        $invoiceUrl = (string) ($data['invoice_url'] ?? '');
        if ($invoiceId === '' || $invoiceUrl === '') {
            return ['ok' => false, 'message' => 'Respons Xendit tidak lengkap.', 'raw' => $data];
        }

        $link->forceFill([
            'xendit_invoice_id' => $invoiceId,
            'xendit_external_id' => $externalId,
            'xendit_invoice_url' => $invoiceUrl,
        ])->save();

        return [
            'ok' => true,
            'message' => 'Invoice Xendit dibuat.',
            'invoice_id' => $invoiceId,
            'invoice_url' => $invoiceUrl,
            'external_id' => $externalId,
            'raw' => $data,
        ];
    }

    /**
     * Buat QRIS dinamis (tanpa redirect ke halaman Xendit).
     *
     * @return array{ok: bool, message: string, checkout?: array, raw?: array}
     */
    public function createQris(Tenant $tenant, BillingPaymentLink $link, ?string $callbackUrl = null): array
    {
        $secret = \App\Support\PaymentGateway::resolveXenditSecretKey($tenant);
        if ($secret === '') {
            return ['ok' => false, 'message' => 'Xendit belum dikonfigurasi.'];
        }

        if (! $link->isPayable()) {
            return ['ok' => false, 'message' => 'Link pembayaran tidak berlaku.'];
        }

        $this->ensureWebhookUrls($tenant, $callbackUrl);

        $externalId = $this->makeExternalId($link, true);
        $expiresAt = now()->addHours(24);
        // API QR Xendit (api-version 2022-07-31): reference_id + currency IDR (tanpa channel_code).
        $payload = [
            'reference_id' => $externalId,
            'type' => 'DYNAMIC',
            'currency' => 'IDR',
            'amount' => (int) $link->amount,
            'expires_at' => $expiresAt->utc()->format('Y-m-d\TH:i:s.000\Z'),
        ];

        try {
            $response = Http::timeout(45)
                ->withBasicAuth($secret, '')
                ->acceptJson()
                ->withHeaders(['api-version' => '2022-07-31'])
                ->post(self::QR_URL, $payload);
        } catch (\Throwable $e) {
            Log::warning('xendit.qris.http_error', ['message' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Gagal menghubungi Xendit: '.$e->getMessage()];
        }

        $data = $response->json() ?? [];
        if (! $response->successful()) {
            $msg = $this->extractErrorMessage($data, $response->body());
            Log::warning('xendit.qris.failed', ['status' => $response->status(), 'body' => $data]);

            return ['ok' => false, 'message' => $msg, 'raw' => $data];
        }

        $qrString = (string) ($data['qr_string'] ?? data_get($data, 'metadata.qr_string') ?? '');
        $xenditId = (string) ($data['id'] ?? '');
        if ($qrString === '') {
            return ['ok' => false, 'message' => 'QRIS tidak tersedia dari Xendit.', 'raw' => $data];
        }

        $checkout = [
            'method' => 'qris',
            'channel' => 'QRIS',
            'label' => 'QRIS',
            'qr_string' => $qrString,
            'xendit_id' => $xenditId,
            'external_id' => $externalId,
            'amount' => (int) $link->amount,
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        $link->forceFill([
            'xendit_invoice_id' => $xenditId ?: null,
            'xendit_external_id' => $externalId,
            'xendit_invoice_url' => null,
            'payment_checkout' => $checkout,
        ])->save();

        return ['ok' => true, 'message' => 'QRIS siap.', 'checkout' => $checkout, 'raw' => $data];
    }

    /**
     * Buat Virtual Account closed amount (tanpa redirect).
     *
     * @return array{ok: bool, message: string, checkout?: array, raw?: array}
     */
    public function createVirtualAccount(Tenant $tenant, BillingPaymentLink $link, string $bankCode, ?string $callbackUrl = null): array
    {
        $bankCode = strtoupper(trim($bankCode));
        if (! in_array($bankCode, self::VA_BANKS, true)) {
            return ['ok' => false, 'message' => 'Bank VA tidak didukung.'];
        }

        $secret = \App\Support\PaymentGateway::resolveXenditSecretKey($tenant);
        if ($secret === '') {
            return ['ok' => false, 'message' => 'Xendit belum dikonfigurasi.'];
        }

        if (! $link->isPayable()) {
            return ['ok' => false, 'message' => 'Link pembayaran tidak berlaku.'];
        }

        $this->ensureWebhookUrls($tenant, $callbackUrl);

        $externalId = $this->makeExternalId($link, true);
        $expiresAt = now()->addHours(24);
        $name = $this->sanitizeVaName($link->display_name ?: $link->customer_name ?: 'Pelanggan MikPay');

        $payload = [
            'external_id' => $externalId,
            'bank_code' => $bankCode,
            'name' => $name,
            'is_closed' => true,
            'expected_amount' => (int) $link->amount,
            'is_single_use' => true,
            'expiration_date' => $expiresAt->toIso8601String(),
        ];

        try {
            $response = Http::timeout(45)
                ->withBasicAuth($secret, '')
                ->acceptJson()
                ->post(self::VA_URL, $payload);
        } catch (\Throwable $e) {
            Log::warning('xendit.va.http_error', ['message' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Gagal menghubungi Xendit: '.$e->getMessage()];
        }

        $data = $response->json() ?? [];
        if (! $response->successful()) {
            $msg = $this->extractErrorMessage($data, $response->body());
            if (($data['error_code'] ?? '') === 'BANK_NOT_ACTIVATED_ERROR') {
                $msg = 'VA '.$bankCode.' belum diaktifkan di akun Xendit. Aktifkan di dashboard Xendit atau pilih bank lain.';
            }
            Log::warning('xendit.va.failed', ['status' => $response->status(), 'body' => $data, 'bank' => $bankCode]);

            return ['ok' => false, 'message' => $msg, 'raw' => $data];
        }

        $accountNumber = (string) ($data['account_number'] ?? '');
        $xenditId = (string) ($data['id'] ?? '');
        if ($accountNumber === '') {
            return ['ok' => false, 'message' => 'Nomor VA tidak tersedia dari Xendit.', 'raw' => $data];
        }

        $checkout = [
            'method' => 'va',
            'channel' => $bankCode,
            'label' => 'Transfer VA '.$bankCode,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
            'account_name' => $name,
            'xendit_id' => $xenditId,
            'external_id' => $externalId,
            'amount' => (int) $link->amount,
            'expires_at' => isset($data['expiration_date'])
                ? Carbon::parse($data['expiration_date'])->toIso8601String()
                : $expiresAt->toIso8601String(),
        ];

        $link->forceFill([
            'xendit_invoice_id' => $xenditId ?: null,
            'xendit_external_id' => $externalId,
            'xendit_invoice_url' => null,
            'payment_checkout' => $checkout,
        ])->save();

        return ['ok' => true, 'message' => 'VA siap.', 'checkout' => $checkout, 'raw' => $data];
    }

    public function verifyCallbackToken(Tenant $tenant, ?string $headerToken): bool
    {
        if (! is_string($headerToken) || $headerToken === '') {
            return false;
        }

        $expected = \App\Support\PaymentGateway::resolveXenditCallbackToken($tenant);
        if ($expected !== '' && hash_equals($expected, $headerToken)) {
            return true;
        }

        $platform = \App\Support\PaymentGateway::platformXenditCallbackToken();
        if ($platform !== '' && hash_equals($platform, $headerToken)) {
            return true;
        }

        if ($expected === '' && $platform === '') {
            return true;
        }

        return false;
    }

    public function makeExternalId(BillingPaymentLink $link, bool $unique = false): string
    {
        $base = 'mpl_'.$link->tenant_id.'_'.$link->id.'_'.substr($link->token, 0, 8);
        if (! $unique) {
            return $base;
        }

        return $base.'_'.substr(md5(implode(',', (array) $link->periods).'|'.$link->amount.'|'.microtime(true)), 0, 10);
    }

    public function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }
        if (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return '+'.$digits;
    }

    public function sanitizeVaName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9 ]+/', ' ', $name) ?: 'Pelanggan';
        $name = trim(preg_replace('/\s+/', ' ', $name) ?: 'Pelanggan');
        if ($name === '') {
            $name = 'Pelanggan';
        }

        return mb_substr($name, 0, 50);
    }

    /**
     * Pastikan webhook URL Xendit mengarah ke MikPay (sekali per secret, di-cache).
     * Tanpa ini, VA/QRIS bisa terbayar tapi status di aplikasi tidak update.
     */
    public function ensureWebhookUrls(?Tenant $tenant = null, ?string $callbackUrl = null): void
    {
        $secret = $tenant
            ? PaymentGateway::resolveXenditSecretKey($tenant)
            : PaymentGateway::platformXenditSecretKey();
        if ($secret === '') {
            return;
        }

        $url = $callbackUrl ?: url('/webhooks/xendit');
        $cacheKey = 'xendit.webhook_urls.'.substr(hash('sha256', $secret.'|'.$url), 0, 24);

        if (cache()->get($cacheKey)) {
            return;
        }

        $ok = true;
        foreach (['fva_paid', 'fva_status', 'invoice', 'qr_code'] as $type) {
            try {
                $response = Http::timeout(20)
                    ->withBasicAuth($secret, '')
                    ->acceptJson()
                    ->post('https://api.xendit.co/callback_urls/'.$type, ['url' => $url]);
                if (! $response->successful()) {
                    $ok = false;
                    Log::warning('xendit.webhook_url.failed', [
                        'type' => $type,
                        'status' => $response->status(),
                        'body' => $response->json() ?? $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                $ok = false;
                Log::warning('xendit.webhook_url.error', [
                    'type' => $type,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($ok) {
            cache()->put($cacheKey, true, now()->addDays(7));
        }
    }

    /**
     * Ambil status Fixed VA dari Xendit (untuk fallback jika webhook belum masuk).
     *
     * @return array{ok: bool, status?: string, expired?: bool, raw?: array, message?: string}
     */
    public function fetchVirtualAccount(Tenant $tenant, string $xenditId): array
    {
        $secret = PaymentGateway::resolveXenditSecretKey($tenant);
        if ($secret === '' || $xenditId === '') {
            return ['ok' => false, 'message' => 'Xendit belum siap.'];
        }

        try {
            $response = Http::timeout(20)
                ->withBasicAuth($secret, '')
                ->acceptJson()
                ->get(self::VA_URL.'/'.$xenditId);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $data = $response->json() ?? [];
        if (! $response->successful()) {
            return ['ok' => false, 'message' => $this->extractErrorMessage($data, $response->body()), 'raw' => $data];
        }

        $status = strtoupper((string) ($data['status'] ?? ''));
        $expired = false;
        if (! empty($data['expiration_date'])) {
            try {
                $expired = now()->greaterThan(Carbon::parse($data['expiration_date']));
            } catch (\Throwable) {
                $expired = false;
            }
        }

        return [
            'ok' => true,
            'status' => $status,
            'expired' => $expired,
            'raw' => $data,
        ];
    }

    /**
     * Deteksi VA closed/single-use yang sudah dibayar tapi webhook belum sampai.
     * Heuristik: status INACTIVE sebelum tanggal kedaluwarsa → hampir pasti sudah dibayar.
     */
    public function virtualAccountLooksPaid(array $vaFetch): bool
    {
        if (! ($vaFetch['ok'] ?? false)) {
            return false;
        }

        $status = strtoupper((string) ($vaFetch['status'] ?? ''));
        if ($status === 'PENDING' || $status === 'ACTIVE') {
            return false;
        }

        if ($status === 'INACTIVE' && ! ($vaFetch['expired'] ?? false)) {
            return true;
        }

        return false;
    }

    protected function extractErrorMessage(array $data, string $body): string
    {
        $details = [];
        if (! empty($data['errors']) && is_array($data['errors'])) {
            foreach ($data['errors'] as $err) {
                if (! is_array($err)) {
                    continue;
                }
                $path = (string) ($err['path'] ?? '');
                $msg = (string) ($err['message'] ?? '');
                if ($path !== '' || $msg !== '') {
                    $details[] = trim($path.($path && $msg ? ': ' : '').$msg);
                }
            }
        }

        $base = '';
        if (! empty($data['message']) && is_string($data['message'])) {
            $base = $data['message'];
        } elseif (! empty($data['error_code'])) {
            $base = (string) $data['error_code'];
        } elseif ($body !== '') {
            $base = $body;
        } else {
            $base = 'Xendit menolak permintaan.';
        }

        if ($details !== []) {
            return $base.' ('.implode('; ', $details).')';
        }

        return $base;
    }
}
