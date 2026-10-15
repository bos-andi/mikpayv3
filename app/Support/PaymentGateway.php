<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * Default gateway = platform (.env): Andidev Pay bila dikonfigurasi, selain itu Xendit — tidak bisa diubah dari UI panel.
 * Tenant memilih tepat 1 gateway aktif: platform | xendit | midtrans | doku | tripay.
 */
class PaymentGateway
{
    public const PROVIDER_XENDIT = 'xendit';

    public const PROVIDER_ANDIDEV_PAY = 'andidev_pay';

    public const ACTIVE_PLATFORM = 'platform';

    public const ACTIVE_XENDIT = 'xendit';

    public const ACTIVE_MIDTRANS = 'midtrans';

    public const ACTIVE_DOKU = 'doku';

    public const ACTIVE_TRIPAY = 'tripay';

    /** @return list<string> */
    public static function allowedActives(): array
    {
        return [
            self::ACTIVE_PLATFORM,
            self::ACTIVE_XENDIT,
            self::ACTIVE_MIDTRANS,
            self::ACTIVE_DOKU,
            self::ACTIVE_TRIPAY,
        ];
    }

    public static function platformXenditEnabled(): bool
    {
        return (bool) config('mikpay.payment.xendit.enabled', true)
            && self::platformXenditSecretKey() !== '';
    }

    public static function platformXenditSecretKey(): string
    {
        return trim((string) config('mikpay.payment.xendit.secret_key', ''));
    }

    public static function platformXenditCallbackToken(): string
    {
        return trim((string) config('mikpay.payment.xendit.callback_token', ''));
    }

    /**
     * Langganan & deposit hanya dikonfirmasi lewat webhook, jadi webhook secret wajib ada sebelum aktif.
     */
    public static function platformAndidevPayEnabled(): bool
    {
        return (bool) config('mikpay.payment.andidev_pay.enabled', true)
            && self::platformAndidevPayKey() !== ''
            && self::platformAndidevPayWebhookSecret() !== '';
    }

    public static function platformAndidevPayKey(): string
    {
        return trim((string) config('mikpay.payment.andidev_pay.api_key', ''));
    }

    public static function platformAndidevPayWebhookSecret(): string
    {
        return trim((string) config('mikpay.payment.andidev_pay.webhook_secret', ''));
    }

    /**
     * Provider penerimaan pembayaran platform (langganan, deposit, link tagihan default, toko voucher).
     */
    public static function platformProvider(): ?string
    {
        if (self::platformAndidevPayEnabled()) {
            return self::PROVIDER_ANDIDEV_PAY;
        }

        if (self::platformXenditEnabled()) {
            return self::PROVIDER_XENDIT;
        }

        return null;
    }

    public static function platformPaymentReady(): bool
    {
        return self::platformProvider() !== null;
    }

    public static function providerLabel(?string $provider): string
    {
        return match ($provider) {
            self::PROVIDER_ANDIDEV_PAY => 'Andidev Pay',
            self::PROVIDER_XENDIT => 'Xendit',
            default => '-',
        };
    }

    public static function platformProviderLabel(): string
    {
        return self::providerLabel(self::platformProvider() ?? self::PROVIDER_XENDIT);
    }

    /**
     * Provider checkout link tagihan pelanggan tenant (null = belum bisa bayar online).
     */
    public static function checkoutProvider(?Tenant $tenant): ?string
    {
        $active = self::active($tenant);

        if ($active === self::ACTIVE_PLATFORM) {
            return self::platformProvider();
        }

        if ($active === self::ACTIVE_XENDIT && $tenant && $tenant->ownXenditSecretKey() !== '') {
            return self::PROVIDER_XENDIT;
        }

        return null;
    }

    public static function defaultGateway(): string
    {
        return (string) config('mikpay.payment.default_gateway', 'xendit');
    }

    /**
     * Gateway aktif tunggal untuk tenant.
     */
    public static function active(?Tenant $tenant): string
    {
        if (! $tenant) {
            return self::ACTIVE_PLATFORM;
        }

        $active = (string) $tenant->metaGet('payment.active', '');
        if (in_array($active, self::allowedActives(), true)) {
            return $active;
        }

        // Legacy migration: payment.mode + payment.gateway
        $mode = (string) $tenant->metaGet('payment.mode', 'platform');
        if ($mode === 'own') {
            $g = (string) $tenant->metaGet('payment.gateway', 'xendit');

            return in_array($g, ['xendit', 'midtrans', 'doku', 'tripay'], true) ? $g : self::ACTIVE_XENDIT;
        }

        return self::ACTIVE_PLATFORM;
    }

    /** @deprecated */
    public static function modeFor(?Tenant $tenant): string
    {
        return self::active($tenant) === self::ACTIVE_PLATFORM ? 'platform' : 'own';
    }

    public static function usesOwnGateway(?Tenant $tenant): bool
    {
        return self::active($tenant) !== self::ACTIVE_PLATFORM;
    }

    /**
     * Nama provider untuk checkout (xendit/midtrans/doku/tripay).
     */
    public static function activeGateway(?Tenant $tenant): string
    {
        $active = self::active($tenant);
        if ($active === self::ACTIVE_PLATFORM) {
            return self::defaultGateway();
        }

        return $active;
    }

    public static function resolveXenditSecretKey(?Tenant $tenant): string
    {
        if (self::active($tenant) === self::ACTIVE_XENDIT && $tenant) {
            $own = $tenant->ownXenditSecretKey();
            if ($own !== '') {
                return $own;
            }

            return '';
        }

        if (self::active($tenant) === self::ACTIVE_PLATFORM) {
            return self::platformXenditSecretKey();
        }

        return '';
    }

    public static function resolveXenditCallbackToken(?Tenant $tenant): string
    {
        if (self::active($tenant) === self::ACTIVE_XENDIT && $tenant) {
            $own = $tenant->ownXenditCallbackToken();
            if ($own !== '') {
                return $own;
            }
        }

        return self::platformXenditCallbackToken();
    }

    /**
     * Checkout online siap (saat ini hanya Xendit platform / Xendit sendiri).
     */
    public static function xenditReady(?Tenant $tenant): bool
    {
        $active = self::active($tenant);

        if ($active === self::ACTIVE_PLATFORM) {
            return self::platformXenditEnabled();
        }

        if ($active === self::ACTIVE_XENDIT) {
            return $tenant !== null && $tenant->ownXenditSecretKey() !== '';
        }

        return false;
    }

    public static function onlineCheckoutReady(?Tenant $tenant): bool
    {
        return self::checkoutProvider($tenant) !== null;
    }

    public static function label(string $active): string
    {
        return match ($active) {
            self::ACTIVE_PLATFORM => 'Default MikPay ('.self::platformProviderLabel().')',
            self::ACTIVE_XENDIT => 'Xendit (akun sendiri)',
            self::ACTIVE_MIDTRANS => 'Midtrans',
            self::ACTIVE_DOKU => 'DOKU',
            self::ACTIVE_TRIPAY => 'Tripay',
            default => $active,
        };
    }

    public static function credentialSourceLabel(?Tenant $tenant): string
    {
        return self::label(self::active($tenant));
    }
}
