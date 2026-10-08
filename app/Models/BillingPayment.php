<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPayment extends Model
{
    protected $fillable = [
        'tenant_id',
        'router_id',
        'invoice_id',
        'xendit_invoice_id',
        'xendit_external_id',
        'payment_link_id',
        'customer_name',
        'display_name',
        'phone',
        'amount',
        'remaining_amount',
        'period',
        'payment_date',
        'payment_method',
        'gateway',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'remaining_amount' => 'integer',
            'payment_date' => 'datetime',
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

    public function isPaidFully(): bool
    {
        return $this->status === 'paid';
    }
}
