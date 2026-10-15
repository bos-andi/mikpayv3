<?php

namespace App\Services;

use App\Jobs\FulfillVoucherOrder;
use App\Models\PlatformSetting;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\VoucherOrder;
use App\Models\VoucherShopItem;
use App\Support\PaymentGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Toko voucher hotspot publik per tenant: pembeli bayar QRIS (gateway platform),
 * voucher langsung dibuat di MikroTik tenant, uang masuk saldo tenant.
 */
class VoucherShopService
{
    public const CHARSETS = [
        'num' => '23456789',
        'lower' => 'abcdefghjkmnpqrstuvwxyz23456789',
        'upper' => 'ABCDEFGHJKMNPQRSTUVWXYZ23456789',
    ];

    public const DEFAULT_SETTINGS = [
        'enabled' => false,
        'title' => '',
        'tagline' => 'Beli voucher WiFi, bayar QRIS, langsung online.',
        'contact_wa' => '',
        'login_url' => '',
        'router_id' => null,
        'server' => '',
        'code_length' => 6,
        'code_chars' => 'num',
        'prefix' => '',
    ];

    public function __construct(
        private XenditService $xendit,
        private AndidevPayService $andidev,
        private MikrotikService $mikrotik,
        private WaApiService $waapi,
    ) {}

    /**
     * @return array{enabled: bool, title: string, tagline: string, contact_wa: string, login_url: string, router_id: ?int, server: string, code_length: int, code_chars: string, prefix: string}
     */
    public function settings(Tenant $tenant): array
    {
        $raw = $tenant->metaGet('voucher_shop');
        $s = array_merge(self::DEFAULT_SETTINGS, is_array($raw) ? $raw : []);

        return [
            'enabled' => (bool) $s['enabled'],
            'title' => trim((string) $s['title']) ?: (string) $tenant->name,
            'tagline' => trim((string) $s['tagline']),
            'contact_wa' => trim((string) $s['contact_wa']),
            'login_url' => trim((string) $s['login_url']),
            'router_id' => $s['router_id'] ? (int) $s['router_id'] : null,
            'server' => trim((string) $s['server']),
            'code_length' => max(4, min(10, (int) $s['code_length'])),
            'code_chars' => isset(self::CHARSETS[$s['code_chars']]) ? (string) $s['code_chars'] : 'num',
            'prefix' => preg_replace('/[^A-Za-z0-9]/', '', (string) $s['prefix']) ?: '',
        ];
    }

    public function saveSettings(Tenant $tenant, array $data): void
    {
        $tenant->metaSet('voucher_shop', [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'title' => (string) ($data['title'] ?? ''),
            'tagline' => (string) ($data['tagline'] ?? ''),
            'contact_wa' => (string) ($data['contact_wa'] ?? ''),
            'login_url' => (string) ($data['login_url'] ?? ''),
            'router_id' => ! empty($data['router_id']) ? (int) $data['router_id'] : null,
            'server' => (string) ($data['server'] ?? ''),
            'code_length' => (int) ($data['code_length'] ?? 6),
            'code_chars' => (string) ($data['code_chars'] ?? 'num'),
            'prefix' => (string) ($data['prefix'] ?? ''),
        ]);
        $tenant->save();
    }

    public function shopUrl(Tenant $tenant): string
    {
        return 'https://'.$tenant->panelHost().'/voucher';
    }

    public function router(Tenant $tenant): ?Router
    {
        $id = $this->settings($tenant)['router_id'];
        $query = Router::query()->where('tenant_id', $tenant->id);

        return ($id ? (clone $query)->whereKey($id)->first() : null) ?? $query->orderBy('id')->first();
    }

    public static function feePercent(): float
    {
        $mdr = max(0, min(10, (float) config('mikpay.voucher_shop.qris_mdr_percent', 0.7)));
        $vat = max(0, min(50, (float) config('mikpay.voucher_shop.qris_vat_percent', 11)));

        return $mdr * (1 + $vat / 100);
    }

