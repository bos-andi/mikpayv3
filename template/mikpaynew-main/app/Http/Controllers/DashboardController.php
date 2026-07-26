<?php

namespace App\Http\Controllers;

use App\Helpers\MikrotikFormat;
use App\Models\BillingPayment;
use App\Models\Subscription;
use App\Services\MikrotikService;
use App\Support\TenantRouterScope;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard setelah Open = data dari API router (sesuai template dashboard/home.php).
     */
    public function index(Request $request)
    {
        $userId = (int) ($request->user()?->id ?? 0);
        $sessionName = $request->session()->get('selected_router');
        if (! $sessionName) {
            return redirect()->route('sessions');
        }

        $router = TenantRouterScope::forUser($userId)->where('session_name', $sessionName)->where('status', 'enable')->first();
        if (! $router) {
            $request->session()->forget('selected_router');
            return redirect()->route('sessions')->with('error', 'Router tidak ditemukan.');
        }

        $mikrotik = new MikrotikService;
        if (! $mikrotik->connectRouter($router)) {
            $mikrotik->disconnect();
            return redirect()->route('sessions')->with('error', 'Tidak bisa connect ke router. Cek koneksi dan API.');
        }

        $identity = 'Unknown';
        $resource = [];
        $routerboard = [];
        $syshealth = [];
        $clock = ['time' => date('H:i:s'), 'date' => date('d M Y'), 'time-zone-name' => date_default_timezone_get()];
        $countHotspotActive = 0;
        $countHotspotUsers = 0;
        $countPppActive = 0;
        $countPppSecrets = 0;
        $hotspotIncomeMonth = 0;
        $pppIncomeMonth = 0;
        $currency = $router->currency ?: 'Rp';

        try {
            $getIdentity = $mikrotik->comm('/system/identity/print');
            $identity = (is_array($getIdentity) && isset($getIdentity[0]['name']) ? $getIdentity[0]['name'] : $router->router_name) ?: $sessionName;

            $getResource = $mikrotik->comm('/system/resource/print');
            $resource = is_array($getResource) && isset($getResource[0]) ? $getResource[0] : [];

            $getRouterboard = $mikrotik->comm('/system/routerboard/print');
            $routerboard = is_array($getRouterboard) && isset($getRouterboard[0]) ? $getRouterboard[0] : [];

            $getHealth = $mikrotik->comm('/system/health/print');
            $syshealth = is_array($getHealth) && isset($getHealth[0]) ? $getHealth[0] : [];

            $getClock = $mikrotik->comm('/system/clock/print');
            if (is_array($getClock) && isset($getClock[0])) {
                $clock = $getClock[0];
            }

            // Data dari API router (sesuai template: print lalu count)
            // Active Hotspot = sesi hotspot aktif saat ini
            $getHotspotActive = $mikrotik->comm('/ip/hotspot/active/print');
            $countHotspotActive = $this->countReply($getHotspotActive);
            // Total Users = semua user hotspot
            $getHotspotUsers = $mikrotik->comm('/ip/hotspot/user/print');
            $countHotspotUsers = $this->countReply($getHotspotUsers);
            // Active PPP = koneksi PPP aktif
            $getPppActive = $mikrotik->comm('/ppp/active/print');
            $countPppActive = $this->countReply($getPppActive);
            // Total Secrets = semua secret PPP
            $getPppSecrets = $mikrotik->comm('/ppp/secret/print');
            $countPppSecrets = $this->countReply($getPppSecrets);

            // Hotspot Income dari report: script MikroTik dengan owner = bulan ini (format: mar2026)
            $idbl = strtolower(date('M')) . date('Y');
            $getSRBl = $mikrotik->comm('/system/script/print', ['?owner' => $idbl]);
            if (is_array($getSRBl)) {
                foreach ($getSRBl as $row) {
                    $scriptName = $row['name'] ?? '';
                    $parts = explode('-|-', $scriptName);
                    if (count($parts) >= 4) {
                        $hotspotIncomeMonth += (float) $parts[3];
                    }
                }
            }

            // PPP Income dari billing (report) — per router_session tenant
            $now = now();
            $pppIncomeMonth = (int) BillingPayment::query()
                ->where('status', 'paid')
                ->where('router_session', $sessionName)
                ->whereNotNull('payment_date')
                ->whereYear('payment_date', (int) $now->year)
                ->whereMonth('payment_date', (int) $now->month)
                ->sum('amount');
        } catch (\Throwable $e) {
            report($e);
        }

        $mikrotik->disconnect();

        $routers = TenantRouterScope::forUser($userId)->where('status', 'enable')->orderBy('session_name')->get();
        $subscription = Subscription::getGlobal();

        $cpuPercent = isset($resource['cpu-load']) ? (int) $resource['cpu-load'] : 0;
        $totalMem = isset($resource['total-memory']) ? (int) $resource['total-memory'] : 1;
        $freeMem = isset($resource['free-memory']) ? (int) $resource['free-memory'] : 0;
        $memoryPercent = $totalMem > 0 ? round((($totalMem - $freeMem) / $totalMem) * 100, 2) : 0;
        $totalHdd = isset($resource['total-hdd-space']) ? (int) $resource['total-hdd-space'] : 1;
        $freeHdd = isset($resource['free-hdd-space']) ? (int) $resource['free-hdd-space'] : 0;
        $hddPercent = $totalHdd > 0 ? round((($totalHdd - $freeHdd) / $totalHdd) * 100, 2) : 0;

        $uptimeFormatted = isset($resource['uptime']) ? MikrotikFormat::formatUptime($resource['uptime']) : '-';
        $memFreeFormatted = isset($resource['free-memory']) ? MikrotikFormat::formatBytes((int) $resource['free-memory'], 2) : '-';
        $memTotalFormatted = isset($resource['total-memory']) ? MikrotikFormat::formatBytes((int) $resource['total-memory'], 2) : '-';
        $hddFreeFormatted = isset($resource['free-hdd-space']) ? MikrotikFormat::formatBytes((int) $resource['free-hdd-space'], 2) : '-';
        $hddTotalFormatted = isset($resource['total-hdd-space']) ? MikrotikFormat::formatBytes((int) $resource['total-hdd-space'], 2) : '-';

        return view('dashboard.index', [
            'routers' => $routers,
            'currentRouter' => $router,
            'subscription' => $subscription,
            'identity' => $identity,
            'resource' => $resource,
            'routerboard' => $routerboard,
            'syshealth' => $syshealth,
            'clock' => $clock,
            'countHotspotActive' => $countHotspotActive,
            'countHotspotUsers' => $countHotspotUsers,
            'countPppActive' => $countPppActive,
            'countPppSecrets' => $countPppSecrets,
            'hotspotIncomeMonth' => $hotspotIncomeMonth,
            'pppIncomeMonth' => $pppIncomeMonth,
            'currency' => $currency,
            'cpuPercent' => $cpuPercent,
            'memoryPercent' => $memoryPercent,
            'hddPercent' => $hddPercent,
            'uptimeFormatted' => $uptimeFormatted,
            'memFreeFormatted' => $memFreeFormatted,
            'memTotalFormatted' => $memTotalFormatted,
            'hddFreeFormatted' => $hddFreeFormatted,
            'hddTotalFormatted' => $hddTotalFormatted,
        ]);
    }

    /**
     * Hitung jumlah dari response /print (sesuai template: array of items = count).
     * Jika API mengembalikan count-only maka result bisa scalar atau ['ret' => N].
     */
    private function countReply($result): int
    {
        if (is_numeric($result)) {
            return (int) $result;
        }
        if (! is_array($result)) {
            return 0;
        }
        if (isset($result['ret']) && ! isset($result[0])) {
            return (int) $result['ret'];
        }
        return count($result);
    }
}
