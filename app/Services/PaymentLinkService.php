<?php

namespace App\Services;

use App\Models\BillingCustomer;
use App\Models\BillingPayment;
use App\Models\BillingPaymentLink;
use App\Models\Router;
use App\Models\Tenant;
use App\Support\BillingArrearsHelper;
use App\Support\BillingStatusHelper;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PaymentLinkService
{
    public function __construct(
        protected XenditService $xendit,
        protected WaApiService $waapi,
        protected AutoIsolirService $autoIsolir,
    ) {}

    /**
     * Buat / reuse link pending untuk pelanggan + outstanding saat ini.
     */
    public function createOrRefresh(
        Tenant $tenant,
        Router $router,
        string $customerName,
        ?int $expireDays = 7,
    ): BillingPaymentLink {
        $customer = BillingCustomer::query()
            ->where('router_id', $router->id)
            ->where('customer_name', $customerName)
            ->first();

        if (! $customer) {
            throw new \RuntimeException('Data billing pelanggan belum diatur.');
        }

        $this->ensurePublicCode($customer);

        $grace = $tenant->isolirGraceDays();
        $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
            $customerName,
            (int) $customer->due_day,
            (int) $customer->monthly_fee,
            $router->id,
            $customer->started_at,
            $grace
        );

        $amount = (int) ($arrears['outstanding_total'] ?? 0);
        $periods = $arrears['unpaid_periods'] ?? [];

        if ($amount <= 0 || $periods === []) {
            throw new \RuntimeException('Tidak ada tunggakan untuk dibuatkan link pembayaran.');
        }

        // Reuse pending link yang belum expired dengan amount/periods sama.
        $existing = BillingPaymentLink::query()
            ->where('router_id', $router->id)
            ->where('customer_name', $customerName)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();

        if ($existing
            && (int) $existing->amount === $amount
            && json_encode(array_values($existing->periods ?? [])) === json_encode(array_values($periods))
        ) {
            $existing->forceFill([
                'display_name' => $customer->display_name,
                'phone' => $customer->phone,
            ])->save();

            return $existing;
        }

        if ($existing) {
            $existing->forceFill(['status' => 'cancelled'])->save();
        }

        return BillingPaymentLink::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'token' => BillingPaymentLink::generateToken(),
            'customer_name' => $customerName,
            'display_name' => $customer->display_name,
            'phone' => $customer->phone,
            'periods' => $periods,
            'amount' => $amount,
            'status' => 'pending',
            'expires_at' => now()->addDays(max(1, $expireDays ?? 7)),
        ]);
    }

    public function ensurePublicCode(BillingCustomer $customer): string
    {
        if ($customer->public_code) {
            return $customer->public_code;
        }

        for ($i = 0; $i < 12; $i++) {
            $code = strtoupper(Str::random(6));
            $exists = BillingCustomer::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('public_code', $code)
                ->exists();
            if (! $exists) {
                $customer->forceFill(['public_code' => $code])->save();

                return $code;
            }
        }

        $code = strtoupper(Str::random(8));
        $customer->forceFill(['public_code' => $code])->save();

        return $code;
    }

    public function buildReminderMessage(BillingPaymentLink $link, ?string $template = null): string
    {
        $name = $link->display_name ?: $link->customer_name;
        $periods = is_array($link->periods) ? $link->periods : [];
        $periodLabels = array_map(
            fn ($p) => Carbon::parse($p.'-01')->translatedFormat('M Y'),
            $periods
        );
        $periodText = implode(', ', $periodLabels);
        $amount = 'Rp '.number_format((int) $link->amount, 0, ',', '.');
        $url = $link->publicUrl();

        $tpl = trim((string) ($template ?: ''));
        if ($tpl === '') {
            $tpl = "Yth. *{nama}*,\n\n"
                ."Berikut informasi tagihan WiFi Anda untuk periode *{periode}* dengan total *{nominal}*.\n"
                ."Tanggal jatuh tempo: *{tanggal}*.\n\n"
                ."Silakan melakukan pembayaran melalui tautan berikut:\n{link}\n\n"
                .'Abaikan pesan ini apabila pembayaran sudah dilakukan. Terima kasih.';
        }

        $tanggal = '—';
        $customer = BillingCustomer::query()
            ->where('router_id', $link->router_id)
            ->where('customer_name', $link->customer_name)
            ->first();
        if ($customer && (int) $customer->due_day >= 1) {
            $periodRef = $periods[0] ?? now()->format('Y-m');
            try {
                $base = Carbon::createFromFormat('Y-m', $periodRef)->startOfMonth();
                $daysInMonth = (int) $base->format('t');
                $day = min((int) $customer->due_day, $daysInMonth);
                $tanggal = $base->copy()->addDays($day - 1)->translatedFormat('d M Y');
            } catch (\Throwable) {
                $tanggal = '—';
            }
        }

        return strtr($tpl, [
            '{nama}' => $name,
            '{periode}' => $periodText,
            '{nominal}' => $amount,
            '{link}' => $url,
            '{username}' => $link->customer_name,
            '{tanggal}' => $tanggal,
            '{jatuh_tempo}' => $tanggal,
        ]);
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function sendWhatsAppReminder(Tenant $tenant, BillingPaymentLink $link): array
    {
        if (! $tenant->waapiEnabled()) {
            return ['ok' => false, 'message' => 'WhatsApp Gateway (WaAPI) belum diaktifkan.'];
        }

        $token = $tenant->plainWaapiToken();
        if ($token === '') {
            return ['ok' => false, 'message' => 'Token WaAPI belum diisi.'];
        }

        $phone = (string) ($link->phone ?? '');
        if (trim($phone) === '') {
            return ['ok' => false, 'message' => 'Nomor HP pelanggan belum diisi.'];
        }

        $message = $this->buildReminderMessage(
            $link,
            \App\Support\WaapiTemplates::body($tenant, 'billing_link')
        );
        $result = $this->waapi->sendText($token, $phone, $message);

        if ($result['ok']) {
            $link->forceFill(['wa_sent_at' => now()])->save();
        }

        return [
            'ok' => $result['ok'],
            'message' => $result['ok'] ? 'Reminder WhatsApp terkirim.' : ('Gagal kirim WA: '.$result['message']),
        ];
    }

    /**
     * Catat pembayaran dari webhook Xendit (alokasi FIFO per periode).
     *
     * @return array{ok: bool, message: string, payments?: list<BillingPayment>}
     */
    public function markPaidFromXendit(BillingPaymentLink $link, array $payload): array
    {
        if ($link->status === 'paid') {
            return ['ok' => true, 'message' => 'Sudah tercatat sebelumnya.'];
        }

        $amountPaid = (int) ($payload['paid_amount'] ?? $payload['amount'] ?? $link->amount);
        if ($amountPaid <= 0) {
            $amountPaid = (int) $link->amount;
        }

        $invoiceId = (string) ($payload['id'] ?? $link->xendit_invoice_id ?? '');
        $externalId = (string) ($payload['external_id'] ?? $link->xendit_external_id ?? '');

        $customer = BillingCustomer::query()
            ->where('router_id', $link->router_id)
            ->where('customer_name', $link->customer_name)
            ->first();

        $monthlyFee = (int) ($customer?->monthly_fee ?? 0);
        $periods = is_array($link->periods) ? $link->periods : [];
        $remainingBudget = $amountPaid;
        $created = [];
        $feeOverrides = \App\Support\BillingFeeHelper::overridesMap(
            (int) $link->router_id,
            (string) $link->customer_name,
            $periods
        );

        foreach ($periods as $period) {
            if ($remainingBudget <= 0) {
                break;
            }

            $periodFee = \App\Support\BillingFeeHelper::feeForPeriod(
                (int) $link->router_id,
                (string) $link->customer_name,
                (string) $period,
                $monthlyFee,
                $feeOverrides
            );

            $already = (int) BillingPayment::query()
                ->where('router_id', $link->router_id)
                ->where('customer_name', $link->customer_name)
                ->where('period', $period)
                ->sum('amount');

            $need = $periodFee > 0 ? max(0, $periodFee - $already) : $remainingBudget;
            if ($need <= 0) {
                continue;
            }

            $pay = min($need, $remainingBudget);
            $after = $already + $pay;
            $paymentStatus = ($periodFee > 0 && $after < $periodFee) ? 'partial' : 'paid';
            $remaining = max(0, $periodFee - $after);

            $payment = BillingPayment::query()->create([
                'tenant_id' => $link->tenant_id,
                'router_id' => $link->router_id,
                'customer_name' => $link->customer_name,
                'display_name' => $link->display_name ?: $customer?->display_name,
                'phone' => $link->phone ?: $customer?->phone,
                'amount' => $pay,
                'remaining_amount' => $remaining,
                'period' => $period,
                'payment_date' => now(),
                'payment_method' => 'Xendit',
                'notes' => 'Xendit '.$invoiceId,
                'status' => $paymentStatus,
                'xendit_invoice_id' => $invoiceId ?: null,
                'xendit_external_id' => $externalId ?: null,
                'payment_link_id' => $link->id,
            ]);

            $payment->invoice_id = BillingStatusHelper::formatInvoiceId($payment->period, (int) $payment->id);
            $payment->save();

            $created[] = $payment;
            $remainingBudget -= $pay;
        }

        // Sisa budget yang tidak teralokasi → periode pertama / current
        if ($remainingBudget > 0) {
            $period = $periods[0] ?? now()->format('Y-m');
            $periodFee = \App\Support\BillingFeeHelper::feeForPeriod(
                (int) $link->router_id,
                (string) $link->customer_name,
                (string) $period,
                $monthlyFee,
                $feeOverrides
            );
            $already = (int) BillingPayment::query()
                ->where('router_id', $link->router_id)
                ->where('customer_name', $link->customer_name)
                ->where('period', $period)
                ->sum('amount');
            $after = $already + $remainingBudget;
            $paymentStatus = ($periodFee > 0 && $after < $periodFee) ? 'partial' : 'paid';
            $remaining = max(0, $periodFee - $after);

            $payment = BillingPayment::query()->create([
                'tenant_id' => $link->tenant_id,
                'router_id' => $link->router_id,
                'customer_name' => $link->customer_name,
                'display_name' => $link->display_name ?: $customer?->display_name,
                'phone' => $link->phone ?: $customer?->phone,
                'amount' => $remainingBudget,
                'remaining_amount' => $remaining,
                'period' => $period,
                'payment_date' => now(),
                'payment_method' => 'Xendit',
                'notes' => 'Xendit '.$invoiceId.' (sisa)',
                'status' => $paymentStatus,
                'xendit_invoice_id' => $invoiceId ?: null,
                'xendit_external_id' => $externalId ?: null,
                'payment_link_id' => $link->id,
            ]);
            $payment->invoice_id = BillingStatusHelper::formatInvoiceId($payment->period, (int) $payment->id);
            $payment->save();
            $created[] = $payment;
        }

        if ($created !== []) {
            BillingArrearsHelper::clearPromisesForPaidPeriods(
                (int) $link->router_id,
                $link->customer_name,
                $monthlyFee,
                array_values(array_unique(array_map(fn ($p) => $p->period, $created)))
            );
        }

        $router = Router::query()->find($link->router_id);
        $tenant = $link->tenant ?: Tenant::query()->find($link->tenant_id);

        // Setelah bayar (bisa parsial per bulan), refresh sisa tunggakan di link yang sama.
        $remainingPeriods = [];
        $remainingAmount = 0;
        if ($customer && $tenant && $router) {
            $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
                $customer->customer_name,
                (int) $customer->due_day,
                (int) $customer->monthly_fee,
                (int) $router->id,
                $customer->started_at,
                $tenant->isolirGraceDays()
            );
            $remainingPeriods = array_values($arrears['unpaid_periods'] ?? []);
            $remainingAmount = (int) ($arrears['outstanding_total'] ?? 0);
        }

        if ($remainingAmount > 0 && $remainingPeriods !== []) {
            $link->forceFill([
                'status' => 'pending',
                'paid_at' => null,
                'periods' => $remainingPeriods,
                'amount' => $remainingAmount,
                'xendit_invoice_id' => null,
                'xendit_external_id' => null,
                'xendit_invoice_url' => null,
                'payment_checkout' => null,
            ])->save();
        } else {
            $link->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'periods' => [],
                'amount' => 0,
                'xendit_invoice_id' => $invoiceId ?: $link->xendit_invoice_id,
                'xendit_external_id' => $externalId ?: $link->xendit_external_id,
                'payment_checkout' => null,
            ])->save();
        }

        if ($router) {
            $this->autoIsolir->syncCustomerByName($router, $link->customer_name);
        }

        return [
            'ok' => true,
            'message' => 'Pembayaran Xendit dicatat ('.count($created).' periode).',
            'payments' => $created,
        ];
    }
}
