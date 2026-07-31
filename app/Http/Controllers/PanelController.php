<?php

namespace App\Http\Controllers;

use App\Models\BillingCustomer;
use App\Models\BillingPayment;
use App\Models\Odp;
use App\Models\OdpPort;
use App\Services\MikrotikService;
use App\Services\TunnelService;
use App\Support\ActiveRouter;
use App\Support\BillingArrearsHelper;
use App\Support\BillingStatusHelper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    public function dashboard(MikrotikService $mikrotik): View
    {
        $tenant = current_tenant();
        $router = ActiveRouter::current();
        $period = now()->format('Y-m');
        $grace = $tenant?->isolirGraceDays() ?? (int) config('mikpay.billing.grace_days', 2);

        $pppTotal = 0;
        $pppActive = 0;
        $pppIsolir = 0;
        $paidCount = 0;
        $unpaidCount = 0;
        $notSetCount = 0;
        $periodIncome = 0;
        $fetchOk = false;
        $fetchMessage = null;
        $odpPortUsed = 0;
        $odpPortTotal = 0;
        $odpBoxCount = 0;

        if ($router) {
            $periodIncome = (int) BillingPayment::query()
                ->where('router_id', $router->id)
                ->where('period', $period)
                ->sum('amount');

            $odpBoxes = Odp::query()
                ->where('router_id', $router->id)
                ->where('type', Odp::TYPE_ODP)
                ->get(['id', 'port_count']);
            $odpBoxCount = $odpBoxes->count();
            $odpPortTotal = (int) $odpBoxes->sum('port_count');
            if ($odpBoxCount > 0) {
                $odpPortUsed = (int) OdpPort::query()
                    ->whereIn('odp_id', $odpBoxes->pluck('id'))
                    ->whereNotNull('customer_name')
                    ->where('customer_name', '!=', '')
                    ->count();
            }

            $result = $mikrotik->fetchPppCustomers($router);
            $fetchOk = (bool) ($result['ok'] ?? false);
            $fetchMessage = $result['message'] ?? null;

            $billingMap = BillingCustomer::query()
                ->where('router_id', $router->id)
                ->get()
                ->keyBy(fn (BillingCustomer $b) => strtolower($b->customer_name));

            foreach ($result['customers'] ?? [] as $c) {
                $pppTotal++;
                $isIsolir = (bool) ($c['isolir'] ?? false) || (bool) ($c['disabled'] ?? false);
                if ($isIsolir) {
                    $pppIsolir++;
                } else {
                    $pppActive++;
                }

                $billing = $billingMap->get(strtolower((string) ($c['name'] ?? '')));
                $dueDay = (int) ($billing?->due_day ?? 0);
                $fee = (int) ($billing?->monthly_fee ?? 0);

                if ($dueDay < 1 || $fee <= 0) {
                    $notSetCount++;
                    continue;
                }

                $status = BillingStatusHelper::getStatus($c['name'], $dueDay, $router->id, $fee);
                $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
                    $c['name'],
                    $dueDay,
                    $fee,
                    $router->id,
                    $billing?->started_at,
                    $grace
                );

                $currentPaid = ($status['status'] ?? '') === 'paid'
                    || ((int) ($status['paid_amount'] ?? 0) >= $fee);
                $hasArrears = ((int) ($arrears['outstanding_total'] ?? 0) > 0);

                if ($currentPaid && ! $hasArrears) {
                    $paidCount++;
                } else {
                    $unpaidCount++;
                }
            }
        }

        return view('panel.dashboard', [
            'period' => $period,
            'periodLabel' => now()->translatedFormat('M Y'),
            'periodIncome' => $periodIncome,
            'pppTotal' => $pppTotal,
            'pppActive' => $pppActive,
            'pppIsolir' => $pppIsolir,
            'paidCount' => $paidCount,
            'unpaidCount' => $unpaidCount,
            'notSetCount' => $notSetCount,
            'hasRouter' => (bool) $router,
            'fetchOk' => $fetchOk,
            'fetchMessage' => $fetchMessage,
            'odpPortUsed' => $odpPortUsed,
            'odpPortTotal' => $odpPortTotal,
            'odpBoxCount' => $odpBoxCount,
            'trafficUrl' => panel_route('dashboard.bandwidth'),
        ]);
    }

    /**
     * Live Rx/Tx dari MikroTik (sama sumber WinBox Traffic Monitor).
     */
    public function bandwidth(Request $request, MikrotikService $mikrotik): JsonResponse
    {
        $router = ActiveRouter::current();
        if (! $router) {
            return response()->json([
                'ok' => false,
                'message' => 'Belum ada router aktif.',
                'interface' => null,
                'rx_bps' => 0,
                'tx_bps' => 0,
                'interfaces' => [],
                'at' => now()->toIso8601String(),
            ]);
        }

        $interface = trim((string) $request->query('interface', ''));
        $result = $mikrotik->monitorInterfaceTraffic(
            $router,
            $interface !== '' ? $interface : null
        );

        return response()->json([
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
            'interface' => $result['interface'] ?? null,
            'rx_bps' => (float) ($result['rx_bps'] ?? 0),
            'tx_bps' => (float) ($result['tx_bps'] ?? 0),
            'interfaces' => $result['interfaces'] ?? [],
            'router' => $router->displayName(),
            'at' => now()->toIso8601String(),
        ]);
    }

    public function remote(TunnelService $tunnels): View
    {
        $tenant = current_tenant();
        $account = null;
        $scripts = ['ros6' => '', 'ros7' => ''];
        $bundleNote = null;

        if ($tenant?->id) {
            try {
                $account = $tunnels->ensureMikpayBundle($tenant)->loadMissing(['portMaps', 'node', 'entitlement']);
                $scripts = $tunnels->buildOpenVpnScripts($account);
                $bundleNote = 'Gratis OpenVPN — username @tunnel.mikpay.link, node an-*.mikpay.link';
            } catch (\Throwable $e) {
                $bundleNote = 'Tunnel belum bisa dibuat: '.$e->getMessage();
            }
        }

        return view('panel.remote', [
            'tunnelAccount' => $account,
            'tunnelScripts' => $scripts,
            'tunnelBundleNote' => $bundleNote,
        ]);
    }

    public function vouchers(): View
    {
        return view('panel.vouchers');
    }

    public function finance(): View
    {
        return view('panel.finance');
    }

    public function tickets(): View
    {
        return view('panel.tickets');
    }

    public function settings(): View
    {
        $tenant = current_tenant();
        $routers = collect();

        if ($tenant?->id) {
            $routers = \App\Models\Router::query()
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get();
        }

        return view('panel.settings', [
            'routers' => $routers,
            'settingsTab' => $this->normalizeSettingsTab(request('tab', 'mikrotik')),
        ]);
    }

    protected function normalizeSettingsTab(string $tab): string
    {
        if ($tab === 'xendit') {
            return 'payment';
        }

        return in_array($tab, ['mikrotik', 'payment', 'whatsapp', 'billing'], true) ? $tab : 'mikrotik';
    }
}
