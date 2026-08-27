<?php

namespace App\Support;

use App\Models\BillingPayment;
use App\Models\BillingPeriodPromise;
use Carbon\Carbon;

class BillingArrearsHelper
{
    public static function hasExplicitTrackingStart(): bool
    {
        $v = config('mikpay.billing.tracking_start_date');

        return $v !== null && $v !== '';
    }

    /**
     * Lantai global go-live billing (opsional).
     * null = tidak memaksa lantai; dipakai started_at pelanggan / bulan berjalan.
     */
    public static function resolveTrackingStartMonth(): ?Carbon
    {
        static $resolved = false;
        static $cached = null;

        if ($resolved) {
            return $cached instanceof Carbon ? $cached->copy() : null;
        }
        $resolved = true;

        $end = now()->startOfMonth();
        $raw = config('mikpay.billing.tracking_start_date');

        if ($raw === null || $raw === '') {
            $cached = null;

            return null;
        }

        try {
            $start = Carbon::parse((string) $raw)->startOfMonth();
        } catch (\Throwable) {
            $cached = null;

            return null;
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
     *   fee_by_period: array<string, int>,
     *   label: string,
     *   tooltip: string,
     *   period_labels: list<string>,
     *   should_isolir: bool,
     *   should_open: bool,
     *   days_to_isolir: int|null,
     *   isolir_date: string|null,
     *   isolir_label: string,
     *   needs_tracking_config_hint: bool,
     *   is_promised: bool,
     *   promised_pay_date: string|null,
     *   promised_pay_note: string|null,
     *   promise_period: string|null,
     *   promise_active: bool,
     *   promise_overdue: bool
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
            'fee_by_period' => [],
            'label' => '—',
            'tooltip' => '',
            'period_labels' => [],
            'should_isolir' => false,
            'should_open' => false,
            'days_to_isolir' => null,
            'isolir_date' => null,
            'isolir_label' => '',
            'needs_tracking_config_hint' => ! self::hasExplicitTrackingStart(),
            'is_promised' => false,
            'promised_pay_date' => null,
            'promised_pay_note' => null,
            'promise_period' => null,
            'promise_active' => false,
            'promise_overdue' => false,
        ];

        if ($dueDay <= 0 || $dueDay > 31) {
            return $empty;
        }

        $end = now()->startOfMonth();
        $currentYm = $end->format('Y-m');

        // Tagihan dimulai dari bulan daftar pelanggan (started_at).
        // Tanpa started_at → hanya bulan berjalan (pelanggan baru tidak kena tunggakan mundur).
        // Global tracking_start (opsional) = lantai go-live agar tidak menagih mundur bertahun-tahun.
        $start = $end->copy();
        if ($startedAt) {
            try {
                $start = Carbon::parse($startedAt)->startOfMonth();
            } catch (\Throwable) {
                $start = $end->copy();
            }
        }

        $trackingFloor = self::resolveTrackingStartMonth();
        if ($trackingFloor instanceof Carbon && $start->lt($trackingFloor)) {
            $start = $trackingFloor->copy();
        }
        if ($start->gt($end)) {
            $start = $end->copy();
        }

        // Batas aman: maksimal 59 bulan ke belakang.
        $minStart = $end->copy()->subMonthsNoOverflow(59);
        if ($start->lt($minStart)) {
            $start = $minStart->copy();
        }

        $unpaid = [];
        $shortfallByPeriod = [];
        $feeByPeriod = [];
        $outstandingTotal = 0;
        $cursor = $start->copy();
        $feeOverrides = BillingFeeHelper::overridesMap($routerId, $customerName);

        while ($cursor->lte($end)) {
            $period = $cursor->format('Y-m');
            $periodFee = BillingFeeHelper::feeForPeriod(
                $routerId,
                $customerName,
                $period,
                $monthlyFee,
                $feeOverrides
            );
            $feeByPeriod[$period] = $periodFee;

            $paidAmount = (int) BillingPayment::query()
                ->where('router_id', $routerId)
                ->where('customer_name', $customerName)
                ->where('period', $period)
                ->sum('amount');

            if ($periodFee > 0) {
                if ($paidAmount < $periodFee) {
                    $shortfall = $periodFee - $paidAmount;
                    $unpaid[] = $period;
                    $shortfallByPeriod[$period] = $shortfall;
                    $outstandingTotal += $shortfall;
                }
            } else {
                // Harga akhir 0 = gratis periode ini (anggap lunas). Tanpa override & tarif 0 → cek status paid lama.
                if (array_key_exists($period, $feeOverrides)) {
                    // gratis — skip
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
        } elseif ($outstandingTotal > 0) {
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

        // Janji bayar: tahan isolir sampai tanggal janji (inklusif).
        $isPromised = false;
        $promisedPayDate = null;
        $promisedPayNote = null;
        $promisePeriod = null;
        $promiseActive = false;
        $promiseOverdue = false;

        if ($count > 0) {
            $promise = BillingPeriodPromise::query()
                ->where('router_id', $routerId)
                ->where('customer_name', $customerName)
                ->whereIn('period', $unpaid)
                ->orderByDesc('promised_pay_date')
                ->first();

            if ($promise?->promised_pay_date) {
                $isPromised = true;
                $promisedPayDate = $promise->promised_pay_date->format('Y-m-d');
                $promisedPayNote = $promise->promised_pay_note;
                $promisePeriod = $promise->period;
                $promiseDay = $promise->promised_pay_date->copy()->startOfDay();
                $today = now()->startOfDay();
                $promiseLabel = $promiseDay->translatedFormat('d M');

                if ($today->lte($promiseDay)) {
                    $promiseActive = true;
                    $shouldIsolir = false;
                    // Isolir mulai sehari setelah tanggal janji.
                    $isolirOn = $promiseDay->copy()->addDay();
                    $daysToIsolir = (int) $today->diffInDays($isolirOn, false);
                    $isolirDate = $isolirOn->format('Y-m-d');
                    if ($daysToIsolir > 0) {
                        $isolirLabel = 'Janji bayar '.$promiseLabel.' · '.$daysToIsolir.' hari lagi';
                    } else {
                        $isolirLabel = 'Janji bayar '.$promiseLabel.' · batas hari ini';
                    }
                } else {
                    $promiseOverdue = true;
                    $shouldIsolir = true;
                    $daysToIsolir = 0;
                    $isolirDate = $today->format('Y-m-d');
                    $isolirLabel = 'Janji lewat '.$promiseLabel.' — siap isolir';
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
            'fee_by_period' => $feeByPeriod,
            'label' => $label,
            'tooltip' => $tooltip,
            'period_labels' => $periodLabels,
            'should_isolir' => $shouldIsolir,
            'should_open' => $shouldOpen,
            'days_to_isolir' => $daysToIsolir,
            'isolir_date' => $isolirDate,
            'isolir_label' => $isolirLabel,
            'needs_tracking_config_hint' => ! self::hasExplicitTrackingStart(),
            'is_promised' => $isPromised,
            'promised_pay_date' => $promisedPayDate,
            'promised_pay_note' => $promisedPayNote,
            'promise_period' => $promisePeriod,
            'promise_active' => $promiseActive,
            'promise_overdue' => $promiseOverdue,
        ];
    }

    /**
     * Hapus janji untuk periode yang sudah lunas (paid_amount >= monthly_fee).
     *
     * @param  list<string>  $periods
     */
    public static function clearPromisesForPaidPeriods(
        int $routerId,
        string $customerName,
        int $monthlyFee,
        array $periods,
    ): int {
        if ($periods === []) {
            return 0;
        }

        $overrides = BillingFeeHelper::overridesMap($routerId, $customerName, array_values(array_unique($periods)));
        $cleared = 0;
        foreach (array_unique($periods) as $period) {
            $fee = BillingFeeHelper::feeForPeriod($routerId, $customerName, (string) $period, $monthlyFee, $overrides);
            if ($fee <= 0) {
                // Periode gratis / tanpa tarif → hapus janji jika ada
                $cleared += BillingPeriodPromise::query()
                    ->where('router_id', $routerId)
                    ->where('customer_name', $customerName)
                    ->where('period', $period)
                    ->delete();
                continue;
            }

            $paid = (int) BillingPayment::query()
                ->where('router_id', $routerId)
                ->where('customer_name', $customerName)
                ->where('period', $period)
                ->sum('amount');

            if ($paid < $fee) {
                continue;
            }

            $cleared += BillingPeriodPromise::query()
                ->where('router_id', $routerId)
                ->where('customer_name', $customerName)
                ->where('period', $period)
                ->delete();
        }

        return $cleared;
    }
}
