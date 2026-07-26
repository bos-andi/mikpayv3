<?php

namespace App\Http\Controllers;

use App\Services\MikrotikService;
use App\Support\TenantRouterScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    private function ensureRouter(Request $request): array|RedirectResponse
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
            return redirect()->route('sessions')->with('error', 'Tidak bisa connect ke router.');
        }
        $identity = $router->router_name ?: $sessionName;
        $clock = ['time' => date('H:i:s'), 'date' => date('d M Y'), 'time-zone-name' => date_default_timezone_get()];
        try {
            $getIdentity = $mikrotik->comm('/system/identity/print');
            if (is_array($getIdentity) && isset($getIdentity[0]['name'])) {
                $identity = $getIdentity[0]['name'];
            }
            $getClock = $mikrotik->comm('/system/clock/print');
            if (is_array($getClock) && isset($getClock[0])) {
                $clock = $getClock[0];
            }
        } catch (\Throwable $e) {
            // keep defaults
        }
        $routers = TenantRouterScope::forUser($userId)->where('status', 'enable')->orderBy('session_name')->get();
        return [
            'currentRouter' => $router,
            'routers' => $routers,
            'identity' => $identity,
            'clock' => $clock,
            'mikrotik' => $mikrotik,
        ];
    }

    /** Report penjualan hotspot dari script (owner = bulan) */
    public function selling(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        $idbl = $request->get('idbl', strtolower(date('M')) . date('Y')); // e.g. mar2026

        $items = [];
        $totalMonth = 0;
        try {
            $getScripts = $mikrotik->comm('/system/script/print', ['?owner' => $idbl]);
            if (is_array($getScripts)) {
                foreach ($getScripts as $row) {
                    $name = $row['name'] ?? '';
                    $parts = explode('-|-', $name);
                    if (count($parts) >= 4) {
                        $price = (float) $parts[3];
                        $totalMonth += $price;
                        $items[] = [
                            'name' => $name,
                            'date' => $parts[0] ?? '',
                            'time' => $parts[1] ?? '',
                            'user' => $parts[2] ?? '',
                            'price' => $price,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        $currency = $ctx['currentRouter']->currency ?: 'Rp';
        return view('report.selling', array_merge($ctx, [
            'items' => $items,
            'totalMonth' => $totalMonth,
            'idbl' => $idbl,
            'currency' => $currency,
        ]));
    }
}
