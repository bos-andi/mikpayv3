<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Router extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'router_name',
        'domain',
        'currency',
        'expiry_days',
        'host',
        'port',
        'username',
        'password',
        'use_ssl',
        'status',
        'is_default',
        'isolir_profile',
        'active_profile',
        'last_check_ok',
        'last_checked_at',
        'last_check_message',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'use_ssl' => 'boolean',
            'is_default' => 'boolean',
            'last_check_ok' => 'boolean',
            'last_checked_at' => 'datetime',
            'port' => 'integer',
            'expiry_days' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['password'] = Crypt::encryptString($value);
    }

    public function plainPassword(): string
    {
        if (empty($this->attributes['password'] ?? null)) {
            return '';
        }

        try {
            return Crypt::decryptString($this->attributes['password']);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Parse host field. Supports "ip:port" in host or separate port column.
     *
     * @return array{ip: string, port: int}
     */
    public function getHostParts(): array
    {
        $raw = trim((string) $this->host);
        $defaultPort = (int) ($this->port ?: config('mikpay.router.api_port', 8728));

        if ($raw === '') {
            return ['ip' => '', 'port' => $defaultPort];
        }

        // host:port (hostname / IPv4). IPv6 with brackets: [addr]:port
        if (preg_match('/^\[([^\]]+)\]:(\d+)$/', $raw, $m)) {
            return ['ip' => $m[1], 'port' => (int) $m[2]];
        }

        if (substr_count($raw, ':') === 1) {
            [$ip, $port] = explode(':', $raw, 2);
            if (is_numeric($port)) {
                return ['ip' => $ip, 'port' => (int) $port];
            }
        }

        return ['ip' => $raw, 'port' => $defaultPort];
    }

    public function hostDisplay(): string
    {
        $parts = $this->getHostParts();

        return $parts['ip'] === ''
            ? ''
            : $parts['ip'].':'.$parts['port'];
    }

    public function endpointLabel(): string
    {
        return $this->hostDisplay();
    }

    public function isEnabled(): bool
    {
        return $this->status === 'enable';
    }

    public function displayName(): string
    {
        return $this->router_name ?: $this->name;
    }
}
