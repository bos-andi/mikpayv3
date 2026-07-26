<?php

namespace App\Support;

use App\Models\BillingPayment;
use Carbon\Carbon;

class BillingStatusHelper
{
    /**
     * Hitung status tagihan periode berjalan untuk satu pelanggan di router tertentu.
     *
     * @return array{
     *   status: string,
     *   status_label: string,
     *   days_to_due: int|null,
     *   current_period: string,
     *   due_date: string|null,
     *   message: string,
     *   paid_amount: int
     * }
     */
    public static function getStatus(string $customerName, int $dueDay, int $routerId, ?int $monthlyFee = null): array
    {
        $today = now();
        $currentPeriod = $today->format('Y-m');

        $paidAmount = (int) BillingPayment::query()
            ->where('router_id', $routerId)
            ->where('customer_name', $customerName)
            ->where('period', $currentPeriod)
            ->sum('amount');

        if ($dueDay <= 0 || $dueDay > 31) {
            return [
                'status' => 'not_set',
                'status_label' => 'Belum Diatur',
                'days_to_due' => null,
                'current_period' => $currentPeriod,
                'due_date' => null,
                'message' => 'Tanggal jatuh tempo belum diatur',
                'paid_amount' => $paidAmount,
            ];
        }

        $daysInMonth = (int) $today->format('t');
        $actualDueDay = min($dueDay, $daysInMonth);
        $dueDateThisMonth = $today->copy()->startOfMonth()->addDays($actualDueDay - 1);
        $daysToDue = (int) $today->diffInDays($dueDateThisMonth, false);

        $fee = (int) ($monthlyFee ?? 0);
        $isFullyPaid = $fee > 0
            ? $paidAmount >= $fee
            : BillingPayment::query()
                ->where('router_id', $routerId)
                ->where('customer_name', $customerName)
                ->where('period', $currentPeriod)
                ->where('status', 'paid')
                ->exists();

        if ($isFullyPaid) {
            return [
                'status' => 'paid',
                'status_label' => 'Lunas',
                'days_to_due' => null,
                'current_period' => $currentPeriod,
                'due_date' => $dueDateThisMonth->format('Y-m-d'),
                'message' => 'Sudah lunas periode ini',
                'paid_amount' => $paidAmount,
            ];
        }

        if ($paidAmount > 0 && $fee > 0 && $paidAmount < $fee) {
            return [
                'status' => 'partial',
                'status_label' => 'Sebagian',
                'days_to_due' => $daysToDue,
                'current_period' => $currentPeriod,
                'due_date' => $dueDateThisMonth->format('Y-m-d'),
                'message' => 'Terbayar sebagian periode ini',
                'paid_amount' => $paidAmount,
            ];
        }

        if ($daysToDue < 0) {
            return [
                'status' => 'overdue',
                'status_label' => 'Terlambat',
                'days_to_due' => $daysToDue,
                'current_period' => $currentPeriod,
                'due_date' => $dueDateThisMonth->format('Y-m-d'),
                'message' => 'Terlambat '.abs($daysToDue).' hari',
                'paid_amount' => $paidAmount,
            ];
        }

        if ($daysToDue === 0) {
            return [
                'status' => 'due_today',
                'status_label' => 'Hari Ini',
                'days_to_due' => 0,
                'current_period' => $currentPeriod,
                'due_date' => $dueDateThisMonth->format('Y-m-d'),
                'message' => 'Jatuh tempo hari ini',
                'paid_amount' => $paidAmount,
            ];
        }

        if ($daysToDue <= 3) {
            return [
                'status' => 'due_soon',
                'status_label' => $daysToDue.' Hari Lagi',
                'days_to_due' => $daysToDue,
                'current_period' => $currentPeriod,
                'due_date' => $dueDateThisMonth->format('Y-m-d'),
                'message' => 'Jatuh tempo '.$daysToDue.' hari lagi',
                'paid_amount' => $paidAmount,
            ];
        }

        return [
            'status' => 'waiting',
            'status_label' => $daysToDue.' Hari',
            'days_to_due' => $daysToDue,
            'current_period' => $currentPeriod,
            'due_date' => $dueDateThisMonth->format('Y-m-d'),
            'message' => 'Jatuh tempo: '.$dueDateThisMonth->format('d M Y'),
            'paid_amount' => $paidAmount,
        ];
    }

    public static function formatInvoiceId(string $period, int $id): string
    {
        $yyyymm = preg_replace('/[^0-9]/', '', $period);
        $yyyymm = str_pad((string) $yyyymm, 6, '0', STR_PAD_LEFT);

        return 'INV-'.$yyyymm.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    public static function formatDueDate(?string $ymd): ?string
    {
        if (! $ymd) {
            return null;
        }

        try {
            return Carbon::parse($ymd)->translatedFormat('d M Y');
        } catch (\Throwable) {
            return $ymd;
        }
    }
}
