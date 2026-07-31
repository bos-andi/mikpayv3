<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odp extends Model
{
    public const TYPE_ODP = 'odp';

    public const TYPE_ODC = 'odc';

    public const TYPE_OLT = 'olt';

    /** @var list<int> */
    public const RATIO_OPTIONS = [2, 4, 8, 16, 32, 64];

    protected $fillable = [
        'tenant_id',
        'router_id',
        'parent_id',
        'type',
        'code',
        'name',
        'latitude',
        'longitude',
        'port_count',
        'pon_label',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'port_count' => 'integer',
            'parent_id' => 'integer',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function ports(): HasMany
    {
        return $this->hasMany(OdpPort::class)->orderBy('port_number');
    }

    public function isOlt(): bool
    {
        return $this->type === self::TYPE_OLT;
    }

    public function isOdc(): bool
    {
        return $this->type === self::TYPE_ODC;
    }

    public function isOdp(): bool
    {
        return $this->type === self::TYPE_ODP;
    }

    /** ODP punya mapping pelanggan per port. */
    public function hasCustomerPorts(): bool
    {
        return $this->isOdp();
    }

    /** ODP/ODC punya rasio splitter. */
    public function hasRatio(): bool
    {
        return $this->isOdp() || $this->isOdc();
    }

    public function usedPortCount(): int
    {
        if (! $this->hasCustomerPorts()) {
            return $this->children->count();
        }

        return $this->ports->filter(fn (OdpPort $p) => filled($p->customer_name))->count();
    }

    public function displayLabel(): string
    {
        return $this->name ?: $this->code;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_OLT => 'OLT',
            self::TYPE_ODC => 'ODC',
            default => 'ODP',
        };
    }

    public function ratioLabel(): ?string
    {
        if (! $this->hasRatio() || (int) $this->port_count < 1) {
            return null;
        }

        return '1 : '.$this->port_count;
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [self::TYPE_OLT, self::TYPE_ODC, self::TYPE_ODP];
    }
}
