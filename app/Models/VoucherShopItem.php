<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherShopItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'label',
        'description',
        'profile',
        'price',
        'limit_uptime',
        'limit_bytes',
        'sort',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'limit_bytes' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
