<?php

namespace App\Services;

use App\Models\BusinessSetting;

class SubscriptionPackageService
{
    /** @return list<string> */
    public static function defaultPremiumFeatures(): array
    {
        return [
            'Dashboard & ringkasan router (API MikroTik)',
            'Manajemen Hotspot (user, profil, host)',
            'PPP Secrets, Profiles & sesi aktif',
            'Tagihan PPP & pembayaran (billing)',
            'Voucher & laporan penjualan',
            'Keuangan: cashflow & laporan (PDF)',
            'WhatsApp (Fonnte) pengingat terjadwal',
            'Telegram Bot per akun',
            'Langganan: QRIS / transfer + bukti',
            'Branding: upload logo & kop surat',
            'Multi-router per akun (sesuai paket)',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaultPackageMeta(): array
    {
        return [
            'monthly' => [
                'key' => 'monthly',
                'name' => 'Paket 1 Bulan',
                'price' => 25000,
                'duration' => 30,
                'duration_label' => 'per bulan',
                'subtitle' => null,
                'original_price' => null,
                'discount_badge' => null,
                'featured' => false,
                'featured_ribbon' => null,
                'button_label' => 'Pilih Paket',
                'highlight_footer' => null,
            ],
            'monthly5' => [
                'key' => 'monthly5',
                'name' => 'Paket 5 Bulan',
                'price' => 100000,
                'duration' => 150,
                'duration_label' => null,
                'subtitle' => '5 bulan (hemat 20%)',
                'original_price' => 125000,
                'discount_badge' => 'DISKON 20%',
                'featured' => true,
                'featured_ribbon' => 'POPULER',
                'button_label' => 'Pilih Paket Populer',
                'highlight_footer' => 'Semua fitur premium di atas — hemat & masa aktif lebih panjang',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultLandingHeadlines(): array
    {
        return [
            'pricing_title' => 'Pilih Paket yang Tepat',
            'pricing_subtitle' => 'Trial gratis, lalu perpanjang dengan QRIS atau transfer — semua fitur premium di bawah ini',
            'premium_tagline' => 'Satu suite premium untuk billing & jaringan MikroTik profesional',
        ];
    }

    /**
     * Kunci paket berbayar (selain trial) untuk form pendaftaran & validasi.
     *
     * @return list<string>
     */
    public static function paidPackageKeys(): array
    {
        return ['monthly', 'monthly5'];
    }

    /**
     * Headline beranda (harga) — dari DB atau default.
     *
     * @return array{pricing_title: string, pricing_subtitle: string, premium_tagline: string}
     */
    public static function landingHeadlines(): array
    {
        $defaults = self::defaultLandingHeadlines();
        $raw = BusinessSetting::get()->subscription_landing_headlines;
        if (! is_array($raw)) {
            return $defaults;
        }

        return [
            'pricing_title' => is_string($raw['pricing_title'] ?? null) && $raw['pricing_title'] !== ''
                ? $raw['pricing_title']
                : $defaults['pricing_title'],
            'pricing_subtitle' => is_string($raw['pricing_subtitle'] ?? null) && $raw['pricing_subtitle'] !== ''
                ? $raw['pricing_subtitle']
                : $defaults['pricing_subtitle'],
            'premium_tagline' => is_string($raw['premium_tagline'] ?? null) && $raw['premium_tagline'] !== ''
                ? $raw['premium_tagline']
                : $defaults['premium_tagline'],
        ];
    }

    /**
     * Semua paket berbayar untuk UI (langganan, superadmin, landing) dengan fitur premium yang sama.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $features = BusinessSetting::get()->subscription_premium_features;
        if (! is_array($features) || $features === []) {
            $features = self::defaultPremiumFeatures();
        } else {
            $features = array_values(array_filter(array_map(static fn ($l) => is_string($l) ? trim($l) : '', $features), static fn ($l) => $l !== ''));
            if ($features === []) {
                $features = self::defaultPremiumFeatures();
            }
        }

        $meta = BusinessSetting::get()->subscription_packages_meta;
        if (! is_array($meta)) {
            $meta = [];
        }

        $out = [];
        foreach (self::defaultPackageMeta() as $key => $base) {
            $row = $base;
            if (isset($meta[$key]) && is_array($meta[$key])) {
                $row = array_merge($row, array_intersect_key($meta[$key], array_flip(array_keys($base))));
            }
            $limits = config('mikpay.subscription_packages.' . $key, []);
            if (is_array($limits)) {
                foreach (['max_customers', 'max_routers', 'max_users'] as $k) {
                    if (array_key_exists($k, $limits)) {
                        $row[$k] = $limits[$k];
                    }
                }
            }
            $row['features'] = $features;
            $price = (int) ($row['price'] ?? 0);
            $orig = isset($row['original_price']) ? (int) $row['original_price'] : null;
            if ($orig !== null && $orig > $price) {
                $row['discount'] = $orig - $price;
            } else {
                $row['discount'] = $row['discount'] ?? null;
            }
            $out[$key] = $row;
        }

        return $out;
    }
}
