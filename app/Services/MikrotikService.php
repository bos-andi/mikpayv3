<?php

namespace App\Services;

use App\Models\Router;
use App\Services\RouterOS\RouterosAPI;
use Illuminate\Support\Facades\Log;

class MikrotikService
{
    protected ?RouterosAPI $api = null;

    protected ?Router $router = null;

    public function connectRouter(Router $router): bool
    {
        $this->router = $router;

        $parts = $router->getHostParts();
        $ip = $parts['ip'];
        $port = $parts['port'] ?: (int) config('mikpay.router.api_port', 8728);

        $this->api = new RouterosAPI;
        $this->api->port = $port;
        $this->api->ssl = (bool) ($router->use_ssl ?: config('mikpay.router.ssl', false));
        $this->api->timeout = (int) config('mikpay.router.timeout', 3);
        $this->api->readMaxSeconds = (int) config('mikpay.router.read_timeout', 45);

        $password = $router->plainPassword();

        $ok = $this->api->connect($ip, $router->username, $password);

        if (! $ok) {
            Log::warning('MikrotikService: connect failed', [
                'router_id' => $router->id,
                'host' => $router->endpointLabel(),
                'error' => $this->api->error_str ?? null,
            ]);
        }

        return $ok;
    }

    /**
     * @return array{ok: bool, message: string, identity?: string|null}
     */
    public function testConnection(Router $router): array
    {
        try {
            if (! $this->connectRouter($router)) {
                $err = $this->api->error_str ?: 'Tidak bisa connect ke router.';

                return [
                    'ok' => false,
                    'message' => $err.' Pastikan IP/port, username/password, dan API MikroTik (8728) aktif.',
                ];
            }

            $identity = null;
            $res = $this->comm('/system/identity/print');
            if (is_array($res) && isset($res[0]['name'])) {
                $identity = $res[0]['name'];
            }

            $this->disconnect();

            return [
                'ok' => true,
                'message' => $identity
                    ? "Terhubung. Identity: {$identity}"
                    : 'Terhubung ke API MikroTik.',
                'identity' => $identity,
            ];
        } catch (\Throwable $e) {
            $this->disconnect();

            return [
                'ok' => false,
                'message' => 'Error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Ambil daftar PPP secret + status online dari router aktif.
     *
     * @return array{
     *     ok: bool,
     *     message: string,
     *     customers: array<int, array<string, mixed>>,
     *     profiles: array<int, string>,
     *     stats: array{total: int, online: int, offline: int, isolir: int}
     * }
     */
    public function fetchPppCustomers(Router $router): array
    {
        $empty = [
            'ok' => false,
            'message' => '',
            'customers' => [],
            'profiles' => [],
            'stats' => ['total' => 0, 'online' => 0, 'offline' => 0, 'isolir' => 0],
        ];

        try {
            if (! $this->connectRouter($router)) {
                $err = $this->api->error_str ?: 'Tidak bisa connect ke router.';

                return array_merge($empty, [
                    'message' => $err.' Pastikan router aktif dan API MikroTik bisa dijangkau.',
                ]);
            }

            $secrets = $this->comm('/ppp/secret/print');
            $secrets = is_array($secrets) ? $secrets : [];
            $activeList = $this->comm('/ppp/active/print');
            $activeList = is_array($activeList) ? $activeList : [];
            $profileRows = $this->comm('/ppp/profile/print');
            $profileRows = is_array($profileRows) ? $profileRows : [];

            $onlineByName = [];
            $activeMeta = [];
            foreach ($activeList as $a) {
                $name = (string) ($a['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $key = strtolower($name);
                $onlineByName[$key] = true;
                $activeMeta[$key] = [
                    'address' => $a['address'] ?? ($a['caller-id'] ?? null),
                    'uptime' => $a['uptime'] ?? null,
                    'service' => $a['service'] ?? null,
                ];
            }

            $profiles = [];
            foreach ($profileRows as $p) {
                $n = (string) ($p['name'] ?? '');
                if ($n !== '') {
                    $profiles[] = $n;
                }
            }
            sort($profiles);

            $customers = [];
            $online = 0;
            $isolir = 0;

            foreach ($secrets as $secret) {
                $name = (string) ($secret['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $key = strtolower($name);
                $isOnline = isset($onlineByName[$key]);
                $isDisabled = $this->isYes($secret['disabled'] ?? 'no');
                $profile = (string) ($secret['profile'] ?? '');
                $profileLooksIsolir = (bool) preg_match('/isolir/i', $profile);
                $isIsolir = $isDisabled || $profileLooksIsolir;

                if ($isOnline) {
                    $online++;
                }
                if ($isIsolir) {
                    $isolir++;
                }

                $meta = $activeMeta[$key] ?? [];

                $customers[] = [
                    'id' => (string) ($secret['.id'] ?? ''),
                    'name' => $name,
                    'profile' => $profile,
                    'service' => (string) ($secret['service'] ?? 'pppoe'),
                    'comment' => (string) ($secret['comment'] ?? ''),
                    'local_address' => (string) ($secret['local-address'] ?? ''),
                    'remote_address' => (string) ($secret['remote-address'] ?? ''),
                    'disabled' => $isDisabled,
                    'online' => $isOnline,
                    'isolir' => $isIsolir,
                    'ip' => $meta['address'] ?? (($secret['remote-address'] ?? '') ?: null),
                    'uptime' => $meta['uptime'] ?? null,
                ];
            }

            usort($customers, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

            $total = count($customers);
            $this->disconnect();

            return [
                'ok' => true,
                'message' => "Berhasil memuat {$total} pelanggan dari ".$router->displayName(),
                'customers' => $customers,
                'profiles' => $profiles,
                'stats' => [
                    'total' => $total,
                    'online' => $online,
                    'offline' => max(0, $total - $online),
                    'isolir' => $isolir,
                ],
            ];
        } catch (\Throwable $e) {
            $this->disconnect();
            Log::error('MikrotikService: fetchPppCustomers failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            return array_merge($empty, [
                'message' => 'Error: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Isolir / buka: set disabled=yes|no + putus session aktif saat isolir.
     *
     * @return array{ok: bool, message: string}
     */
    public function setSecretIsolir(Router $router, string $secretId, string $secretName, bool $isolir): array
    {
        try {
            if (! $this->connectRouter($router)) {
                $err = $this->api->error_str ?: 'Tidak bisa connect ke router.';

                return ['ok' => false, 'message' => $err];
            }

            $this->comm('/ppp/secret/set', [
                '.id' => $secretId,
                'disabled' => $isolir ? 'yes' : 'no',
            ]);

            if ($isolir) {
                $this->removeActiveByName($secretName);
            }

            $this->disconnect();

            return [
                'ok' => true,
                'message' => $isolir
                    ? "“{$secretName}” diisolir (disabled) dan session aktif diputus."
                    : "“{$secretName}” dibuka kembali (enabled).",
            ];
        } catch (\Throwable $e) {
            $this->disconnect();

            return ['ok' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }

    /**
     * Ubah PPP profile secret di MikroTik. Session aktif diputus agar profile baru terpakai.
     *
     * @return array{ok: bool, message: string}
     */
    public function setSecretProfile(Router $router, string $secretId, string $secretName, string $profile): array
    {
        $profile = trim($profile);
        if ($profile === '' || $secretId === '') {
            return ['ok' => false, 'message' => 'Profile atau ID secret tidak valid.'];
        }

        try {
            if (! $this->connectRouter($router)) {
                $err = $this->api->error_str ?: 'Tidak bisa connect ke router.';

                return ['ok' => false, 'message' => $err];
            }

            $this->comm('/ppp/secret/set', [
                '.id' => $secretId,
                'profile' => $profile,
            ]);

            // Putus session agar profile baru aktif saat reconnect.
            $this->removeActiveByName($secretName);

            $this->disconnect();

            return [
                'ok' => true,
                'message' => "Profile “{$secretName}” diubah ke “{$profile}” di MikroTik.",
            ];
        } catch (\Throwable $e) {
            $this->disconnect();

            return ['ok' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }

    /**
     * Cari satu PPP secret by name.
     *
     * @return array{ok: bool, message: string, secret: ?array<string, mixed>, profiles: array<int, string>}
     */
    public function findSecretByName(Router $router, string $secretName): array
    {
        $result = $this->fetchPppCustomers($router);
        if (! $result['ok']) {
            return [
                'ok' => false,
                'message' => $result['message'],
                'secret' => null,
                'profiles' => [],
            ];
        }

        $needle = strtolower(trim($secretName));
        $secret = collect($result['customers'])->first(
            fn ($c) => strtolower((string) ($c['name'] ?? '')) === $needle
        );

        return [
            'ok' => true,
            'message' => $secret ? 'OK' : 'Secret tidak ditemukan di MikroTik.',
            'secret' => $secret,
            'profiles' => $result['profiles'],
        ];
    }

    /**
     * Live bandwidth seperti WinBox Interface → Traffic Monitor.
     * Download = Rx (bits/s), Upload = Tx (bits/s).
     *
     * @return array{
     *     ok: bool,
     *     message: string,
     *     interface: ?string,
     *     rx_bps: float,
     *     tx_bps: float,
     *     interfaces: array<int, array{name: string, type: string, running: bool, comment: string}>
     * }
     */
    public function monitorInterfaceTraffic(Router $router, ?string $interface = null): array
    {
        $empty = [
            'ok' => false,
            'message' => '',
            'interface' => null,
            'rx_bps' => 0.0,
            'tx_bps' => 0.0,
            'interfaces' => [],
        ];

        try {
            if (! $this->connectRouter($router)) {
                $err = $this->api->error_str ?: 'Tidak bisa connect ke router.';

                return array_merge($empty, [
                    'message' => $err.' Pastikan API MikroTik bisa dijangkau.',
                ]);
            }

            $interfaces = $this->listInterfaces();
            $chosen = $this->resolveTrafficInterface($interfaces, $interface);

            if ($chosen === null) {
                $this->disconnect();

                return array_merge($empty, [
                    'message' => 'Tidak ada interface yang bisa dimonitor.',
                    'interfaces' => $interfaces,
                ]);
            }

            $sample = $this->sampleMonitorTraffic($chosen);
            if ($sample === null) {
                $sample = $this->sampleTrafficFromCounters($chosen);
            }

            $this->disconnect();

            if ($sample === null) {
                return array_merge($empty, [
                    'message' => "Gagal membaca traffic interface “{$chosen}”.",
                    'interface' => $chosen,
                    'interfaces' => $interfaces,
                ]);
            }

            return [
                'ok' => true,
                'message' => 'OK',
                'interface' => $chosen,
                'rx_bps' => $sample['rx_bps'],
                'tx_bps' => $sample['tx_bps'],
                'interfaces' => $interfaces,
            ];
        } catch (\Throwable $e) {
            $this->disconnect();
            Log::error('MikrotikService: monitorInterfaceTraffic failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            return array_merge($empty, [
                'message' => 'Error: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, array{name: string, type: string, running: bool, comment: string}>
     */
    protected function listInterfaces(): array
    {
        $rows = $this->comm('/interface/print');
        $rows = is_array($rows) ? $rows : [];
        $out = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '' || strcasecmp($name, 'lo') === 0) {
                continue;
            }

            // Skip dynamic PPP interfaces (<pppoe-user@domain>) — clutter dropdown.
            $type = strtolower((string) ($row['type'] ?? ''));
            if ($type === 'pppoe-in' || preg_match('/^<.*>$/', $name)) {
                continue;
            }

            $out[] = [
                'name' => $name,
                'type' => (string) ($row['type'] ?? ''),
                'running' => $this->isYes($row['running'] ?? 'false'),
                'comment' => (string) ($row['comment'] ?? ''),
            ];
        }

        usort($out, function (array $a, array $b): int {
            $score = static function (array $iface): int {
                $type = strtolower($iface['type']);
                $name = strtolower($iface['name']);
                if (str_contains($type, 'ether') || str_starts_with($name, 'ether') || str_starts_with($name, 'sfp')) {
                    return 0;
                }
                if ($type === 'bridge' || $type === 'vlan' || $type === 'bonding') {
                    return 1;
                }

                return 2;
            };

            $sa = $score($a);
            $sb = $score($b);
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }
            if ($a['running'] !== $b['running']) {
                return $a['running'] ? -1 : 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $out;
    }

    /**
     * @param  array<int, array{name: string, type: string, running: bool, comment: string}>  $interfaces
     */
    protected function resolveTrafficInterface(array $interfaces, ?string $requested): ?string
    {
        $names = [];
        foreach ($interfaces as $iface) {
            $names[strtolower($iface['name'])] = $iface['name'];
        }

        $requested = trim((string) $requested);
        if ($requested !== '' && isset($names[strtolower($requested)])) {
            return $names[strtolower($requested)];
        }

        $wan = $this->detectDefaultRouteInterface();
        if ($wan !== null && isset($names[strtolower($wan)])) {
            return $names[strtolower($wan)];
        }

        foreach ($interfaces as $iface) {
            if (! $iface['running']) {
                continue;
            }
            $type = strtolower($iface['type']);
            $name = strtolower($iface['name']);
            if (
                str_contains($type, 'ether')
                || str_contains($type, 'sfp')
                || str_starts_with($name, 'ether')
                || str_starts_with($name, 'sfp')
                || str_starts_with($name, 'combo')
            ) {
                return $iface['name'];
            }
        }

        foreach ($interfaces as $iface) {
            if ($iface['running']) {
                return $iface['name'];
            }
        }

        return $interfaces[0]['name'] ?? null;
    }

    protected function detectDefaultRouteInterface(): ?string
    {
        $routes = $this->comm('/ip/route/print');
        $routes = is_array($routes) ? $routes : [];

        foreach ($routes as $route) {
            $dst = (string) ($route['dst-address'] ?? '');
            if ($dst !== '0.0.0.0/0') {
                continue;
            }
            if (isset($route['active']) && ! $this->isYes($route['active'])) {
                continue;
            }

            foreach (['immediate-gw', 'gateway', 'gateway-status'] as $key) {
                $raw = (string) ($route[$key] ?? '');
                if ($raw === '') {
                    continue;
                }
                // e.g. 1.2.3.4%ether1 or ether1
                if (preg_match('/%([^%\s,]+)/', $raw, $m)) {
                    return $m[1];
                }
                if (preg_match('/\b((?:ether|sfp|combo|bridge|vlan|bonding|pppoe)[^\s,]*)/i', $raw, $m)) {
                    return $m[1];
                }
            }
        }

        return null;
    }

    /**
     * @return array{rx_bps: float, tx_bps: float}|null
     */
    protected function sampleMonitorTraffic(string $interface): ?array
    {
        $rows = $this->comm('/interface/monitor-traffic', [
            'interface' => $interface,
            'once' => '',
        ]);

        if (! is_array($rows) || $rows === []) {
            return null;
        }

        $row = $rows[0] ?? null;
        if (! is_array($row)) {
            return null;
        }

        $rx = $row['rx-bits-per-second'] ?? $row['rx-bits'] ?? null;
        $tx = $row['tx-bits-per-second'] ?? $row['tx-bits'] ?? null;
        if ($rx === null && $tx === null) {
            return null;
        }

        return [
            'rx_bps' => max(0.0, (float) $rx),
            'tx_bps' => max(0.0, (float) $tx),
        ];
    }

    /**
     * Fallback: hitung rate dari delta rx-byte/tx-byte (mirip graph MikroTik).
     *
     * @return array{rx_bps: float, tx_bps: float}|null
     */
    protected function sampleTrafficFromCounters(string $interface): ?array
    {
        $first = $this->readInterfaceCounters($interface);
        if ($first === null) {
            return null;
        }

        usleep(800000);

        $second = $this->readInterfaceCounters($interface);
        if ($second === null) {
            return null;
        }

        $dt = max(0.2, $second['t'] - $first['t']);
        $rxDelta = $second['rx'] - $first['rx'];
        $txDelta = $second['tx'] - $first['tx'];
        if ($rxDelta < 0) {
            $rxDelta = 0;
        }
        if ($txDelta < 0) {
            $txDelta = 0;
        }

        return [
            'rx_bps' => ($rxDelta * 8) / $dt,
            'tx_bps' => ($txDelta * 8) / $dt,
        ];
    }

    /**
     * @return array{rx: float, tx: float, t: float}|null
     */
    protected function readInterfaceCounters(string $interface): ?array
    {
        $rows = $this->comm('/interface/print', [
            '?name' => $interface,
            '.proplist' => 'name,rx-byte,tx-byte',
        ]);

        if (! is_array($rows) || $rows === []) {
            $rows = $this->comm('/interface/print', ['?name' => $interface]);
        }

        if (! is_array($rows) || $rows === []) {
            return null;
        }

        $row = $rows[0] ?? null;
        if (! is_array($row)) {
            return null;
        }

        return [
            'rx' => (float) ($row['rx-byte'] ?? 0),
            'tx' => (float) ($row['tx-byte'] ?? 0),
            't' => microtime(true),
        ];
    }

    protected function removeActiveByName(string $secretName): void
    {
        $normalized = strtolower(trim($secretName));
        $activeList = $this->comm('/ppp/active/print');
        if (! is_array($activeList)) {
            return;
        }

        foreach ($activeList as $active) {
            $activeName = strtolower(trim((string) ($active['name'] ?? '')));
            $activeId = (string) ($active['.id'] ?? '');
            if ($activeName === $normalized && $activeId !== '') {
                $this->comm('/ppp/active/remove', ['.id' => $activeId]);
            }
        }
    }

    protected function isYes(mixed $value): bool
    {
        $v = strtolower(trim((string) $value));

        return in_array($v, ['yes', 'true', '1'], true);
    }

    public function disconnect(): void
    {
        if ($this->api) {
            $this->api->disconnect();
            $this->api = null;
        }
        $this->router = null;
    }

    public function isConnected(): bool
    {
        return $this->api && $this->api->connected;
    }

    /**
     * @return array|mixed
     */
    public function comm(string $path, array $attrs = [])
    {
        if (! $this->api || ! $this->api->connected) {
            return [];
        }

        return $this->api->comm($path, $attrs);
    }

    public function getRouter(): ?Router
    {
        return $this->router;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
