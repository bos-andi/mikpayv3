<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OltDevice extends Model
{
    public const STATUS_ONLINE = 'online';

    public const STATUS_OFFLINE = 'offline';

    public const STATUS_UNKNOWN = 'unknown';

    protected $fillable = [
        'tenant_id',
        'router_id',
        'odp_id',
        'name',
        'vendor',
        'management_ip',
        'web_port',
        'pon_count',
        'status',
        'avg_rx_power',
        'onu_online_count',
        'onu_offline_count',
        'last_polled_at',
        'poll_message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'web_port' => 'integer',
            'pon_count' => 'integer',
            'avg_rx_power' => 'float',
            'onu_online_count' => 'integer',
            'onu_offline_count' => 'integer',
            'last_polled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ONLINE => 'ONLINE',
            self::STATUS_OFFLINE => 'OFFLINE',
            default => 'BELUM DIPOLL',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ONLINE => 'emerald',
            self::STATUS_OFFLINE => 'rose',
            default => 'slate',
        };
    }
}
