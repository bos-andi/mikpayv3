<?php

namespace App\Services;

use App\Models\Router;
use App\Models\TunnelAccount;
use App\Models\TunnelNode;
use App\Models\TunnelPortMap;
use App\Support\PaymentGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan kondisi server untuk dashboard superadmin: VPS, layanan, node VPN, dan router tenant.
 * Pemeriksaan jaringan butuh beberapa detik, jadi hasilnya di-cache singkat.
 */
class PlatformStatusService
{
    private const CACHE_KEY = 'superadmin.platform_status';

    public function __construct(private readonly OpenVpnManagementService $ovpn)
    {
    }

    /**
     * @return array{checked_at: string, host: array, services: list<array>, nodes: list<array>, routers: array}
     */
    public function snapshot(bool $refresh = false): array
    {
        if ($refresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, now()->addSeconds(60), function () {
            $clients = $this->ovpn->connectedClients();
            $online = $clients === null ? null : array_values(array_unique(array_column($clients, 'common_name')));

            return [
                'checked_at' => now()->toIso8601String(),
                'host' => $this->host(),
                'services' => $this->services($clients !== null),
                'nodes' => $this->nodes($online),
                'routers' => $this->routers($online),
            ];
        });
    }

    protected function host(): array
    {
        $cores = max(1, (int) preg_match_all('/^processor\s*:/m', (string) @file_get_contents('/proc/cpuinfo')));
        $load = function_exists('sys_getloadavg') ? (sys_getloadavg() ?: [0, 0, 0]) : [0, 0, 0];

        $mem = [];
        foreach (explode("\n", (string) @file_get_contents('/proc/meminfo')) as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
                $mem[$m[1]] = (int) $m[2] * 1024;
            }
        }
        $memTotal = $mem['MemTotal'] ?? 0;
        $memUsed = max(0, $memTotal - ($mem['MemAvailable'] ?? $memTotal));

        $diskTotal = (int) (@disk_total_space('/') ?: 0);
        $diskUsed = max(0, $diskTotal - (int) (@disk_free_space('/') ?: 0));

        $uptime = (int) (float) explode(' ', (string) @file_get_contents('/proc/uptime'))[0];

