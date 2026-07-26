<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class TunnelAccount extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'tunnel_node_id',
        'username',
        'password_encrypted',
        'protocol',
        'server_host',
        'server_port',
        'tunnel_ip',
        'status',
        'connected_at',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'server_port' => 'integer',
            'connected_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(TunnelNode::class, 'tunnel_node_id');
    }

    public function nodeHostname(): string
    {
        return $this->node?->hostname ?: (string) $this->server_host;
    }

    public function portMaps(): HasMany
    {
        return $this->hasMany(TunnelPortMap::class);
    }

    public function entitlement(): HasOne
    {
        return $this->hasOne(TunnelEntitlement::class)->latestOfMany();
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(TunnelEntitlement::class);
    }

    public function setPlainPassword(string $password): void
    {
        $this->password_encrypted = Crypt::encryptString($password);
    }

    public function plainPassword(): string
    {
        try {
            return Crypt::decryptString((string) $this->password_encrypted);
        } catch (\Throwable) {
            return '';
        }
    }

    public function isUsable(): bool
    {
        return in_array($this->status, ['active', 'pending'], true);
    }

    public function remoteEndpoint(int $remotePort): string
    {
        return $this->nodeHostname().':'.$remotePort;
    }
}
