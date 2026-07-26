<?php

namespace App\Helpers;

use App\Models\BillingPayment;
use App\Models\BillingCustomer;
use App\Models\BusinessSetting;
use Carbon\Carbon;

class BillingArrearsHelper
{
    /**
     * Apakah tanggal mulai pencatatan sudah di-set (DB atau env).
     */
    public static function hasExplicitTrackingStart(): bool
    {
        $row = BusinessSetting::query()->first();
        if ($row && $row->billing_tracking_start_date) {
            return true;
        }

        return self::envTrackingRaw() !== null;
    }

    private static function envTrackingRaw(): ?string
    {
        $v = config('billing.tracking_start_date');
        if ($v === null || $v === '') {
            return null;
        }

        return (string) $v;
    }

    /**
     * Bulan pertama yang masuk hitungan tunggakan (awal pencatatan / install aplikasi).
     * Di-cache per request (static).
     */
    public static function resolveTrackingStartMonth(): Carbon
    {
        static $cached = null;
        if ($cached instanceof Carbon) {
            return $cached->copy();
        }

        $end = now()->startOfMonth();

        $raw = null;
        $row = BusinessSetting::query()->first();
        if ($row && $row->billing_tracking_start_date) {
            $raw = $row->billing_tracking_start_date;
        }
        if ($raw === null) {
            $env = self::envTrackingRaw();
            if ($env !== null) {
                $raw = $env;
            }
        }

        if ($raw === null) {
            // Belum dikonfigurasi: hanya bulan berjalan (aman, tidak menjejak tahun 2023, dll.)
            $cached = $end->copy();

            return $cached->copy();
        }

        try {
            $start = Carbon::parse($raw)->startOfMonth();
        } catch (\Throwable $e) {
            $cached = $end->copy();

            return $cached->copy();
        }

        if ($start->gt($end)) {
            $start = $end->copy();
        }

        // Batasi ke belakang (maks. 60 bulan dari sekarang)
        $minStart = $end->copy()->subMonthsNoOverflow(59);
        if ($start->lt($minStart)) {
            $start = $minStart->copy();
        }

        $cached = $start;

        return $cached->copy();
    }

    /**
     * Daftar periode (Y-m) mulai dari bulan awal pencatatan aplikasi sampai bulan berjalan,
     * yang belum ada pembayaran status paid.
     *
     * Perhitungan bulan awal tunggakan memakai batas gabungan:
     * 1) tracking start global (business_settings.billing_tracking_start_date / env), dan
     * 2) started_at milik masing-masing pelanggan (billing_customers.started_at).
     *
     * @return array{
     *   unpaid_periods: list<string>,
     *   unpaid_count: int,
     *   unpaid_past_count: int,
     *   unpaid_current: bool,
     *   label: string,
     *   tooltip: string,
     *   needs_tracking_config_hint: bool
     * }
     */
    public static function getUnpaidPeriodsSummary(
        string $customerName,
        int $dueDay,
        int $monthlyFee = 0,
        ?string $routerSession = null
    ): array
    {
        $empty = [
            'unpaid_periods' => [],
            'unpaid_count' => 0,
            'unpaid_past_count' => 0,
            'unpaid_current' => false,
            'outstanding_total' => 0,
            'label' => '—',
            'tooltip' => '',
            'needs_tracking_config_hint' => ! self::hasExplicitTrackingStart(),
        ];

        if ($dueDay <= 0 || $dueDay > 31) {
            return $empty;
        }

        $end = now()->startOfMonth();
        $currentYm = $end->format('Y-m');

        $start = self::resolveTrackingStartMonth();

        // Batasi hitungan tunggakan mulai bulan pelanggan mulai berlangganan.
        // Contoh problem yang kamu sebut:
        // - tracking start: Jan 2026
        // - pelanggan started_at: Mar 2026
        // => Jan-Feb tidak boleh dianggap tunggakan.
        $customerStart = BillingCustomer::query()
            ->where('customer_name', $customerName)
            ->value('started_at');

        if ($customerStart) {
            try {
                $customerStartMonth = Carbon::parse($customerStart)->startOfMonth();
                if ($customerStartMonth->gt($start)) {
                    $start = $customerStartMonth;
                }
            } catch (\Throwable $e) {
                // Jika parsing gagal, fallback ke tracking start global.
            }
        }

        $unpaid = [];
        $shortfallByPeriod = [];
        $outstandingTotal = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $period = $cursor->format('Y-m');
            $paymentQuery = BillingPayment::query()
                ->where('customer_name', $customerName)
                ->where('period', $period);

            if ($routerSession !== null && $routerSession !== '') {
                $paymentQuery->where('router_session', $routerSession);
            }

            $paidAmount = (int) $paymentQuery->sum('amount');

            if ($monthlyFee > 0) {
                if ($paidAmount < $monthlyFee) {
                    $shortfall = $monthlyFee - $paidAmount;
                    $unpaid[] = $period;
                    $shortfallByPeriod[$period] = $shortfall;
                    $outstandingTotal += $shortfall;
                }
            } else {
                $paid = (clone $paymentQuery)->where('status', 'paid')->exists();
                if (! $paid) {
                    $unpaid[] = $period;
                }
            }
            $cursor->addMonth();
        }

        $unpaidPast = array_values(array_filter($unpaid, fn ($p) => $p < $currentYm));
        $unpaidCurrent = in_array($currentYm, $unpaid, true);
        $count = count($unpaid);
        $pastCount = count($unpaidPast);

        $tooltipParts = array_map(function ($p) use ($shortfallByPeriod) {
            $label = Carbon::parse($p.'-01')->format('M Y');
            if (isset($shortfallByPeriod[$p])) {
                return $label . ' (kurang Rp ' . number_format((int) $shortfallByPeriod[$p], 0, ',', '.') . ')';
            }

            return $label;
        }, $unpaid);
        $tooltip = $tooltipParts !== [] ? implode(', ', $tooltipParts) : '';

        if ($count === 0) {
            $label = 'Lunas';
        } elseif ($monthlyFee > 0 && $outstandingTotal > 0) {
            $label = 'Kurang Rp ' . number_format($outstandingTotal, 0, ',', '.');
        } elseif ($pastCount > 0 && $unpaidCurrent) {
            $label = $pastCount.' bln lalu + bln ini';
        } elseif ($pastCount > 0) {
            $label = $pastCount.' bulan menunggak';
        } else {
            $label = 'Bulan ini belum lunas';
        }

        return [
            'unpaid_periods' => $unpaid,
            'unpaid_count' => $count,
            'unpaid_past_count' => $pastCount,
            'unpaid_current' => $unpaidCurrent,
            'outstanding_total' => $outstandingTotal,
            'label' => $label,
            'tooltip' => $tooltip,
            'needs_tracking_config_hint' => ! self::hasExplicitTrackingStart(),
        ];
    }
}