    /**
     * Potongan QRIS gateway yang tetap per transaksi (rupiah).
     */
    public static function feeFlat(): int
    {
        return min(10000, (int) config('mikpay.voucher_shop.qris_fee_flat', 0));
    }

    /**
     * Perkiraan potongan gateway atas total bayar, dibulatkan ke atas di tiap komponen (skenario terburuk).
     */
    public static function qrisFeeOn(int $total): int
    {
        $mdr = (int) ceil(round($total * (float) config('mikpay.voucher_shop.qris_mdr_percent', 0.7) / 100, 6));

        return $mdr + (int) ceil(round($mdr * (float) config('mikpay.voucher_shop.qris_vat_percent', 11) / 100, 6)) + self::feeFlat();
    }

    public static function minPrice(): int
    {
        return (int) config('mikpay.voucher_shop.min_price', 1500);
    }

    /**
     * Biaya layanan MikPay per transaksi (nominal tetap), diatur superadmin.
     */
    public static function serviceFee(): int
    {
        $raw = PlatformSetting::get()->payout_settings;
        $value = is_array($raw) && array_key_exists('voucher_service_fee', $raw)
            ? $raw['voucher_service_fee']
            : config('mikpay.voucher_shop.service_fee', 0);

        return max(0, min(100000, (int) $value));
    }

    public static function saveServiceFee(int $fee): void
    {
        $setting = PlatformSetting::get();
        $raw = is_array($setting->payout_settings) ? $setting->payout_settings : [];
        $raw['voucher_service_fee'] = max(0, min(100000, $fee));
        $setting->payout_settings = $raw;
        $setting->save();
    }

    /**
     * Total biaya layanan yang dibayar pembeli (QRIS gateway + biaya MikPay).
     * Potongan gateway dihitung dari total, jadi dibalik agar tenant menerima harga utuh dan MikPay menerima biaya layanannya utuh.
     */
    public static function feeFor(int $price, ?int $serviceFee = null): int
    {
        if ($price <= 0) {
            return 0;
        }
        $base = $price + ($serviceFee ?? self::serviceFee());
        $p = self::feePercent();
        $gross = $base + self::feeFlat();
        $total = $p > 0 ? (int) ceil(round($gross / (1 - $p / 100), 6)) : $gross;
        while ($total - self::qrisFeeOn($total) < $base) {
            $total++;
        }

        return $total - $price;
    }

    /**
     * @return array{open: bool, reason: string}
     */
    public function availability(Tenant $tenant): array
    {
        $s = $this->settings($tenant);
        if (! $s['enabled']) {
            return ['open' => false, 'reason' => 'Toko voucher online sedang tutup.'];
        }
        if ($tenant->status === 'inactive' || ! $tenant->subscriptionIsActive() || $tenant->awaitingEmailVerification()) {
            return ['open' => false, 'reason' => 'Toko voucher sedang tidak tersedia. Hubungi pengelola WiFi.'];
        }
        if (! PaymentGateway::platformPaymentReady()) {
            return ['open' => false, 'reason' => 'Pembayaran online sedang tidak tersedia.'];
        }
        $router = $this->router($tenant);
        if (! $router || ! $router->isEnabled()) {
            return ['open' => false, 'reason' => 'Toko voucher sedang perawatan. Hubungi pengelola WiFi.'];
        }

        return ['open' => true, 'reason' => ''];
    }

