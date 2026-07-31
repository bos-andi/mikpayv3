<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdpPort extends Model
{
    protected $fillable = [
        'odp_id',
        'port_number',
        'customer_name',
        'latitude',
        'longitude',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'port_number' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function isOccupied(): bool
    {
        return filled($this->customer_name);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
