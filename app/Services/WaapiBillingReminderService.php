<?php

namespace App\Services;

use App\Models\BillingCustomer;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\WaapiReminderDispatch;
use App\Support\BillingArrearsHelper;
use App\Support\BillingStatusHelper;
use App\Support\WaapiTemplates;
use Illuminate\Support\Facades\Log;

class WaapiBillingReminderService
{
    public function __construct(
        protected WaApiService $waapi,
        protected PaymentLinkService $links,
    ) {}

    /**
     * @return array{tenants: int, sent: int, skipped: int, failed: int}
     */
    public function runAll(bool $dryRun = false): array
    {
        $totals = ['tenants' => 0, 'sent' => 0, 'skipped' => 0, 'failed' => 0];

        $tenants = Tenant::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        foreach ($tenants as $tenant) {
            if (! $tenant->waapiEnabled() || ! $tenant->hasWaapiToken()) {
                continue;
            }

            $hasAuto = WaapiTemplates::autoEnabled($tenant, 'h3')
                || WaapiTemplates::autoEnabled($tenant, 'h0')
                || WaapiTemplates::autoEnabled($tenant, 'overdue');
            if (! $hasAuto) {
                continue;
            }

            $totals['tenants']++;
            $batch = $this->runForTenant($tenant, $dryRun);
            $totals['sent'] += $batch['sent'];
            $totals['skipped'] += $batch['skipped'];
            $totals['failed'] += $batch['failed'];
        }

        return $totals;
    }

    /**
     * @return array{sent: int, skipped: int, failed: int}
     */
    public function runForTenant(Tenant $tenant, bool $dryRun = false): array
    {
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        $routers = Router::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'enable')
            ->get();

        if ($routers->isEmpty()) {
            return compact('sent', 'skipped', 'failed');
        }

        $token = $tenant->plainWaapiToken();
        $today = now()->toDateString();
        $grace = $tenant->isolirGraceDays();

        foreach ($routers as $router) {
            $customers = BillingCustomer::query()
                ->where('router_id', $router->id)
                ->orderBy('customer_name')
                ->get();

            foreach ($customers as $customer) {
                if (! $customer->isBillingConfigured()) {
                    continue;
                }

                $result = $this->processCustomer(
                    $tenant,
                    $router,
                    $customer,
                    $token,
                    $today,
                    $grace,
                    $dryRun
                );
                $sent += $result['sent'];
                $skipped += $result['skipped'];
                $failed += $result['failed'];
            }
        }

