<?php

namespace App\Services;

use App\Models\BillingCustomer;
use App\Models\Router;
use App\Support\BillingArrearsHelper;
use Illuminate\Support\Facades\Log;

class AutoIsolirService
{
    public function __construct(
        protected MikrotikService $mikrotik
    ) {}

    /**
     * Evaluasi satu pelanggan: isolir atau buka sesuai tunggakan.
     *
     * @param  array<string, mixed>|null  $pppSecret  Row dari /ppp/secret/print (butuh .id, name, disabled)
     * @return array{action: string, message: string, arrears: array}
     */
    public function syncCustomer(Router $router, BillingCustomer $billing, ?array $pppSecret = null): array
    {
        $tenant = $router->tenant ?: current_tenant();
        $grace = $tenant instanceof \App\Models\Tenant
            ? $tenant->isolirGraceDays()
            : (int) config('mikpay.billing.grace_days', 2);
        $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
            $billing->customer_name,
            (int) $billing->due_day,
            (int) $billing->monthly_fee,
            (int) $router->id,
            $billing->started_at,
            $grace
        );

        if (! $pppSecret) {
            return [
                'action' => 'skip',
                'message' => 'PPP secret tidak ditemukan di MikroTik.',
                'arrears' => $arrears,
            ];
        }

        $secretId = (string) ($pppSecret['.id'] ?? '');
        $secretName = (string) ($pppSecret['name'] ?? $billing->customer_name);
        $isDisabled = $this->isYes($pppSecret['disabled'] ?? 'no');

        $autoIsolir = (bool) config('mikpay.billing.auto_isolir', true);
        $autoOpen = (bool) config('mikpay.billing.auto_open', true);

        if ($autoIsolir && $arrears['should_isolir'] && ! $isDisabled) {
            $result = $this->mikrotik->setSecretIsolir($router, $secretId, $secretName, true);

            return [
                'action' => $result['ok'] ? 'isolir' : 'error',
                'message' => $result['message'],
                'arrears' => $arrears,
            ];
        }

        if ($autoOpen && $arrears['should_open'] && $isDisabled) {
            $result = $this->mikrotik->setSecretIsolir($router, $secretId, $secretName, false);

            return [
                'action' => $result['ok'] ? 'open' : 'error',
                'message' => $result['message'],
                'arrears' => $arrears,
            ];
        }

        return [
            'action' => 'none',
            'message' => $isDisabled
                ? 'Tetap isolir (masih ada tunggakan).'
                : 'Tidak perlu perubahan.',
            'arrears' => $arrears,
        ];
    }

    /**
     * Jalankan sync untuk semua billing customer configured di satu router.
     *
     * @return array{isolir: int, open: int, skip: int, error: int, details: list<array>}
     */
    public function syncRouter(Router $router): array
    {
        $summary = ['isolir' => 0, 'open' => 0, 'skip' => 0, 'error' => 0, 'none' => 0, 'details' => []];

        $ppp = $this->mikrotik->fetchPppCustomers($router);
        if (! $ppp['ok']) {
            Log::warning('AutoIsolirService: gagal fetch PPP', [
                'router_id' => $router->id,
                'message' => $ppp['message'],
            ]);

            return array_merge($summary, [
                'error' => 1,
                'details' => [['customer' => '-', 'action' => 'error', 'message' => $ppp['message']]],
            ]);
        }

        $secretsByName = collect($ppp['customers'])->keyBy(fn ($c) => strtolower($c['name']));

        $customers = BillingCustomer::query()
            ->where('router_id', $router->id)
            ->where('due_day', '>=', 1)
            ->where('due_day', '<=', 31)
            ->get();

        foreach ($customers as $billing) {
            $secret = $secretsByName->get(strtolower($billing->customer_name));
            // Rebuild secret-like array for setSecretIsolir (needs .id)
            $pppRow = $secret ? [
                '.id' => $secret['id'],
                'name' => $secret['name'],
                'disabled' => ($secret['disabled'] ?? false) ? 'yes' : 'no',
            ] : null;

            $result = $this->syncCustomer($router, $billing, $pppRow);
            $action = $result['action'];
            if (! isset($summary[$action])) {
                $summary[$action] = 0;
            }
            $summary[$action]++;
            $summary['details'][] = [
                'customer' => $billing->customer_name,
                'action' => $action,
                'message' => $result['message'],
                'arrears_label' => $result['arrears']['label'] ?? null,
            ];
        }

        return $summary;
    }

    /**
     * Sync satu customer by name setelah pembayaran berubah.
     */
    public function syncCustomerByName(Router $router, string $customerName): array
    {
        $billing = BillingCustomer::query()
            ->where('router_id', $router->id)
            ->where('customer_name', $customerName)
            ->first();

        if (! $billing || ! $billing->isBillingConfigured()) {
            return [
                'action' => 'skip',
                'message' => 'Billing belum dikonfigurasi.',
                'arrears' => [],
            ];
        }

        $ppp = $this->mikrotik->fetchPppCustomers($router);
        if (! $ppp['ok']) {
            return [
                'action' => 'error',
                'message' => $ppp['message'],
                'arrears' => [],
            ];
        }

        $secret = collect($ppp['customers'])->first(
            fn ($c) => strtolower($c['name']) === strtolower($customerName)
        );

        $pppRow = $secret ? [
            '.id' => $secret['id'],
            'name' => $secret['name'],
            'disabled' => ($secret['disabled'] ?? false) ? 'yes' : 'no',
        ] : null;

        return $this->syncCustomer($router, $billing, $pppRow);
    }

    protected function isYes(mixed $value): bool
    {
        $v = strtolower(trim((string) $value));

        return in_array($v, ['yes', 'true', '1'], true);
    }
}
