<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TunnelNode extends Model
{
    protected $fillable = [
        'code',
        'hostname',
        'label',
        'region',
        'ovpn_port',
        'port_range_start',
        'port_range_end',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'ovpn_port' => 'integer',
            'port_range_start' => 'integer',
            'port_range_end' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(TunnelAccount::class);
    }

    public static function defaultNode(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }
}
