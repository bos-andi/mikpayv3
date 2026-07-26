<?php

namespace App\Http\Controllers\Tunnel;

use App\Http\Controllers\Controller;
use App\Models\TunnelAccount;
use App\Models\TunnelEntitlement;
use App\Models\TunnelNode;
use App\Services\TunnelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TunnelHomeController extends Controller
{
    public function landing(): View
    {
        return view('tunnel.landing', [
            'defaultNodeHost' => TunnelNode::defaultNode()?->hostname
                ?? config('mikpay.tunnel.default_node_host', 'an-1.mikpay.link'),
        ]);
    }

    public function dashboard(Request $request, TunnelService $tunnels): View
    {
        $user = Auth::user();
        $tenant = $user?->tenant ?? current_tenant();

        $accounts = TunnelAccount::query()
            ->with(['portMaps', 'entitlement', 'node'])
            ->when($user?->id, function ($q) use ($user) {
                $q->where(function ($inner) use ($user) {
                    $inner->where('user_id', $user->id);
                    if ($user->tenant_id) {
                        $inner->orWhere('tenant_id', $user->tenant_id);
                    }
                });
            })
            ->when(! $user?->id && $tenant?->id, fn ($q) => $q->where('tenant_id', $tenant->id))
            ->orderByDesc('id')
            ->get();

        // MikPay bundle: auto-create jika tenant panel aktif belum punya akun.
        if ($tenant?->id && $tenant->status === 'active' && $tenant->plan !== 'tunnel' && $accounts->isEmpty()) {
            $accounts = collect([$tunnels->ensureMikpayBundle($tenant)->load(['portMaps', 'entitlement', 'node'])]);
        }

        $sourceLabels = [
            TunnelEntitlement::SOURCE_MIKPAY_BUNDLE => 'Gratis MikPay',
            TunnelEntitlement::SOURCE_TUNNEL_PAID => 'Langganan Tunnel',
        ];

        $vpnRows = $accounts->map(function (TunnelAccount $acc) use ($tunnels, $sourceLabels) {
            $winbox = $acc->portMaps->firstWhere('label', 'Winbox')
                ?? $acc->portMaps->first();
            $scripts = $tunnels->buildOpenVpnScripts($acc);
            $src = $acc->entitlement?->source;

            return [
                'id' => $acc->id,
                'username' => $acc->username,
                'password' => $acc->plainPassword(),
                'status' => $acc->status,
                'sourceLabel' => $sourceLabels[$src] ?? ($src ?: 'Tunnel'),
                'nodeHost' => $acc->nodeHostname(),
                'ovpnPort' => (int) ($acc->node?->ovpn_port ?: $acc->server_port),
                'remoteUrl' => $winbox?->endpoint() ?? $acc->nodeHostname(),
                'targetPort' => (int) ($winbox?->target_port ?: 8291),
                'portMaps' => $acc->portMaps->map(fn ($m) => [
                    'label' => $m->label,
                    'remote_port' => $m->remote_port,
                    'target_port' => $m->target_port,
                    'endpoint' => $m->endpoint(),
                ])->values()->all(),
                'scripts' => [
                    'ros6' => $scripts['ros6'],
                    'ros7' => $scripts['ros7'],
                ],
            ];
        })->values()->all();

        return view('tunnel.dashboard', [
            'vpnRows' => $vpnRows,
            'defaultNodeHost' => TunnelNode::defaultNode()?->hostname
                ?? config('mikpay.tunnel.default_node_host', 'an-1.mikpay.link'),
            'success' => session('success'),
        ]);
    }
}
