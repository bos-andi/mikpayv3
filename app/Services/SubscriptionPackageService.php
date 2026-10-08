<?php

namespace App\Services;

use App\Models\PlatformSetting;

/**
 * Satu paket MikPay (unlimited pelanggan, 1 router, 1 VPN remote) dengan pilihan durasi.
 * Setiap durasi disimpan sebagai "paket" tersendiri supaya alur saldo/approve tetap sama.
 */
class SubscriptionPackageService
{
    /** @var array<string, string> */
    protected const LEGACY_LABELS = [
        'trial' => 'Trial',
        'starter' => 'Starter (lama)',
        'pro' => 'Pro ISP (lama)',
        'enterprise' => 'Ultimate ISP (lama)',
        'tunnel' => 'Tunnel',
    ];

    /**
     * @return list<string>
     */
    public static function defaultFeatures(): array
    {
        return [
            'Unlimited Pelanggan',
            '1 Router MikroTik',
            '1 Akun VPN Remote (OpenVPN) Gratis',
            'Remote Winbox',
            'Remote API MikroTik',
            'Remote Web OLT',
            'Remote Web Router / Modem Client',
            'Auto Isolir & Pengingat WhatsApp',
            'Hotspot Voucher Generator',
            'Peta ODP & GIS',
            'Laporan Keuangan',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaultPackageMeta(): array
    {
        $features = self::defaultFeatures();
        $trialLabel = 'Mulai Coba Gratis '.(int) config('mikpay.trial_days', 4).' Hari';

        return [
            'bulan1' => [
                'key' => 'bulan1',
                'name' => '1 Bulan',
                'months' => 1,
                'price' => 35000,
                'subtitle' => 'Fleksibel, bayar per bulan.',
                'featured' => false,
                'featured_ribbon' => null,
                'button_label' => $trialLabel,
                'features' => $features,
            ],
            'bulan3' => [
                'key' => 'bulan3',
                'name' => '3 Bulan',
                'months' => 3,
                'price' => 80000,
                'subtitle' => 'Pilihan favorit pengguna MikPay.',
                'featured' => true,
                'featured_ribbon' => 'PALING POPULER',
                'button_label' => $trialLabel,
                'features' => $features,
            ],
            'bulan6' => [
                'key' => 'bulan6',
                'name' => '6 Bulan',
                'months' => 6,
                'price' => 155000,
                'subtitle' => 'Paling hemat untuk jangka panjang.',
                'featured' => false,
                'featured_ribbon' => 'PALING HEMAT',
                'button_label' => $trialLabel,
                'features' => $features,
            ],
        ];
    }

    /**
     * @return array{pricing_eyebrow: string, pricing_title: string, pricing_subtitle: string}
     */
    public static function defaultLandingHeadlines(): array
    {
        return [
            'pricing_eyebrow' => 'Harga Simpel',
            'pricing_title' => 'Satu Paket, Semua Fitur, Unlimited Pelanggan.',
            'pricing_subtitle' => 'Tanpa batas jumlah pelanggan. Pilih durasi langganan — makin panjang makin hemat.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function paidCheckoutKeys(): array
    {
        return array_keys(self::defaultPackageMeta());
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

        $editable = ['name', 'price', 'subtitle', 'featured', 'featured_ribbon', 'button_label', 'features'];
        $rows = [];
        foreach (self::defaultPackageMeta() as $key => $base) {
            $row = $base;
            if (isset($meta[$key]) && is_array($meta[$key])) {
                $row = array_merge($row, array_intersect_key($meta[$key], array_flip($editable)));
            }

            $features = is_array($row['features'] ?? null)
                ? array_values(array_filter(
                    array_map(static fn ($l) => is_string($l) ? trim($l) : '', $row['features']),
                    static fn ($l) => $l !== ''
                ))
                : [];
            $row['features'] = $features !== [] ? $features : $base['features'];
            $row['price'] = max(0, (int) ($row['price'] ?? 0));
            $rows[$key] = $row;
        }

        $baseMonthly = (int) ($rows['bulan1']['price'] ?? 0);
        foreach ($rows as $key => $row) {
            $rows[$key] = self::withComputedPricing($row, $baseMonthly);
        }

        return $rows;
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Fitur yang sama untuk semua durasi (diambil dari paket 1 bulan).
     *
     * @return list<string>
     */
    public static function sharedFeatures(): array
    {
        return self::all()['bulan1']['features'] ?? self::defaultFeatures();
    }

    public static function planLabel(?string $plan): string
    {
        $plan = (string) $plan;
        if ($plan === '') {
            return '—';
        }

        $pkg = self::get($plan);
        if ($pkg) {
            return 'MikPay '.$pkg['name'];
        }

        return self::LEGACY_LABELS[$plan] ?? ucfirst($plan);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected static function withComputedPricing(array $row, int $baseMonthly): array
    {
        $months = max(1, (int) ($row['months'] ?? 1));
        $price = (int) $row['price'];
        $normal = $baseMonthly * $months;
        $savings = ($months > 1 && $normal > $price) ? $normal - $price : 0;

        $row['months'] = $months;
        $row['duration'] = $months * 30;
        $row['duration_label'] = $months === 1 ? '/ bulan' : "/ {$months} bulan";
        $row['monthly_equivalent'] = (int) round($price / $months);
        $row['normal_price'] = $savings > 0 ? $normal : null;
        $row['savings'] = $savings;
        $row['savings_percent'] = $savings > 0 ? (int) round($savings / $normal * 100) : 0;

        return $row;
    }
}
