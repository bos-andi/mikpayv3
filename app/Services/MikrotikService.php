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
     * Tambah PPP secret baru di MikroTik.
     *
     * @param  array{name: string, password: string, service?: string, profile?: string, local_address?: ?string, remote_address?: ?string, comment?: ?string}  $data
     * @return array{ok: bool, message: string, id: ?string}
     */
    public function addPppSecret(Router $router, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $profile = trim((string) ($data['profile'] ?? ''));
        if ($name === '' || (string) ($data['password'] ?? '') === '') {
            return ['ok' => false, 'message' => 'Username dan password PPP wajib diisi.', 'id' => null];
        }

        try {
            if (! $this->connectRouter($router)) {
                return ['ok' => false, 'message' => $this->api->error_str ?: 'Tidak bisa connect ke router.', 'id' => null];
            }

            $existing = $this->normalizeHotspotList($this->comm('/ppp/secret/print', ['?name' => $name]));
            if ($existing !== []) {
                $this->disconnect();

                return ['ok' => false, 'message' => "Username PPP “{$name}” sudah ada di MikroTik.", 'id' => null];
            }

            if ($profile !== '') {
                $profiles = array_column($this->normalizeHotspotList($this->comm('/ppp/profile/print')), 'name');
                if (! in_array($profile, $profiles, true)) {
                    $this->disconnect();

                    return ['ok' => false, 'message' => "Profile “{$profile}” tidak ditemukan di MikroTik.", 'id' => null];
                }
            }

            $payload = array_filter([
                'name' => $name,
                'password' => (string) $data['password'],
                'service' => (string) ($data['service'] ?? 'pppoe'),
                'profile' => $profile,
                'local-address' => trim((string) ($data['local_address'] ?? '')),
                'remote-address' => trim((string) ($data['remote_address'] ?? '')),
                'comment' => trim((string) ($data['comment'] ?? '')),
            ], fn ($v) => $v !== '');

            $result = $this->comm('/ppp/secret/add', $payload);
            $this->disconnect();

            if (is_array($result) && (isset($result['!trap']) || isset($result['!fatal']))) {
                $err = $result['!trap'][0]['message'] ?? $result['!fatal'][0]['message'] ?? 'MikroTik menolak permintaan.';

                return ['ok' => false, 'message' => 'MikroTik: '.$err, 'id' => null];
            }

            return [
                'ok' => true,
                'message' => "PPP “{$name}” berhasil ditambahkan ke MikroTik.",
                'id' => is_string($result) ? $result : null,
            ];
        } catch (\Throwable $e) {
            $this->disconnect();
            Log::error('MikrotikService: addPppSecret failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Error: '.$e->getMessage(), 'id' => null];
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

    /**
     * @return array{ok: bool, message: string, users: list<array<string, mixed>>, profiles: list<array<string, mixed>>, servers: list<array<string, mixed>>}
     */
    public function fetchHotspotUsers(Router $router, array $filters = []): array
    {
        $empty = ['ok' => false, 'message' => '', 'users' => [], 'profiles' => [], 'servers' => []];

        try {
            if (! $this->connectRouter($router)) {
                return array_merge($empty, ['message' => $this->api->error_str ?: 'Tidak bisa connect ke router.']);
            }

            $query = [];
            if (! empty($filters['exp'])) {
                $query['?limit-uptime'] = '1s';
            } elseif (! empty($filters['comment'])) {
                $query['?comment'] = (string) $filters['comment'];
            } elseif (! empty($filters['profile']) && $filters['profile'] !== 'all') {
                $query['?profile'] = (string) $filters['profile'];
            }

            $users = $this->normalizeHotspotList($query === []
                ? $this->comm('/ip/hotspot/user/print')
                : $this->comm('/ip/hotspot/user/print', $query));
            $profiles = $this->normalizeHotspotList($this->comm('/ip/hotspot/user/profile/print'));
            $servers = $this->normalizeHotspotList($this->comm('/ip/hotspot/print'));
            $this->disconnect();

            return [
                'ok' => true,
                'message' => '',
                'users' => $users,
                'profiles' => $profiles,
                'servers' => $servers,
            ];
        } catch (\Throwable $e) {
            $this->disconnect();

            return array_merge($empty, ['message' => $e->getMessage()]);
        }
    }

    /**
     * @return array{ok: bool, message: string, profiles: list<array<string, mixed>>, pools: list<array<string, mixed>>, queues: list<array<string, mixed>>}
     */
    public function fetchHotspotProfiles(Router $router): array
    {
        $empty = ['ok' => false, 'message' => '', 'profiles' => [], 'pools' => [], 'queues' => []];

        try {
            if (! $this->connectRouter($router)) {
                return array_merge($empty, ['message' => $this->api->error_str ?: 'Tidak bisa connect ke router.']);
            }

            $profiles = $this->normalizeHotspotList($this->comm('/ip/hotspot/user/profile/print'));
            $pools = $this->normalizeHotspotList($this->comm('/ip/pool/print'));
            $queues = $this->normalizeHotspotList($this->comm('/queue/simple/print', ['?dynamic' => 'false']));
            $this->disconnect();

            return [
                'ok' => true,
                'message' => '',
                'profiles' => $profiles,
                'pools' => $pools,
                'queues' => $queues,
            ];
        } catch (\Throwable $e) {
            $this->disconnect();

            return array_merge($empty, ['message' => $e->getMessage()]);
        }
    }

    /**
     * @return array{ok: bool, message: string, active: list<array<string, mixed>>}
     */
    public function fetchHotspotActive(Router $router): array
    {
        $empty = ['ok' => false, 'message' => '', 'active' => []];

        try {
            if (! $this->connectRouter($router)) {
                return array_merge($empty, ['message' => $this->api->error_str ?: 'Tidak bisa connect ke router.']);
            }

            $active = $this->normalizeHotspotList($this->comm('/ip/hotspot/active/print'));
            $this->disconnect();

            return ['ok' => true, 'message' => '', 'active' => $active];
        } catch (\Throwable $e) {
            $this->disconnect();

            return array_merge($empty, ['message' => $e->getMessage()]);
        }
    }

    /**
     * @return array{ok: bool, message: string, rows: list<array<string, mixed>>, total: int, owner?: string}
     */
    public function fetchHotspotSelling(Router $router, ?string $owner = null): array
    {
        $empty = ['ok' => false, 'message' => '', 'rows' => [], 'total' => 0];

        try {
            if (! $this->connectRouter($router)) {
                return array_merge($empty, ['message' => $this->api->error_str ?: 'Tidak bisa connect ke router.']);
            }

            $ownerKey = $owner ?: (strtolower(now()->format('M')).now()->format('Y'));
            $scripts = $this->normalizeHotspotList($this->comm('/system/script/print', [
                '?owner' => $ownerKey,
            ]));

            $rows = [];
            $total = 0;
            foreach ($scripts as $s) {
                $name = (string) ($s['name'] ?? '');
                $parts = explode('-|-', $name);
                if (count($parts) < 4) {
                    continue;
                }
                $price = (int) preg_replace('/\D/', '', $parts[3] ?? '0');
                $total += $price;
                $rows[] = [
                    'date' => $parts[0] ?? '',
                    'time' => $parts[1] ?? '',
                    'user' => $parts[2] ?? '',
                    'price' => $price,
                    'mac' => $parts[5] ?? '',
                    'validity' => $parts[6] ?? '',
                    'profile' => $parts[7] ?? '',
                ];
            }

            $this->disconnect();

            return ['ok' => true, 'message' => '', 'rows' => $rows, 'total' => $total, 'owner' => $ownerKey];
        } catch (\Throwable $e) {
            $this->disconnect();

            return array_merge($empty, ['message' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{ok: bool, message: string}
     */
    public function addHotspotUser(Router $router, array $params): array
    {
        try {
            if (! $this->connectRouter($router)) {
                return ['ok' => false, 'message' => $this->api->error_str ?: 'Tidak bisa connect ke router.'];
            }
            $result = $this->comm('/ip/hotspot/user/add', $params);
            $this->disconnect();

            if (is_array($result) && (isset($result['!trap']) || isset($result['!fatal']))) {
                $err = $result['!trap'][0]['message'] ?? $result['!fatal'][0]['message'] ?? 'MikroTik menolak permintaan.';

                return ['ok' => false, 'message' => 'MikroTik: '.$err];
            }

            return ['ok' => true, 'message' => 'User hotspot ditambahkan.'];
        } catch (\Throwable $e) {
            $this->disconnect();

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function hotspotUserAction(Router $router, string $action, string $id): array
    {
        if (! in_array($action, ['remove', 'enable', 'disable'], true)) {
            return ['ok' => false, 'message' => 'Aksi tidak valid.'];
        }

        try {
            if (! $this->connectRouter($router)) {
                return ['ok' => false, 'message' => $this->api->error_str ?: 'Tidak bisa connect ke router.'];
            }
            $this->comm('/ip/hotspot/user/'.$action, ['.id' => $id]);
            $this->disconnect();

            return ['ok' => true, 'message' => 'Berhasil.'];
        } catch (\Throwable $e) {
            $this->disconnect();

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array{scheduler_name?: string, scheduler_event?: string, start_time?: string, interval?: string}|null  $monitor
     * @return array{ok: bool, message: string}
     */
    public function addHotspotProfile(Router $router, array $profile, ?array $monitor = null): array
    {
        try {
            if (! $this->connectRouter($router)) {
                return ['ok' => false, 'message' => $this->api->error_str ?: 'Tidak bisa connect ke router.'];
            }

            $this->comm('/ip/hotspot/user/profile/add', $profile);

            if ($monitor && ! empty($monitor['scheduler_name']) && ! empty($monitor['scheduler_event'])) {
                $name = (string) $monitor['scheduler_name'];
                $existing = $this->normalizeHotspotList($this->comm('/system/scheduler/print', ['?name' => $name]));
                $payload = [
                    'name' => $name,
                    'start-time' => $monitor['start_time'] ?? ('0'.random_int(1, 5).':'.random_int(10, 59).':'.random_int(10, 59)),
                    'interval' => $monitor['interval'] ?? ('00:02:'.random_int(10, 59)),
                    'on-event' => $monitor['scheduler_event'],
                    'disabled' => 'no',
                    'comment' => 'MikPay Monitor Profile '.$name,
                ];
                if (! empty($existing[0]['.id'])) {
                    $payload['.id'] = $existing[0]['.id'];
                    $this->comm('/system/scheduler/set', $payload);
                } else {
                    $this->comm('/system/scheduler/add', $payload);
                }
            }

            $this->disconnect();

            return ['ok' => true, 'message' => 'Profile hotspot ditambahkan.'];
        } catch (\Throwable $e) {
            $this->disconnect();

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function hotspotUserExists(Router $router, string $name): bool
    {
        try {
            if (! $this->isConnected() && ! $this->connectRouter($router)) {
                return false;
            }
            $rows = $this->normalizeHotspotList($this->comm('/ip/hotspot/user/print', ['?name' => $name]));

            return $rows !== [];
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Batch add hotspot users (satu koneksi).
     *
     * @param  list<array<string, mixed>>  $users
     * @return array{ok: bool, message: string, created: int}
     */
    public function addHotspotUsersBatch(Router $router, array $users): array
    {
        try {
            if (! $this->connectRouter($router)) {
                return ['ok' => false, 'message' => $this->api->error_str ?: 'Tidak bisa connect ke router.', 'created' => 0];
            }

            $created = 0;
            foreach ($users as $params) {
                $name = (string) ($params['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $exists = $this->normalizeHotspotList($this->comm('/ip/hotspot/user/print', ['?name' => $name]));
                if ($exists !== []) {
                    continue;
                }
                $this->comm('/ip/hotspot/user/add', $params);
                $created++;
            }

            $this->disconnect();

            return ['ok' => true, 'message' => "Berhasil membuat {$created} voucher.", 'created' => $created];
        } catch (\Throwable $e) {
            $this->disconnect();

            return ['ok' => false, 'message' => $e->getMessage(), 'created' => 0];
        }
    }

    /**
     * Hapus banyak user by comment atau expired (limit-uptime=1s).
     *
     * @return array{ok: bool, message: string, removed: int}
     */
    public function removeHotspotUsers(Router $router, array $query): array
    {
        try {
            if (! $this->connectRouter($router)) {
                return ['ok' => false, 'message' => $this->api->error_str ?: 'Tidak bisa connect ke router.', 'removed' => 0];
            }

            $users = $this->normalizeHotspotList($this->comm('/ip/hotspot/user/print', $query));
            $removed = 0;
            foreach ($users as $u) {
                if (empty($u['.id'])) {
                    continue;
                }
                $this->comm('/ip/hotspot/user/remove', ['.id' => $u['.id']]);
                $removed++;
            }
            $this->disconnect();

            return ['ok' => true, 'message' => "{$removed} user dihapus.", 'removed' => $removed];
        } catch (\Throwable $e) {
            $this->disconnect();

            return ['ok' => false, 'message' => $e->getMessage(), 'removed' => 0];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function normalizeHotspotList(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (isset($row['!trap']) || isset($row['!fatal'])) {
                continue;
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Baca rule NAT MikPay OLT dari MikroTik (hasil konfigurasi script tunnel).
     *
     * @return array{
     *     ok: bool,
     *     message: string,
     *     rules: array<int, array{
     *         management_ip: string,
     *         web_port: int,
     *         tunnel_dst_port: int,
     *         comment: string
     *     }>
     * }
     */
    public function fetchMikpayOltNatRules(Router $router): array
    {
        $empty = ['ok' => false, 'message' => '', 'rules' => []];

        try {
            if (! $this->connectRouter($router)) {
                $err = $this->api->error_str ?: 'Tidak bisa connect ke router.';

                return array_merge($empty, [
                    'message' => $err.' Pastikan API MikroTik bisa dijangkau.',
                ]);
            }

            $rows = $this->comm('/ip/firewall/nat/print', ['?chain' => 'dstnat']);
            $rows = is_array($rows) ? $rows : [];
            $rules = [];

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $comment = (string) ($row['comment'] ?? '');
                if (! str_contains(strtoupper($comment), 'MIKPAY') || ! str_contains(strtoupper($comment), 'OLT')) {
                    continue;
                }

                $managementIp = (string) ($row['to-addresses'] ?? $row['to-address'] ?? '');
                if ($managementIp === '') {
                    continue;
                }

                $rules[] = [
                    'management_ip' => $managementIp,
                    'web_port' => max(1, (int) ($row['to-ports'] ?? 80)),
                    'tunnel_dst_port' => max(1, (int) ($row['dst-port'] ?? 25)),
                    'comment' => $comment,
                ];
            }

            $this->disconnect();

            if ($rules === []) {
                return [
                    'ok' => true,
                    'message' => 'Rule NAT MikPay OLT tidak ditemukan. Pastikan script tunnel sudah di-paste dan IP OLT sudah diganti.',
                    'rules' => [],
                ];
            }

            return [
                'ok' => true,
                'message' => count($rules).' rule OLT terdeteksi dari MikroTik.',
                'rules' => $rules,
            ];
        } catch (\Throwable $e) {
            $this->disconnect();

            return array_merge($empty, [
                'message' => 'Gagal membaca NAT OLT: '.$e->getMessage(),
            ]);
        }
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
