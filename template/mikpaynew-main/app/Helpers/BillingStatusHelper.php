<?php

namespace App\Helpers;

use App\Models\BillingPayment;
use Carbon\Carbon;

class BillingStatusHelper
{
    /**
     * Hitung status tagihan pelanggan (sesuai template billing-data.php).
     *
     * @param  string  $customerName  Nama pelanggan (PPP username)
     * @param  int  $dueDay  Tanggal jatuh tempo (1-31), 0 = belum diatur
     * @return array{
     *   status: string,
     *   status_label: string,
     *   status_class: string,
     *   days_to_due: int|null,
     *   current_period: string,
     *   due_date: string|null,
     *   original_due_date: string|null,
     *   is_promised: bool,
     *   promised_pay_date: string|null,
     *   message: string
     * }
     */
    public static function getStatus(string $customerName, int $dueDay, ?string $promisedPayDate = null): array
    {
        $today = now();
        $currentPeriod = $today->format('Y-m');

        if ($dueDay <= 0 || $dueDay > 31) {
            return [
                'status' => 'not_set',
                'status_label' => 'Belum Diatur',
                'status_class' => 'not-set',
                'days_to_due' => null,
                'current_period' => $currentPeriod,
                'due_date' => null,
                'original_due_date' => null,
                'is_promised' => false,
                'promised_pay_date' => null,
                'message' => 'Tanggal jatuh tempo belum diatur',
            ];
        }

        $daysInMonth = (int) $today->format('t');
        $actualDueDay = min($dueDay, $daysInMonth);
        $dueDateThisMonth = $today->copy()->startOfMonth()->addDays($actualDueDay - 1);
        $effectiveDueDate = $dueDateThisMonth->copy();
        $promiseDate = null;
        if ($promisedPayDate) {
            try {
                $promiseDate = Carbon::parse($promisedPayDate)->startOfDay();
                $effectiveDueDate = $promiseDate->copy();
            } catch (\Throwable) {
                $promiseDate = null;
            }
        }
        $daysTodue = (int) $today->diffInDays($effectiveDueDate, false);

        $hasPaid = BillingPayment::where('customer_name', $customerName)
            ->where('period', $currentPeriod)
            ->where('status', 'paid')
            ->exists();

        if ($hasPaid) {
            return [
                'status' => 'paid',
                'status_label' => 'Lunas',
                'status_class' => 'paid',
                'days_to_due' => null,
                'current_period' => $currentPeriod,
                'due_date' => $dueDateThisMonth->format('Y-m-d'),
                'original_due_date' => $dueDateThisMonth->format('Y-m-d'),
                'is_promised' => false,
                'promised_pay_date' => null,
                'message' => 'Sudah lunas periode ini',
            ];
        }

        if ($daysTodue < 0) {
            $isPromised = $promiseDate !== null;
            return [
                'status' => 'overdue',
                'status_label' => $isPromised ? 'Janji Lewat' : 'Terlambat',
                'status_class' => 'overdue',
                'days_to_due' => $daysTodue,
                'current_period' => $currentPeriod,
                'due_date' => $effectiveDueDate->format('Y-m-d'),
                'original_due_date' => $dueDateThisMonth->format('Y-m-d'),
                'is_promised' => $isPromised,
                'promised_pay_date' => $promiseDate?->format('Y-m-d'),
                'message' => $isPromised
                    ? ('Janji bayar lewat ' . abs($daysTodue) . ' hari')
                    : ('Terlambat ' . abs($daysTodue) . ' hari'),
            ];
        }
        if ($daysTodue === 0) {
            $isPromised = $promiseDate !== null;
            return [
                'status' => 'due_today',
                'status_label' => $isPromised ? 'Janji Hari Ini' : 'Hari Ini',
                'status_class' => 'due-today',
                'days_to_due' => 0,
                'current_period' => $currentPeriod,
                'due_date' => $effectiveDueDate->format('Y-m-d'),
                'original_due_date' => $dueDateThisMonth->format('Y-m-d'),
                'is_promised' => $isPromised,
                'promised_pay_date' => $promiseDate?->format('Y-m-d'),
                'message' => $isPromised ? 'Janji bayar hari ini!' : 'Jatuh tempo hari ini!',
            ];
        }
        if ($daysTodue <= 3) {
            $isPromised = $promiseDate !== null;
            return [
                'status' => 'due_soon',
                'status_label' => $isPromised ? ('Janji ' . $daysTodue . ' Hari') : ($daysTodue . ' Hari Lagi'),
                'status_class' => 'due-soon',
                'days_to_due' => $daysTodue,
                'current_period' => $currentPeriod,
                'due_date' => $effectiveDueDate->format('Y-m-d'),
                'original_due_date' => $dueDateThisMonth->format('Y-m-d'),
                'is_promised' => $isPromised,
                'promised_pay_date' => $promiseDate?->format('Y-m-d'),
                'message' => $isPromised
                    ? ('Janji bayar ' . $daysTodue . ' hari lagi')
                    : ('Jatuh tempo ' . $daysTodue . ' hari lagi'),
            ];
        }

        $isPromised = $promiseDate !== null;
        return [
            'status' => 'waiting',
            'status_label' => $isPromised ? ('Janji ' . $daysTodue . ' Hari') : ($daysTodue . ' Hari'),
            'status_class' => 'waiting',
            'days_to_due' => $daysTodue,
            'current_period' => $currentPeriod,
            'due_date' => $effectiveDueDate->format('Y-m-d'),
            'original_due_date' => $dueDateThisMonth->format('Y-m-d'),
            'is_promised' => $isPromised,
            'promised_pay_date' => $promiseDate?->format('Y-m-d'),
            'message' => $isPromised
                ? ('Janji bayar: ' . $effectiveDueDate->format('d M Y'))
                : ('Jatuh tempo: ' . $dueDateThisMonth->format('d M Y')),
        ];
    }
}