        return compact('sent', 'skipped', 'failed');
    }

    /**
     * @return array{sent: int, skipped: int, failed: int}
     */
    protected function processCustomer(
        Tenant $tenant,
        Router $router,
        BillingCustomer $customer,
        string $token,
        string $today,
        int $grace,
        bool $dryRun,
    ): array {
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        $name = $customer->customer_name;
        $dueDay = (int) $customer->due_day;
        $fee = (int) $customer->monthly_fee;

        $status = BillingStatusHelper::getStatus($name, $dueDay, (int) $router->id, $fee);
        $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
            $name,
            $dueDay,
            $fee,
            (int) $router->id,
            $customer->started_at,
            $grace
        );

        $kinds = $this->resolveKinds($tenant, $status, $arrears, $today);
        if ($kinds === []) {
            return compact('sent', 'skipped', 'failed');
        }

        $phone = trim((string) ($customer->phone ?? ''));
        if ($phone === '') {
            return ['sent' => 0, 'skipped' => count($kinds), 'failed' => 0];
        }

        foreach ($kinds as $kind) {
            $exists = WaapiReminderDispatch::query()
                ->where('router_id', $router->id)
                ->where('customer_name', $name)
                ->where('kind', $kind)
                ->whereDate('dispatch_date', $today)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $tpl = WaapiTemplates::body($tenant, $kind);
            if (trim($tpl) === '') {
                $skipped++;
                continue;
            }

            $linkUrl = '';
            try {
                if (($arrears['outstanding_total'] ?? 0) > 0) {
                    $link = $this->links->createOrRefresh($tenant, $router, $name);
                    $linkUrl = $link->publicUrl();
                }
            } catch (\Throwable) {
                $linkUrl = '';
            }

            $period = $status['current_period'] ?? now()->format('Y-m');
            $nominal = $kind === WaapiReminderDispatch::KIND_OVERDUE
                ? (int) ($arrears['outstanding_total'] ?? $fee)
                : $fee;
            if ($nominal <= 0) {
                $nominal = $fee;
            }

            $message = strtr($tpl, [
                '{nama}' => $customer->display_name ?: $name,
                '{username}' => $name,
                '{periode}' => $this->formatPeriod($period),
                '{nominal}' => 'Rp '.number_format($nominal, 0, ',', '.'),
                '{link}' => $linkUrl !== '' ? $linkUrl : '(hubungi admin)',
                '{tanggal}' => $status['due_date']
                    ? BillingStatusHelper::formatDueDate($status['due_date'])
                    : '—',
                '{jatuh_tempo}' => $status['due_date']
                    ? BillingStatusHelper::formatDueDate($status['due_date'])
                    : '—',
                '{hari}' => (string) abs((int) ($status['days_to_due'] ?? 0)),
            ]);

            if ($dryRun) {
                Log::info('waapi.reminder.dry_run', [
                    'tenant' => $tenant->id,
                    'customer' => $name,
                    'kind' => $kind,
                ]);
                $sent++;
                continue;
            }

            $result = $this->waapi->sendText($token, $phone, $message);
            if (! $result['ok']) {
                Log::error('waapi.reminder.failed', [
                    'tenant' => $tenant->id,
                    'customer' => $name,
                    'kind' => $kind,
                    'phone' => $phone,
                    'message' => $result['message'],
                    'status' => $result['status'] ?? null,
                ]);
                $failed++;
                continue;
            }

            WaapiReminderDispatch::query()->create([
                'tenant_id' => $tenant->id,
                'router_id' => $router->id,
                'customer_name' => $name,
                'kind' => $kind,
                'dispatch_date' => $today,
                'phone' => $phone,
            ]);
            $sent++;

            // jeda singkat antispam antar nomor
            usleep(400_000);
        }

        return compact('sent', 'skipped', 'failed');
    }

    /**
     * @param  array<string, mixed>  $status
     * @param  array<string, mixed>  $arrears
     * @return list<string>
     */
    protected function resolveKinds(Tenant $tenant, array $status, array $arrears, string $today): array
    {
        $kinds = [];
        $promiseActive = (bool) ($arrears['promise_active'] ?? false);
        $promiseDate = $arrears['promised_pay_date'] ?? null;
        $promiseOverdue = (bool) ($arrears['promise_overdue'] ?? false);

        // Ada janji aktif: hanya kirim di hari tanggal janji (pakai template H-0).
        if ($promiseActive) {
            if ($promiseDate === $today && WaapiTemplates::autoEnabled($tenant, 'h0')) {
                $kinds[] = WaapiReminderDispatch::KIND_H0;
            }

            return $kinds;
        }

        if (WaapiTemplates::autoEnabled($tenant, 'h3')
            && ($status['status'] ?? '') === 'due_soon'
            && (int) ($status['days_to_due'] ?? -1) === 3) {
            $kinds[] = WaapiReminderDispatch::KIND_H3;
        }

        if (WaapiTemplates::autoEnabled($tenant, 'h0')
            && ($status['status'] ?? '') === 'due_today') {
            $kinds[] = WaapiReminderDispatch::KIND_H0;
        }

        if (WaapiTemplates::autoEnabled($tenant, 'overdue')) {
            $isOverdue = ($status['status'] ?? '') === 'overdue'
                || $promiseOverdue
                || (($arrears['should_isolir'] ?? false) && ($arrears['outstanding_total'] ?? 0) > 0);
            if ($isOverdue && ($arrears['outstanding_total'] ?? 0) > 0) {
                $kinds[] = WaapiReminderDispatch::KIND_OVERDUE;
            }
        }

        return $kinds;
    }

    protected function formatPeriod(string $ym): string
    {
        try {
            return \Carbon\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('M Y');
        } catch (\Throwable) {
            return $ym;
        }
    }
}
