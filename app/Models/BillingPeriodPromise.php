<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPeriodPromise extends Model
{
    protected $table = 'billing_period_promises';

    protected $fillable = [
        'tenant_id',
        'router_id',
        'customer_name',
        'period',
        'promised_pay_date',
        'promised_pay_note',
    ];

    protected function casts(): array
    {
        return [
            'promised_pay_date' => 'date',
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

    public function isActive(): bool
    {
        if (! $this->promised_pay_date) {
            return false;
        }

        return now()->startOfDay()->lte($this->promised_pay_date->copy()->startOfDay());
    }
}
