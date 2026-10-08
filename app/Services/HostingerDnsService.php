<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HostingerDnsService
{
    public function token(): string
    {
        return trim((string) config('hostinger.api_token', ''));
    }

    public function enabled(): bool
    {
        return $this->token() !== ''
            && (bool) config('hostinger.create_tenant_a_record', true);
    }

    public function domain(): string
    {
        return trim((string) config('hostinger.dns_domain', 'mikpay.link'));
    }

    public function targetIp(): string
    {
        $ip = trim((string) config('hostinger.dns_a_ip', ''));
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        $apex = $this->domain();
        $resolved = gethostbyname($apex);
        if (is_string($resolved) && $resolved !== $apex && filter_var($resolved, FILTER_VALIDATE_IP)) {
            return $resolved;
        }

        return '43.156.138.254';
    }

    protected function apiUrl(string $path = ''): string
    {
        return rtrim((string) config('hostinger.api_base'), '/').'/api/dns/v1/zones/'.$this->domain().$path;
    }

    /**
     * Setup DNS lengkap untuk tenant: A record subdomain panel.
     *
     * @return array{ok: bool, message: string, name?: string, ip?: string, fqdn?: string, raw?: mixed}
     */
    public function ensureTenantSubdomain(Tenant $tenant): array
    {
        $name = strtolower(trim((string) $tenant->subdomain));
        if ($name === '' || ! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $name)) {
            return ['ok' => false, 'message' => 'Subdomain tidak valid untuk DNS.'];
        }

        if ($this->token() === '') {
            return ['ok' => false, 'message' => 'HOSTINGER_API_TOKEN kosong.'];
        }

        // Pastikan wildcard tetap ada (aman jika sudah ada).
        $this->upsertARecord('*', $this->targetIp());

        $ip = $this->targetIp();
        $result = $this->upsertARecord($name, $ip);

        if ($result['ok'] ?? false) {
            $fqdn = $name.'.'.$this->domain();
            $tenant->metaSet('dns.hostinger', [
                'name' => $name,
                'type' => 'A',
                'ip' => $ip,
                'domain' => $this->domain(),
                'fqdn' => $fqdn,
                'synced_at' => now()->toIso8601String(),
            ]);
            $tenant->save();
            $result['fqdn'] = $fqdn;
        }

        return $result;
    }

    /**
     * @return array{ok: bool, message: string, name?: string, ip?: string, raw?: mixed}
     */
    public function upsertARecord(string $name, ?string $ip = null): array
    {
        $ip = $ip ?: $this->targetIp();

        return $this->upsertRecord($name, 'A', [$ip], true);
    }

    /**
     * @return array{ok: bool, message: string, raw?: mixed}
     */
    public function upsertTxtRecord(string $name, string $content): array
    {
        return $this->upsertRecord($name, 'TXT', [$content]);
    }

    /**
     * $overwrite=false menambah record ke nama+tipe yang sudah ada (dibutuhkan TXT ACME wildcard + apex).
     *
     * @param  list<string>  $contents
     * @return array{ok: bool, message: string, name?: string, ip?: string, raw?: mixed}
     */
    public function upsertRecord(string $name, string $type, array $contents, bool $overwrite = false): array
    {
        if ($this->token() === '') {
            return ['ok' => false, 'message' => 'HOSTINGER_API_TOKEN kosong.'];
        }

        $name = trim($name);
        $type = strtoupper(trim($type));
        $ttl = max(60, (int) config('hostinger.dns_ttl', 300));
        $records = [];
        foreach ($contents as $content) {
            $content = trim((string) $content);
            if ($content !== '') {
                $records[] = ['content' => $content];
            }
        }
        if ($records === []) {
            return ['ok' => false, 'message' => 'Konten DNS kosong.'];
        }

        $payload = [
            'overwrite' => $overwrite,
            'zone' => [
                [
                    'name' => $name,
                    'type' => $type,
                    'ttl' => $ttl,
                    'records' => $records,
                ],
            ],
        ];

        try {
            $response = Http::timeout(45)
                ->withToken($this->token())
                ->acceptJson()
                ->asJson()
                ->put($this->apiUrl(), $payload);
        } catch (\Throwable $e) {
            Log::warning('hostinger.dns.http_error', [
                'name' => $name,
                'type' => $type,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Gagal menghubungi Hostinger: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            Log::warning('hostinger.dns.failed', [
                'name' => $name,
                'type' => $type,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return [
                'ok' => false,
                'message' => (string) data_get($response->json(), 'message', 'Hostinger menolak update DNS.'),
                'raw' => $response->json() ?? $response->body(),
            ];
        }

        Log::info('hostinger.dns.upserted', [
            'name' => $name,
            'type' => $type,
            'domain' => $this->domain(),
        ]);

        return [
            'ok' => true,
            'message' => "DNS {$type} {$name}.{$this->domain()} disimpan.",
            'name' => $name,
            'ip' => $type === 'A' ? ($contents[0] ?? null) : null,
            'raw' => $response->json(),
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function deleteRecords(string $name, string $type): array
    {
        if ($this->token() === '') {
            return ['ok' => false, 'message' => 'HOSTINGER_API_TOKEN kosong.'];
        }

        $name = trim($name);
        $type = strtoupper(trim($type));

        try {
            $response = Http::timeout(45)
                ->withToken($this->token())
                ->acceptJson()
                ->asJson()
                ->withQueryParameters([
                    'filters' => [
                        ['name' => $name, 'type' => $type],
                    ],
                ])
                ->delete($this->apiUrl());
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'message' => (string) data_get($response->json(), 'message', 'Gagal hapus DNS.'),
            ];
        }

        return ['ok' => true, 'message' => "DNS {$type} {$name}.{$this->domain()} dihapus."];
    }

    public function deleteARecord(string $name): array
    {
        return $this->deleteRecords($name, 'A');
    }

    /**
     * Certbot DNS-01: set TXT _acme-challenge[.sub]
     *
     * CERTBOT_DOMAIN / CERTBOT_VALIDATION diisi env oleh certbot.
     */
    public function certbotAuthHook(?string $domain = null, ?string $validation = null): array
    {
        $domain = $domain ?: (string) getenv('CERTBOT_DOMAIN');
        $validation = $validation ?: (string) getenv('CERTBOT_VALIDATION');
        if ($domain === '' || $validation === '') {
            return ['ok' => false, 'message' => 'CERTBOT_DOMAIN / CERTBOT_VALIDATION kosong.'];
        }

        $recordName = $this->acmeChallengeName($domain);
        $result = $this->upsertTxtRecord($recordName, $validation);
        if ($result['ok'] ?? false) {
            // Tunggu propagasi DNS
            sleep(max(15, (int) config('hostinger.acme_wait_seconds', 30)));
        }

        return $result;
    }

    public function certbotCleanupHook(?string $domain = null): array
    {
        $domain = $domain ?: (string) getenv('CERTBOT_DOMAIN');
        if ($domain === '') {
            return ['ok' => false, 'message' => 'CERTBOT_DOMAIN kosong.'];
        }

        return $this->deleteRecords($this->acmeChallengeName($domain), 'TXT');
    }

    protected function acmeChallengeName(string $domain): string
    {
        $zone = $this->domain();
        $domain = strtolower(trim($domain));

        // *.mikpay.link atau mikpay.link → _acme-challenge
        if ($domain === $zone || $domain === '*.'.$zone) {
            return '_acme-challenge';
        }

        // sub.mikpay.link → _acme-challenge.sub
        if (str_ends_with($domain, '.'.$zone)) {
            $sub = substr($domain, 0, -strlen('.'.$zone));

            return '_acme-challenge.'.$sub;
        }

        return '_acme-challenge';
    }
}
