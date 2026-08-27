<?php

namespace App\Services;

use App\Models\PlatformSetting;

class SubscriptionPackageService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaultPackageMeta(): array
    {
        return [
            'starter' => [
                'key' => 'starter',
                'name' => 'Starter',
                'badge' => 'STARTER',
                'price' => 99000,
                'duration' => 30,
                'duration_label' => '/ bulan',
                'subtitle' => 'Cocok untuk pemula RTRW Net koin / voucheran.',
                'original_price' => null,
                'discount_badge' => null,
                'featured' => false,
                'featured_ribbon' => null,
                'button_label' => 'Mulai Trial — Starter',
                'cta_type' => 'register',
                'max_customers' => 100,
                'max_routers' => 1,
                'features' => [
                    'Hingga 100 Pelanggan',
                    '1 Router MikroTik',
                    'Auto Isolir & WA Gateway',
                    'Hotspot Voucher Generator',
                    '1 Dedicated Remote VPN Tunnel',
                ],
            ],
            'pro' => [
                'key' => 'pro',
                'name' => 'Pro ISP',
                'badge' => 'PRO ISP',
                'price' => 199000,
                'duration' => 30,
                'duration_label' => '/ bulan',
                'subtitle' => 'Solusi lengkap bisnis jaringan menengah & OLT.',
                'original_price' => null,
                'discount_badge' => null,
                'featured' => true,
                'featured_ribbon' => 'PALING POPULER',
                'button_label' => 'Mulai Coba Gratis '.(int) config('mikpay.trial_days', 3).' Hari',
                'cta_type' => 'register',
                'max_customers' => 500,
                'max_routers' => 3,
                'features' => [
                    'Hingga 500 Pelanggan',
                    '3 Router MikroTik',
                    'Pemetaan ODP & Peta GIS',
                    'Remote OLT & TR-069 Modem',
                    'Auto Isolir & WA Gateway',
                    '3 Dedicated Remote VPN Tunnel',
                ],
            ],
            'enterprise' => [
                'key' => 'enterprise',
                'name' => 'Ultimate ISP',
                'badge' => 'ULTIMATE ISP',
                'price' => 399000,
                'duration' => 30,
                'duration_label' => '/ bulan',
                'subtitle' => 'Kapasitas unlimited untuk ISP skala besar.',
                'original_price' => null,
                'discount_badge' => null,
                'featured' => false,
                'featured_ribbon' => null,
                'button_label' => 'Hubungi Tim Sales',
                'cta_type' => 'whatsapp',
                'wa_message' => 'Halo Admin, saya tertarik paket Enterprise',
                'max_customers' => null,
                'max_routers' => null,
                'features' => [
                    'Unlimited Pelanggan',
                    'Unlimited Router & OLT',
                    'Peta ODP & Network Infrastructure',
                    'Tiket Gangguan & Management Teknisi',
                    'Priority VIP Support 24/7',
                ],
            ],
        ];
    }

    /**
     * @return array{pricing_eyebrow: string, pricing_title: string, pricing_subtitle: string}
     */
    public static function defaultLandingHeadlines(): array
    {
        return [
            'pricing_eyebrow' => 'Investasi Terjangkau',
            'pricing_title' => 'Paket Berlangganan Tanpa Biaya Tersembunyi.',
            'pricing_subtitle' => 'Pilih paket yang sesuai dengan jumlah pelanggan bisnis jaringan Anda saat ini.',
        ];
    }

    /**
     * Paket yang bisa dibayar lewat unggah bukti (bukan WA sales).
     *
     * @return list<string>
     */
    public static function paidCheckoutKeys(): array
    {
        return ['starter', 'pro', 'enterprise'];
    }

    /**
     * @return array{pricing_eyebrow: string, pricing_title: string, pricing_subtitle: string}
     */
    public static function landingHeadlines(): array
    {
        $defaults = self::defaultLandingHeadlines();
        $raw = PlatformSetting::get()->subscription_landing_headlines;
        if (! is_array($raw)) {
            return $defaults;
        }

        $out = $defaults;
        foreach (array_keys($defaults) as $key) {
            if (is_string($raw[$key] ?? null) && trim($raw[$key]) !== '') {
                $out[$key] = trim($raw[$key]);
            }
        }

        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $meta = PlatformSetting::get()->subscription_packages_meta;
        if (! is_array($meta)) {
            $meta = [];
        }

        $out = [];
        foreach (self::defaultPackageMeta() as $key => $base) {
            $row = $base;
            if (isset($meta[$key]) && is_array($meta[$key])) {
                $row = array_merge($row, array_intersect_key($meta[$key], array_flip(array_keys($base))));
            }

            $cfg = config('mikpay.subscription_packages.'.$key, []);
            if (is_array($cfg)) {
                foreach (['max_customers', 'max_routers', 'price', 'duration'] as $k) {
                    if (array_key_exists($k, $cfg) && $cfg[$k] !== null && $cfg[$k] !== '') {
                        $row[$k] = $cfg[$k];
                    }
                }
            }

            if (! is_array($row['features'] ?? null) || $row['features'] === []) {
                $row['features'] = $base['features'];
            } else {
                $row['features'] = array_values(array_filter(
                    array_map(static fn ($l) => is_string($l) ? trim($l) : '', $row['features']),
                    static fn ($l) => $l !== ''
                ));
            }

            $price = (int) ($row['price'] ?? 0);
            $orig = isset($row['original_price']) && $row['original_price'] !== null && $row['original_price'] !== ''
                ? (int) $row['original_price']
                : null;
            $row['discount'] = ($orig !== null && $orig > $price) ? ($orig - $price) : null;
            $row['key'] = $key;
            $out[$key] = $row;
        }

        return $out;
    }

    public static function get(string $key): ?array
    {
        $all = self::all();

        return $all[$key] ?? null;
    }
}
