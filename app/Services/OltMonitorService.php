<?php

namespace App\Services;

use App\Models\OltDevice;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\TunnelAccount;
use App\Models\TunnelPortMap;
use App\Services\MikrotikService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OltMonitorService
{
    /**
     * Sinkronkan perangkat OLT dari rule NAT MikroTik + port tunnel.
     * Dipanggil otomatis saat dashboard/remote dibuka.
     *
     * @return array{created:int,updated:int,source:string,message:string}
     */
    public function syncFromRouter(Tenant $tenant, Router $router, MikrotikService $mikrotik): array
    {
        $account = TunnelAccount::query()
            ->where('tenant_id', $tenant->id)
            ->with(['portMaps', 'node'])
            ->first();

        $oltMap = $account?->portMaps->firstWhere('label', 'OLT');
        $nat = $mikrotik->fetchMikpayOltNatRules($router);

        $created = 0;
        $updated = 0;
        $source = 'none';

        if (($nat['rules'] ?? []) !== []) {
            $source = 'mikrotik';
            foreach ($nat['rules'] as $index => $rule) {
                $autoKey = 'nat_'.($rule['management_ip'] ?? 'unknown').'_'.($rule['tunnel_dst_port'] ?? 25);
                $device = OltDevice::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('router_id', $router->id)
                    ->where('meta->auto_key', $autoKey)
                    ->first();

                $payload = [
                    'tenant_id' => $tenant->id,
                    'router_id' => $router->id,
                    'name' => $index === 0 ? 'OLT Utama' : 'OLT '.($index + 1),
                    'vendor' => 'hsgq',
                    'management_ip' => $rule['management_ip'],
                    'web_port' => (int) $rule['web_port'],
                    'meta' => [
                        'auto_key' => $autoKey,
                        'auto_detected' => true,
                        'nat_comment' => $rule['comment'] ?? null,
                        'tunnel_dst_port' => (int) ($rule['tunnel_dst_port'] ?? 25),
                        'tunnel_endpoint' => $oltMap?->endpoint(),
                    ],
                ];

                if ($device) {
                    $device->update($payload);
                    $updated++;
                } else {
                    OltDevice::query()->create(array_merge($payload, [
                        'pon_count' => 0,
                        'status' => OltDevice::STATUS_UNKNOWN,
                    ]));
                    $created++;
                }
            }
        } elseif ($oltMap instanceof TunnelPortMap && $oltMap->is_enabled) {
            $source = 'tunnel';
            $autoKey = 'tunnel_olt';
            $device = OltDevice::query()
                ->where('tenant_id', $tenant->id)
                ->where('router_id', $router->id)
                ->where('meta->auto_key', $autoKey)
                ->first();

            $payload = [
                'tenant_id' => $tenant->id,
                'router_id' => $router->id,
                'name' => 'OLT (Tunnel)',
                'vendor' => 'hsgq',
                'meta' => [
                    'auto_key' => $autoKey,
                    'auto_detected' => true,
                    'tunnel_endpoint' => $oltMap->endpoint(),
                    'tunnel_dst_port' => (int) $oltMap->target_port,
                ],
            ];

            if ($device) {
                $device->update($payload);
                $updated++;
            } else {
                OltDevice::query()->create(array_merge($payload, [
                    'pon_count' => 0,
                    'status' => OltDevice::STATUS_UNKNOWN,
                ]));
                $created++;
            }
        }

        $message = match ($source) {
            'mikrotik' => $nat['message'] ?? 'OLT terdeteksi dari MikroTik.',
            'tunnel' => 'Port tunnel OLT aktif. IP OLT belum terbaca dari NAT — pastikan script MikPay sudah di-paste.',
            default => 'Tunnel OLT belum aktif.',
        };

        return compact('created', 'updated', 'source', 'message');
    }

    /**
     * @return array{pon_total:int,pon_online:int,avg_rx_power:?float,devices:Collection<int, OltDevice>,has_devices:bool}
     */
    public function dashboardSummary(Tenant $tenant, ?int $routerId = null): array
    {
        $query = OltDevice::query()->where('tenant_id', $tenant->id);
        if ($routerId) {
            $query->where('router_id', $routerId);
        }

        $devices = $query->orderBy('name')->get();
        if ($devices->isEmpty()) {
            return [
                'pon_total' => 0,
                'pon_online' => 0,
                'avg_rx_power' => null,
                'devices' => $devices,
                'has_devices' => false,
            ];
        }

        $ponTotal = (int) $devices->sum('pon_count');
        $ponOnline = (int) $devices->where('status', OltDevice::STATUS_ONLINE)->sum('pon_count');
        $rxValues = $devices->pluck('avg_rx_power')->filter(fn ($v) => $v !== null && $v !== '');

        return [
            'pon_total' => $ponTotal,
            'pon_online' => $ponOnline,
            'avg_rx_power' => $rxValues->isNotEmpty() ? round((float) $rxValues->avg(), 1) : null,
            'devices' => $devices,
            'has_devices' => true,
        ];
    }

    /**
     * @return array{ok:bool,status:string,message:string,onu_online?:int,onu_offline?:int,avg_rx_power?:?float}
     */
    public function pollDevice(OltDevice $device): array
    {
        $endpoint = $this->resolvePollEndpoint($device);
        if ($endpoint === null) {
            return $this->markPolled($device, OltDevice::STATUS_UNKNOWN, 'Endpoint polling belum dikonfigurasi. Pastikan tunnel OLT aktif.');
        }

        $url = "http://{$endpoint}/";

        try {
            $response = Http::timeout(8)
                ->withOptions(['verify' => false, 'allow_redirects' => true])
                ->get($url);

            $online = $response->status() > 0 && $response->status() < 500;
            if (! $online) {
                return $this->markPolled($device, OltDevice::STATUS_OFFLINE, "HTTP {$response->status()} dari {$endpoint}");
            }

            $meta = $device->meta ?? [];
            $onuStats = $this->estimateOnuStats($device, (string) $response->body());

            $device->forceFill([
                'status' => OltDevice::STATUS_ONLINE,
                'onu_online_count' => $onuStats['online'],
                'onu_offline_count' => $onuStats['offline'],
                'avg_rx_power' => $onuStats['avg_rx'],
                'last_polled_at' => wib_now(),
                'poll_message' => "Terakhir dicek via {$endpoint}",
                'meta' => array_merge($meta, [
                    'last_http_status' => $response->status(),
                    'last_endpoint' => $endpoint,
                ]),
            ])->save();

            return [
                'ok' => true,
                'status' => OltDevice::STATUS_ONLINE,
                'message' => "OLT online ({$endpoint})",
                'onu_online' => $onuStats['online'],
                'onu_offline' => $onuStats['offline'],
                'avg_rx_power' => $onuStats['avg_rx'],
            ];
        } catch (\Throwable $e) {
            Log::warning('olt.poll.failed', [
                'device_id' => $device->id,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->markPolled($device, OltDevice::STATUS_OFFLINE, 'Tidak dapat dijangkau: '.$e->getMessage());
        }
    }

    /**
     * @return array{polled:int,online:int,offline:int}
     */
    public function pollTenant(Tenant $tenant, ?int $routerId = null, ?MikrotikService $mikrotik = null): array
    {
        if ($routerId && $mikrotik) {
            $router = Router::query()->find($routerId);
            if ($router) {
                $this->syncFromRouter($tenant, $router, $mikrotik);
            }
        }

        $query = OltDevice::query()->where('tenant_id', $tenant->id);
        if ($routerId) {
            $query->where('router_id', $routerId);
        }

        $summary = ['polled' => 0, 'online' => 0, 'offline' => 0];
        foreach ($query->get() as $device) {
            $result = $this->pollDevice($device);
            $summary['polled']++;
            if (($result['status'] ?? '') === OltDevice::STATUS_ONLINE) {
                $summary['online']++;
            } else {
                $summary['offline']++;
            }
        }

        return $summary;
    }

    protected function resolvePollEndpoint(OltDevice $device): ?string
    {
        $account = TunnelAccount::query()
            ->where('tenant_id', $device->tenant_id)
            ->with(['portMaps', 'node'])
            ->first();

        if (! $account) {
            return null;
        }

        $oltMap = $account->portMaps->firstWhere('label', 'OLT');
        if ($oltMap instanceof TunnelPortMap && $oltMap->is_enabled) {
            return $oltMap->endpoint();
        }

        return null;
    }

    /**
     * @return array{online:int,offline:int,avg_rx:?float}
     */
    protected function estimateOnuStats(OltDevice $device, string $body): array
    {
        $rxValues = [];
        if (preg_match_all('/(-?\d{1,2}\.\d)\s*dBm/i', $body, $matches)) {
            foreach ($matches[1] as $raw) {
                $val = (float) $raw;
                if ($val <= 0 && $val >= -40) {
                    $rxValues[] = $val;
                }
            }
        }

        $online = $device->onu_online_count;
        $offline = $device->onu_offline_count;
        $avgRx = $device->avg_rx_power;

        if ($rxValues !== []) {
            $avgRx = round(array_sum($rxValues) / count($rxValues), 1);
            $online = count($rxValues);
            $offline = max(0, (int) $device->onu_offline_count);
        }

        return [
            'online' => (int) $online,
            'offline' => (int) $offline,
            'avg_rx' => $avgRx,
        ];
    }

    /**
     * @return array{ok:bool,status:string,message:string}
     */
    protected function markPolled(OltDevice $device, string $status, string $message): array
    {
        $device->forceFill([
            'status' => $status,
            'last_polled_at' => wib_now(),
            'poll_message' => $message,
        ])->save();

        return [
            'ok' => $status === OltDevice::STATUS_ONLINE,
            'status' => $status,
            'message' => $message,
        ];
    }
}
