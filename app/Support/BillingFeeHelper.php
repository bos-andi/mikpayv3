<?php

namespace App\Support;

use App\Models\BillingPeriodFee;

class BillingFeeHelper
{
    /**
     * Harga tagihan untuk satu periode: override (harga akhir) atau tarif normal.
     */
    public static function feeForPeriod(
        int $routerId,
        string $customerName,
        string $period,
        int $defaultMonthlyFee,
        ?array $overridesMap = null,
    ): int {
        $map = $overridesMap ?? self::overridesMap($routerId, $customerName, [$period]);

        if (array_key_exists($period, $map)) {
            return max(0, (int) $map[$period]);
        }

        return max(0, $defaultMonthlyFee);
    }

    /**
     * @param  list<string>|null  $periods
     * @return array<string, int> period => amount
     */
    public static function overridesMap(int $routerId, string $customerName, ?array $periods = null): array
    {
        $q = BillingPeriodFee::query()
            ->where('router_id', $routerId)
            ->where('customer_name', $customerName);

        if ($periods !== null) {
            if ($periods === []) {
                return [];
            }
            $q->whereIn('period', $periods);
        }

        /** @var array<string, int|string> $raw */
        $raw = $q->pluck('amount', 'period')->all();

        $out = [];
        foreach ($raw as $period => $amount) {
            $out[(string) $period] = max(0, (int) $amount);
        }

        return $out;
    }

    public static function hasOverride(int $routerId, string $customerName, string $period): bool
    {
        return BillingPeriodFee::query()
            ->where('router_id', $routerId)
            ->where('customer_name', $customerName)
            ->where('period', $period)
            ->exists();
    }
}
