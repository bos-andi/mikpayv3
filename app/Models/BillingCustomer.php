<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingCustomer extends Model
{
    protected $fillable = [
        'tenant_id',
        'router_id',
        'customer_name',
        'public_code',
        'display_name',
        'phone',
        'email',
        'zone',
        'package_label',
        'monthly_fee',
        'due_day',
        'started_at',
        'address',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_day' => 'integer',
            'monthly_fee' => 'integer',
            'started_at' => 'date',
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

    public function isBillingConfigured(): bool
    {
        return (int) $this->due_day >= 1 && (int) $this->due_day <= 31;
    }
}
