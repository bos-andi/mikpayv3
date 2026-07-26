<?php

namespace App\Support;

use App\Models\BillingPayment;
use Carbon\Carbon;

class BillingArrearsHelper
{
    public static function hasExplicitTrackingStart(): bool
    {
        $v = config('mikpay.billing.tracking_start_date');

        return $v !== null && $v !== '';
    }

    /**
     * Bulan pertama yang masuk hitungan tunggakan (global).
     * Fallback: bulan berjalan (aman, tidak menjejak tahun lama).
     */
    public static function resolveTrackingStartMonth(): Carbon
    {
        static $cached = null;
        if ($cached instanceof Carbon) {
            return $cached->copy();
        }

        $end = now()->startOfMonth();
        $raw = config('mikpay.billing.tracking_start_date');

        if ($raw === null || $raw === '') {
            $cached = $end->copy();

            return $cached->copy();
        }

        try {
            $start = Carbon::parse((string) $raw)->startOfMonth();
        } catch (\Throwable) {
            $cached = $end->copy();

            return $cached->copy();
        }

        if ($start->gt($end)) {
            $start = $end->copy();
        }

        $minStart = $end->copy()->subMonthsNoOverflow(59);
        if ($start->lt($minStart)) {
            $start = $minStart->copy();
        }

        $cached = $start;

        return $cached->copy();
    }

    /**
     * @return array{
     *   unpaid_periods: list<string>,
     *   unpaid_count: int,
     *   unpaid_past_count: int,
     *   unpaid_current: bool,
     *   outstanding_total: int,
     *   shortfall_by_period: array<string, int>,
     *   label: string,
     *   tooltip: string,
     *   period_labels: list<string>,
     *   should_isolir: bool,
     *   should_open: bool,
     *   days_to_isolir: int|null,
     *   isolir_date: string|null,
     *   isolir_label: string,
     *   needs_tracking_config_hint: bool
     * }
     */
    public static function getUnpaidPeriodsSummary(
        string $customerName,
        int $dueDay,
        int $monthlyFee,
        int $routerId,
        mixed $startedAt = null,
        int $graceDays = 0,
    ): array {
        $empty = [
            'unpaid_periods' => [],
            'unpaid_count' => 0,
            'unpaid_past_count' => 0,
            'unpaid_current' => false,
            'outstanding_total' => 0,
            'shortfall_by_period' => [],
            'label' => '—',
            'tooltip' => '',
            'period_labels' => [],
            'should_isolir' => false,
            'should_open' => false,
            'days_to_isolir' => null,
            'isolir_date' => null,
            'isolir_label' => '',
            'needs_tracking_config_hint' => ! self::hasExplicitTrackingStart(),
        ];

        if ($dueDay <= 0 || $dueDay > 31) {
            return $empty;
        }

        $end = now()->startOfMonth();
        $currentYm = $end->format('Y-m');

        // Tagihan dimulai dari bulan daftar pelanggan (started_at).
        // Tanpa started_at → hanya bulan berjalan (pelanggan baru tidak kena tunggakan mundur).
        // Global tracking_start hanya sebagai lantai sistem (tidak boleh lebih awal dari go-live).
        $start = $end->copy();
        if ($startedAt) {
            try {
                $start = Carbon::parse($startedAt)->startOfMonth();
            } catch (\Throwable) {
                $start = $end->copy();
            }
        }

        $trackingFloor = self::resolveTrackingStartMonth();
        if ($start->lt($trackingFloor)) {
            $start = $trackingFloor;
        }
        if ($start->gt($end)) {
            $start = $end->copy();
        }

        $unpaid = [];
        $shortfallByPeriod = [];
        $outstandingTotal = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $period = $cursor->format('Y-m');
            $paidAmount = (int) BillingPayment::query()
                ->where('router_id', $routerId)
                ->where('customer_name', $customerName)
                ->where('period', $period)
                ->sum('amount');

            if ($monthlyFee > 0) {
                if ($paidAmount < $monthlyFee) {
                    $shortfall = $monthlyFee - $paidAmount;
                    $unpaid[] = $period;
                    $shortfallByPeriod[$period] = $shortfall;
                    $outstandingTotal += $shortfall;
                }
            } else {
                $paid = BillingPayment::query()
                    ->where('router_id', $routerId)
                    ->where('customer_name', $customerName)
                    ->where('period', $period)
                    ->where('status', 'paid')
                    ->exists();
                if (! $paid) {
                    $unpaid[] = $period;
                }
            }

            $cursor->addMonthNoOverflow();
        }