    /**
     * @return array{ok: bool, message: string, order?: VoucherOrder}
     */
    public function createOrder(Tenant $tenant, VoucherShopItem $item, ?string $phone, ?string $ip): array
    {
        $open = $this->availability($tenant);
        if (! $open['open']) {
            return ['ok' => false, 'message' => $open['reason']];
        }
        if ((int) $item->tenant_id !== (int) $tenant->id || ! $item->is_active) {
            return ['ok' => false, 'message' => 'Paket tidak tersedia.'];
        }
        if ($item->price < self::minPrice()) {
            return ['ok' => false, 'message' => 'Harga paket di bawah batas minimal QRIS.'];
        }

        if ($ip) {
            $recent = VoucherOrder::query()
                ->where('tenant_id', $tenant->id)
                ->where('buyer_ip', $ip)
                ->where('status', VoucherOrder::STATUS_PENDING)
                ->where('created_at', '>', now()->subMinutes(15))
                ->count();
            if ($recent >= 3) {
                return ['ok' => false, 'message' => 'Terlalu banyak pesanan belum dibayar. Selesaikan pembayaran sebelumnya atau tunggu 15 menit.'];
            }
        }

        $router = $this->router($tenant);
        $serviceFee = self::serviceFee();
        $fee = self::feeFor($item->price, $serviceFee);
        $expiresAt = now()->addMinutes((int) config('mikpay.voucher_shop.payment_minutes', 15));

        $order = VoucherOrder::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router?->id,
            'item_id' => $item->id,
            'reference' => 'VO'.now()->format('ymd').strtoupper(Str::random(6)),
            'token' => Str::random(40),
            'item_label' => $item->label,
            'profile' => $item->profile,
            'limit_uptime' => $item->limit_uptime,
            'limit_bytes' => $item->limit_bytes,
            'price' => $item->price,
            'fee' => $fee,
            'service_fee' => $serviceFee,
            'total' => $item->price + $fee,
            'phone' => $phone ?: null,
            'status' => VoucherOrder::STATUS_PENDING,
            'expires_at' => $expiresAt,
            'buyer_ip' => $ip,
        ]);

        $referenceId = 'mpv_'.$tenant->id.'_'.$order->id.'_'.strtolower(Str::random(6));
        $qr = PaymentGateway::platformProvider() === PaymentGateway::PROVIDER_ANDIDEV_PAY
            ? $this->andidev->createPlatformQris($referenceId, $order->total, $expiresAt)
            : $this->xendit->createPlatformQris($referenceId, $order->total, $expiresAt);
        if (! $qr['ok']) {
            $order->delete();

            return ['ok' => false, 'message' => 'Gagal membuat QRIS: '.$qr['message']];
        }

        $order->forceFill([
            'xendit_reference_id' => $referenceId,
            'xendit_qr_id' => $qr['qr_id'] ?: null,
            'qr_string' => $qr['qr_string'],
        ])->save();

        return ['ok' => true, 'message' => 'Pesanan dibuat.', 'order' => $order];
    }

    /**
     * Tandai lunas (idempoten) lalu antrekan pembuatan voucher.
     */
    public function markPaid(VoucherOrder $order, array $payload = []): bool
    {
        $amount = (int) ($payload['amount'] ?? 0);
        if ($amount > 0 && $amount < (int) $order->total) {
            Log::warning('voucher_shop.amount_mismatch', ['order' => $order->reference, 'paid' => $amount, 'total' => $order->total]);

            return false;
        }

        $transitioned = DB::transaction(function () use ($order) {
            $locked = VoucherOrder::query()->whereKey($order->id)->lockForUpdate()->first();
            if (! $locked || ! in_array($locked->status, [VoucherOrder::STATUS_PENDING, VoucherOrder::STATUS_EXPIRED], true)) {
                return false;
            }
            $locked->forceFill([
                'status' => VoucherOrder::STATUS_PAID,
                'paid_at' => now(),
                'qr_string' => null,
            ])->save();

            return true;
        });

        if ($transitioned) {
            Log::info('voucher_shop.paid', ['order' => $order->reference, 'tenant_id' => $order->tenant_id]);
            FulfillVoucherOrder::dispatch($order->id);
        }

        return $transitioned;
    }

    /**
     * Fallback polling: cek status QRIS ke Xendit (dibatasi 1x per 5 detik per pesanan).
     */
    public function checkPayment(VoucherOrder $order): void
    {
        if (! $order->isPending() || ! $order->xendit_qr_id) {
            return;
        }
        if (! Cache::add('voucher_shop.check.'.$order->id, 1, now()->addSeconds(5))) {
            return;
        }

        $res = $this->fetchQrPayment($order);
        if ($res['paid']) {
            $this->markPaid($order, ['amount' => (int) ($res['payment']['amount'] ?? 0)]);
        }
    }

    /**
     * @return array{ok: bool, paid: bool, payment?: array}
     */
    protected function fetchQrPayment(VoucherOrder $order): array
    {
        return AndidevPayService::isPaymentId($order->xendit_qr_id)
            ? $this->andidev->fetchPlatformQrPayment((string) $order->xendit_qr_id)
            : $this->xendit->fetchPlatformQrPayment((string) $order->xendit_qr_id);
    }

    /**
     * Buat user hotspot di MikroTik. Aman diulang: kode dicadangkan dulu di DB.
     *
     * @return array{ok: bool, message: string}
     */
    public function fulfill(VoucherOrder $order): array
    {
        $order->refresh();
        if ($order->status === VoucherOrder::STATUS_FULFILLED) {
            return ['ok' => true, 'message' => 'Sudah dibuat.'];
        }
        if (! in_array($order->status, [VoucherOrder::STATUS_PAID, VoucherOrder::STATUS_FAILED], true)) {
            return ['ok' => false, 'message' => 'Pesanan belum dibayar.'];
        }

        $tenant = $order->tenant;
        $router = $order->router ?? ($tenant ? $this->router($tenant) : null);
        $order->increment('fulfill_attempts');

        if (! $tenant || ! $router) {
            return $this->failAttempt($order, 'Router tenant tidak ditemukan.');
        }

        $settings = $this->settings($tenant);

        try {
            if (! $order->voucher_code) {
                $code = null;
                for ($i = 0; $i < 25; $i++) {
                    $candidate = $this->generateCode($settings);
                    if (! $this->mikrotik->hotspotUserExists($router, $candidate)) {
                        $code = $candidate;
                        break;
                    }
                }
                $this->mikrotik->disconnect();
                if (! $code) {
                    return $this->failAttempt($order, 'Tidak bisa membuat kode unik / router tidak terjangkau.');
                }
                $order->forceFill(['voucher_code' => $code, 'voucher_password' => $code, 'router_id' => $router->id])->save();
            } elseif ($this->mikrotik->hotspotUserExists($router, $order->voucher_code)) {
                $this->mikrotik->disconnect();

                return $this->complete($order);
            }
            $this->mikrotik->disconnect();

            $params = [
                'name' => $order->voucher_code,
                'password' => $order->voucher_password ?: $order->voucher_code,
                'profile' => $order->profile,
                'disabled' => 'no',
                'comment' => 'vc-shop-'.$order->reference,
            ];
            if ($settings['server'] !== '' && $settings['server'] !== 'all') {
                $params['server'] = $settings['server'];
            }
            if ($order->limit_uptime) {
                $params['limit-uptime'] = $order->limit_uptime;
            }
            if ($order->limit_bytes) {
                $params['limit-bytes-total'] = (string) $order->limit_bytes;
            }

            $result = $this->mikrotik->addHotspotUser($router, $params);
            if (! $result['ok']) {
                return $this->failAttempt($order, $result['message']);
            }
        } catch (\Throwable $e) {
            $this->mikrotik->disconnect();

            return $this->failAttempt($order, $e->getMessage());
        }

        return $this->complete($order);
    }

    public function markFailed(VoucherOrder $order, string $error): void
    {
        $order->forceFill([
            'status' => VoucherOrder::STATUS_FAILED,
            'last_error' => mb_substr($error, 0, 500),
        ])->save();
        Log::warning('voucher_shop.fulfill_failed', ['order' => $order->reference, 'error' => $error]);
    }

    /**
     * Kedaluwarsakan pesanan pending yang lewat batas (cek Xendit sekali dulu), dan antrekan ulang pesanan lunas yang macet.
     *
     * @return array{expired: int, requeued: int}
     */
    public function maintain(): array
    {
        $expired = 0;
        VoucherOrder::query()
            ->where('status', VoucherOrder::STATUS_PENDING)
            ->where('expires_at', '<', now()->subMinute())
            ->limit(100)
            ->get()
            ->each(function (VoucherOrder $order) use (&$expired) {
                if ($order->xendit_qr_id) {
                    $res = $this->fetchQrPayment($order);
                    if ($res['paid']) {
                        $this->markPaid($order, ['amount' => (int) ($res['payment']['amount'] ?? 0)]);

                        return;
                    }
                }
                $updated = VoucherOrder::query()
                    ->whereKey($order->id)
                    ->where('status', VoucherOrder::STATUS_PENDING)
                    ->update(['status' => VoucherOrder::STATUS_EXPIRED, 'qr_string' => null, 'updated_at' => now()]);
                $expired += $updated;
            });

        $requeued = 0;
        VoucherOrder::query()
            ->where('status', VoucherOrder::STATUS_PAID)
            ->where('updated_at', '<', now()->subMinutes(20))
            ->limit(50)
            ->get()
            ->each(function (VoucherOrder $order) use (&$requeued) {
                $order->touch();
                FulfillVoucherOrder::dispatch($order->id);
                $requeued++;
            });

        return ['expired' => $expired, 'requeued' => $requeued];
    }

    /**
     * Total penjualan voucher yang sudah dibayar (harga bersih, tanpa biaya QRIS).
     */
    public function collectedQuery(Tenant $tenant)
    {
        return VoucherOrder::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', VoucherOrder::PAID_STATUSES);
    }

    protected function complete(VoucherOrder $order): array
    {
        $order->forceFill([
            'status' => VoucherOrder::STATUS_FULFILLED,
            'fulfilled_at' => $order->fulfilled_at ?: now(),
            'last_error' => null,
        ])->save();
        Log::info('voucher_shop.fulfilled', ['order' => $order->reference]);

        $this->sendWhatsApp($order);

        return ['ok' => true, 'message' => 'Voucher dibuat.'];
    }

    protected function failAttempt(VoucherOrder $order, string $error): array
    {
        $order->forceFill(['last_error' => mb_substr($error, 0, 500)])->save();

        return ['ok' => false, 'message' => $error];
    }

    protected function sendWhatsApp(VoucherOrder $order): void
    {
        $tenant = $order->tenant;
        if (! $order->phone || $order->wa_sent_at || ! $tenant || ! $tenant->waapiEnabled()) {
            return;
        }
        $token = $tenant->plainWaapiToken();
        if ($token === '') {
            return;
        }

        $settings = $this->settings($tenant);
        $lines = [
            '*Voucher '.$settings['title'].'*',
            '',
            'Paket: '.$order->item_label,
            'Kode voucher: *'.$order->voucher_code.'*',
        ];
        if ($order->voucher_password && $order->voucher_password !== $order->voucher_code) {
            $lines[] = 'Password: *'.$order->voucher_password.'*';
        }
        $lines[] = '';
        $lines[] = 'Sambungkan ke jaringan '.$settings['title'].', lalu masukkan kode di halaman login.';
        $lines[] = 'Lihat lagi: '.$this->shopUrl($tenant).'/o/'.$order->token;
        $lines[] = 'Ref: '.$order->reference;

        try {
            $res = $this->waapi->sendText($token, $order->phone, implode("\n", $lines));
            if ($res['ok']) {
                $order->forceFill(['wa_sent_at' => now()])->save();
            }
        } catch (\Throwable $e) {
            Log::warning('voucher_shop.wa_failed', ['order' => $order->reference, 'error' => $e->getMessage()]);
        }
    }

    protected function generateCode(array $settings): string
    {
        $chars = self::CHARSETS[$settings['code_chars']] ?? self::CHARSETS['num'];
        $max = strlen($chars) - 1;
        $code = '';
        for ($i = 0; $i < $settings['code_length']; $i++) {
            $code .= $chars[random_int(0, $max)];
        }

        return $settings['prefix'].$code;
    }
}
