<?php

namespace App\Support;

use App\Models\BillingPayment;
use Illuminate\Support\Collection;

class BillingPaymentAllocator
{
    /**
     * Alokasi nominal ke beberapa periode (FIFO).
     * Periode pertama dalam $periodsOrdered diisi dulu, sisanya ke berikutnya.
     *
     * @param  array{
     *   tenant_id: int,
     *   router_id: int,
     *   customer_name: string,
     *   display_name?: string|null,
     *   phone?: string|null,
     *   payment_date: string|\DateTimeInterface,
     *   payment_method: string,
     *   notes?: string|null
     * }  $base
     * @param  list<string>  $periodsOrdered  Format Y-m
     * @return Collection<int, BillingPayment>
     */
    public static function allocateManual(array $base, int $amount, int $monthlyFee, array $periodsOrdered): Collection
    {
        $budget = max(0, $amount);
        $created = collect();
        $userNotes = trim((string) ($base['notes'] ?? ''));

        if ($budget <= 0 || $periodsOrdered === []) {
            return $created;
        }

        foreach ($periodsOrdered as $period) {
            if ($budget <= 0) {
                break;
            }

            $already = (int) BillingPayment::query()
                ->where('router_id', $base['router_id'])
                ->where('customer_name', $base['customer_name'])
                ->where('period', $period)
                ->sum('amount');

            $need = $monthlyFee > 0 ? max(0, $monthlyFee - $already) : $budget;
            if ($need <= 0) {
                continue;
            }

            $pay = min($need, $budget);
            $after = $already + $pay;
            $status = ($monthlyFee > 0 && $after < $monthlyFee) ? 'partial' : 'paid';
            $remaining = $monthlyFee > 0 ? max(0, $monthlyFee - $after) : 0;

            $notes = $userNotes;
            if ($remaining > 0) {
                $carry = 'Sisa tagihan periode ini: Rp '.number_format($remaining, 0, ',', '.');
                $notes = $notes !== '' ? ($notes.' | '.$carry) : $carry;
            }

            $payment = BillingPayment::query()->create([
                'tenant_id' => $base['tenant_id'],
                'router_id' => $base['router_id'],
                'customer_name' => $base['customer_name'],
                'display_name' => $base['display_name'] ?? null,
                'phone' => $base['phone'] ?? null,
                'amount' => $pay,
                'remaining_amount' => $remaining,
                'period' => $period,
                'payment_date' => $base['payment_date'],
                'payment_method' => $base['payment_method'],
                'notes' => $notes !== '' ? $notes : null,
                'status' => $status,
            ]);

            $payment->invoice_id = BillingStatusHelper::formatInvoiceId($payment->period, (int) $payment->id);
            $payment->save();

            $created->push($payment);
            $budget -= $pay;
        }

        // Kelebihan setelah semua periode terisi → catat di periode pertama (overpay)
        if ($budget > 0) {
            $period = $periodsOrdered[0];
            $already = (int) BillingPayment::query()
                ->where('router_id', $base['router_id'])
                ->where('customer_name', $base['customer_name'])
                ->where('period', $period)
                ->sum('amount');
            $after = $already + $budget;
            $status = ($monthlyFee > 0 && $after < $monthlyFee) ? 'partial' : 'paid';
            $remaining = $monthlyFee > 0 ? max(0, $monthlyFee - $after) : 0;

            $notes = $userNotes !== '' ? ($userNotes.' | Kelebihan bayar') : 'Kelebihan bayar';

            $payment = BillingPayment::query()->create([
                'tenant_id' => $base['tenant_id'],
                'router_id' => $base['router_id'],
                'customer_name' => $base['customer_name'],
                'display_name' => $base['display_name'] ?? null,
                'phone' => $base['phone'] ?? null,
                'amount' => $budget,
                'remaining_amount' => $remaining,
                'period' => $period,
                'payment_date' => $base['payment_date'],
                'payment_method' => $base['payment_method'],
                'notes' => $notes,
                'status' => $status,
            ]);
            $payment->invoice_id = BillingStatusHelper::formatInvoiceId($payment->period, (int) $payment->id);
            $payment->save();
            $created->push($payment);
        }

        return $created;
    }

    /**
     * Urutan alokasi: periode dipilih dulu, lalu tunggakan lain (tertua → terbaru).
     *
     * @param  list<string>  $unpaidPeriods
     * @return list<string>
     */
    public static function orderedPeriods(string $selectedPeriod, array $unpaidPeriods): array
    {
        $others = array_values(array_filter(
            $unpaidPeriods,
            fn ($p) => $p !== $selectedPeriod
        ));
        sort($others);

        $ordered = [$selectedPeriod];
        foreach ($others as $p) {
            $ordered[] = $p;
        }

        return $ordered;
    }

    /**
     * Periode berurutan dari $startPeriod sebanyak $months (untuk bayar di muka).
     * Contoh: 2026-07 + 3 → [2026-07, 2026-08, 2026-09]
     *
     * @return list<string>
     */
    public static function periodsFromStart(string $startPeriod, int $months): array
    {
        $months = max(1, min(12, $months));
        try {
            $cursor = \Carbon\Carbon::createFromFormat('Y-m', $startPeriod)->startOfMonth();
        } catch (\Throwable) {
            $cursor = now()->startOfMonth();
        }

        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $out[] = $cursor->format('Y-m');
            $cursor->addMonthNoOverflow();
        }

        return $out;
    }
}
