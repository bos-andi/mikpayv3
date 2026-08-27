<?php

namespace App\Services;

use App\Models\PlatformSetting;

class ManualPaymentGatewayService
{
    /** @var list<string> */
    private const CHANNEL_ORDER_DEFAULT = ['bank_transfer', 'ewallet', 'qris'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function channels(): array
    {
        $channels = [];

        $bank = config('payment_gateway.bank');
        if (
            $this->filled($bank['bank_name'] ?? null)
            && $this->filled($bank['account_number'] ?? null)
            && $this->filled($bank['account_name'] ?? null)
        ) {
            $channels['bank_transfer'] = [
                'name' => 'Bank Transfer',
                'account_name' => $bank['account_name'],
                'account_number' => $bank['account_number'],
                'bank_name' => $bank['bank_name'],
            ];
        }

        $ew = config('payment_gateway.ewallet');
        if ($this->filled($ew['wallet_number'] ?? null)) {
            $channels['ewallet'] = [
                'name' => 'E-Wallet',
                'wallet_name' => $this->filled($ew['wallet_name'] ?? null)
                    ? $ew['wallet_name']
                    : 'E-Wallet',
                'wallet_number' => $ew['wallet_number'],
            ];
        }

        $qris = config('payment_gateway.qris');
        $channels['qris'] = [
            'name' => 'QRIS',
            'merchant_name' => $qris['merchant_name'] ?? 'MIKPAY',
            'nmid' => $qris['nmid'] ?? '',
            'terminal_id' => $qris['terminal_id'] ?? '',
            'qr_image' => $qris['image_path'] ?? 'mikpay/img/qris_mikpay.png',
        ];

        return $this->applyChannelOrder($channels);
    }

    /**
     * @param  array<string, array<string, mixed>>  $channels
     * @return array<string, array<string, mixed>>
     */
    private function applyChannelOrder(array $channels): array
    {
        $allowed = ['bank_transfer', 'ewallet', 'qris'];
        $raw = PlatformSetting::get()->payment_channel_order;
        $order = is_array($raw)
            ? array_values(array_filter($raw, fn ($k) => is_string($k) && in_array($k, $allowed, true)))
            : [];

        if ($order === []) {
            $order = self::CHANNEL_ORDER_DEFAULT;
        }

        foreach ($allowed as $k) {
            if (! in_array($k, $order, true)) {
                $order[] = $k;
            }
        }

        $out = [];
        foreach ($order as $k) {
            if (isset($channels[$k])) {
                $out[$k] = $channels[$k];
            }
        }
        foreach ($channels as $k => $data) {
            if (! isset($out[$k])) {
                $out[$k] = $data;
            }
        }

        return $out;
    }

    private function filled(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    public function generatePaymentId(): string
    {
        return 'MP'.strtoupper(bin2hex(random_bytes(4))).now()->format('ymd');
    }
}
