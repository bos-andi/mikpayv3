<?php

namespace App\Http\Controllers;

use App\Services\TunnelService;
use Illuminate\Contracts\View\View;

class PanelController extends Controller
{
    public function dashboard(): View
    {
        return view('panel.dashboard');
    }

    public function gis(): View
    {
        return view('panel.gis');
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