        $unpaidPast = array_values(array_filter($unpaid, fn ($p) => $p < $currentYm));
        $unpaidCurrent = in_array($currentYm, $unpaid, true);
        $count = count($unpaid);
        $pastCount = count($unpaidPast);

        $periodLabels = array_map(
            fn ($p) => Carbon::parse($p.'-01')->translatedFormat('M Y'),
            $unpaid
        );

        $tooltipParts = [];
        foreach ($unpaid as $i => $p) {
            $label = $periodLabels[$i] ?? $p;
            if (isset($shortfallByPeriod[$p])) {
                $tooltipParts[] = $label.' (kurang Rp '.number_format((int) $shortfallByPeriod[$p], 0, ',', '.').')';
            } else {
                $tooltipParts[] = $label;
            }
        }
        $tooltip = implode(', ', $tooltipParts);

        if ($count === 0) {
            $label = 'Lunas';
        } elseif ($monthlyFee > 0 && $outstandingTotal > 0) {
            $label = $pastCount > 0
                ? $pastCount.' bln · Rp '.number_format($outstandingTotal, 0, ',', '.')
                : 'Kurang Rp '.number_format($outstandingTotal, 0, ',', '.');
        } elseif ($pastCount > 0 && $unpaidCurrent) {
            $label = $pastCount.' bln lalu + bln ini';
        } elseif ($pastCount > 0) {
            $label = $pastCount.' bulan menunggak';
        } else {
            $label = 'Bulan ini belum lunas';
        }

        // Auto isolir: ada tunggakan bulan lalu, ATAU bulan ini sudah lewat due_day (+ grace) dan belum lunas.
        $shouldIsolir = false;
        $daysToIsolir = null;
        $isolirDate = null;
        $isolirLabel = '';

        if ($pastCount > 0) {
            $shouldIsolir = true;
            $daysToIsolir = 0;
            $isolirDate = now()->format('Y-m-d');
            $isolirLabel = 'Siap isolir (ada tunggakan bulan lalu)';
        } elseif ($unpaidCurrent) {
            $daysInMonth = (int) now()->format('t');
            $actualDueDay = min($dueDay, $daysInMonth);
            $dueDate = now()->copy()->startOfMonth()->addDays($actualDueDay - 1)->startOfDay();
            $cutoff = $dueDate->copy()->addDays(max(0, $graceDays));
            // Isolir mulai sehari setelah cutoff (sama dengan should_isolir: today > cutoff).
            $isolirOn = $cutoff->copy()->addDay()->startOfDay();
            $daysToIsolir = (int) now()->startOfDay()->diffInDays($isolirOn, false);
            $isolirDate = $isolirOn->format('Y-m-d');

            if (now()->startOfDay()->gt($cutoff)) {
                $shouldIsolir = true;
            }

            if ($daysToIsolir > 0) {
                $isolirLabel = $daysToIsolir.' hari lagi siap isolir';
            } elseif ($daysToIsolir === 0) {
                $isolirLabel = 'Siap isolir hari ini';
            } else {
                $isolirLabel = 'Lewat masa tenggang — dapat diisolir';
            }
        }

        // Buka isolir: tidak ada tunggakan past, dan bulan berjalan lunas ATAU belum jatuh tempo.
        $shouldOpen = false;
        if ($pastCount === 0) {
            if (! $unpaidCurrent) {
                $shouldOpen = true;
            } else {
                $daysInMonth = (int) now()->format('t');
                $actualDueDay = min($dueDay, $daysInMonth);
                $dueDate = now()->copy()->startOfMonth()->addDays($actualDueDay - 1)->startOfDay();
                if (now()->startOfDay()->lte($dueDate)) {
                    $shouldOpen = true;
                }
            }
        }

        return [
            'unpaid_periods' => $unpaid,
            'unpaid_count' => $count,
            'unpaid_past_count' => $pastCount,
            'unpaid_current' => $unpaidCurrent,
            'outstanding_total' => $outstandingTotal,
            'shortfall_by_period' => $shortfallByPeriod,
            'label' => $label,
            'tooltip' => $tooltip,
            'period_labels' => $periodLabels,
            'should_isolir' => $shouldIsolir,
            'should_open' => $shouldOpen,
            'days_to_isolir' => $daysToIsolir,
            'isolir_date' => $isolirDate,
            'isolir_label' => $isolirLabel,
            'needs_tracking_config_hint' => ! self::hasExplicitTrackingStart(),
        ];
    }
}
