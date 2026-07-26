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