        return [
            'hostname' => (string) gethostname(),
            'uptime_seconds' => $uptime,
            'cores' => $cores,
            'load' => array_map(fn ($v) => round((float) $v, 2), array_slice($load, 0, 3)),
            'cpu_percent' => $this->percent((float) $load[0], $cores),
            'memory' => ['used' => $memUsed, 'total' => $memTotal, 'percent' => $this->percent($memUsed, $memTotal)],
            'disk' => ['used' => $diskUsed, 'total' => $diskTotal, 'percent' => $this->percent($diskUsed, $diskTotal)],
        ];
    }

    /**
     * @return list<array{name: string, icon: string, ok: bool, detail: string}>
     */
    protected function services(bool $ovpnManagementOk): array
    {
        try {
            DB::select('select 1');
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        $pendingJobs = $dbOk ? (int) DB::table('jobs')->count() : 0;
        $failedJobs = $dbOk ? (int) DB::table('failed_jobs')->count() : 0;
        $queueRunning = $this->processRunning('queue:work');
        $gatewayReady = PaymentGateway::platformPaymentReady();

        return [
            ['name' => 'Web server', 'icon' => 'fa-globe', 'ok' => $this->portOpen('127.0.0.1', 443), 'detail' => 'HTTPS :443'],
            ['name' => 'Database', 'icon' => 'fa-database', 'ok' => $dbOk, 'detail' => $dbOk ? 'MySQL terhubung' : 'Tidak bisa terhubung'],
            [
                'name' => 'OpenVPN',
                'icon' => 'fa-shield-halved',
                'ok' => $ovpnManagementOk || $this->portOpen('127.0.0.1', (int) config('mikpay.tunnel.server_port', 1194)),
                'detail' => 'TCP :'.(int) config('mikpay.tunnel.server_port', 1194),
            ],
            [
                'name' => 'Queue worker',
                'icon' => 'fa-gears',
                'ok' => $queueRunning && $failedJobs === 0,
                'detail' => ($queueRunning ? 'Berjalan' : 'Mati').' · antre '.$pendingJobs.($failedJobs > 0 ? ' · gagal '.$failedJobs : ''),
            ],
            [
                'name' => 'Payment gateway',
                'icon' => 'fa-credit-card',
                'ok' => $gatewayReady,
                'detail' => $gatewayReady ? PaymentGateway::platformProviderLabel() : 'Belum dikonfigurasi',
            ],
        ];
    }

    /**
     * @param  list<string>|null  $online  common name VPN yang sedang tersambung (null = management tidak terjangkau)
     */
    protected function nodes(?array $online): array
    {
        $ipCapacity = max(0, (int) config('mikpay.tunnel.vpn_client_end', 254) - (int) config('mikpay.tunnel.vpn_client_start', 2) + 1);
        $ipUsed = TunnelAccount::query()->whereNotNull('tunnel_ip')->count();

        return TunnelNode::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (TunnelNode $node) use ($online, $ipCapacity, $ipUsed) {
                $accounts = TunnelAccount::query()->where('tunnel_node_id', $node->id)->get(['id', 'username', 'status']);
                $portCapacity = max(0, $node->port_range_end - $node->port_range_start + 1);
                $portUsed = TunnelPortMap::query()
                    ->whereIn('tunnel_account_id', $accounts->pluck('id'))
                    ->whereBetween('remote_port', [$node->port_range_start, $node->port_range_end])
                    ->count();
                $connected = $online === null ? null : $accounts->whereIn('username', $online)->count();

                return [
                    'code' => $node->code,
                    'label' => $node->label ?: strtoupper($node->code),
                    'hostname' => $node->hostname,
                    'region' => $node->region,
                    'ovpn_port' => $node->ovpn_port,
                    'is_active' => $node->is_active,
                    'is_default' => $node->is_default,
                    'online' => $node->is_active && ($connected !== null || $this->portOpen($node->hostname, $node->ovpn_port)),
                    'connected' => $connected,
                    'accounts_total' => $accounts->count(),
                    'accounts_active' => $accounts->where('status', 'active')->count(),
                    'ports' => ['used' => $portUsed, 'total' => $portCapacity, 'free' => max(0, $portCapacity - $portUsed), 'percent' => $this->percent($portUsed, $portCapacity)],
                    'ips' => ['used' => $ipUsed, 'total' => $ipCapacity, 'free' => max(0, $ipCapacity - $ipUsed), 'percent' => $this->percent($ipUsed, $ipCapacity)],
                ];
            })
            ->all();
    }

    /**
     * Router tenant dianggap online bila akun VPN pemilik port API-nya sedang tersambung.
     *
     * @param  list<string>|null  $online
     */
    protected function routers(?array $online): array
    {
        $nodeHosts = TunnelNode::query()->pluck('hostname')->map(fn ($h) => strtolower((string) $h))->all();
        $portOwners = TunnelPortMap::query()
            ->join('tunnel_accounts', 'tunnel_accounts.id', '=', 'tunnel_port_maps.tunnel_account_id')
            ->pluck('tunnel_accounts.username', 'tunnel_port_maps.remote_port');

        $items = Router::query()
            ->with('tenant:id,name,subdomain')
            ->orderBy('tenant_id')
            ->get()
            ->map(function (Router $router) use ($online, $nodeHosts, $portOwners) {
                $viaTunnel = in_array(strtolower((string) $router->host), $nodeHosts, true);
                $owner = $viaTunnel ? ($portOwners[(int) $router->port] ?? null) : null;

                $state = match (true) {
                    $router->status !== 'enable' => 'disabled',
                    $viaTunnel && $online !== null => $owner && in_array($owner, $online, true) ? 'online' : 'offline',
                    default => $router->last_check_ok ? 'online' : 'unknown',
                };

                return [
                    'tenant' => $router->tenant?->name ?? '-',
                    'subdomain' => $router->tenant?->subdomain,
                    'name' => $router->router_name ?: $router->name,
                    'endpoint' => $router->host.':'.$router->port,
                    'via_tunnel' => $viaTunnel,
                    'state' => $state,
                    'last_checked_at' => $router->last_checked_at?->toIso8601String(),
                ];
            });

        return [
            'total' => $items->count(),
            'online' => $items->where('state', 'online')->count(),
            'offline' => $items->whereIn('state', ['offline', 'unknown'])->count(),
            'items' => $items->sortBy(fn ($r) => [$r['state'] === 'online' ? 1 : 0, $r['tenant']])->values()->all(),
        ];
    }

    protected function portOpen(string $host, int $port): bool
    {
        $socket = @fsockopen($host, $port, $errno, $errstr, 2.0);
        if (! $socket) {
            return false;
        }
        fclose($socket);

        return true;
    }

    protected function processRunning(string $needle): bool
    {
        foreach (glob('/proc/[0-9]*/cmdline') ?: [] as $file) {
            $cmd = @file_get_contents($file);
            if ($cmd !== false && str_contains(str_replace("\0", ' ', $cmd), $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function percent(float|int $used, float|int $total): int
    {
        return $total > 0 ? (int) min(100, round($used / $total * 100)) : 0;
    }
}
