<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BillingPaymentLink extends Model
{
    protected $fillable = [
        'tenant_id',
        'router_id',
        'token',
        'customer_name',
        'display_name',
        'phone',
        'periods',
        'amount',
        'status',
        'xendit_invoice_id',
        'xendit_external_id',
        'xendit_invoice_url',
        'payment_checkout',
        'expires_at',
        'paid_at',
        'wa_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'periods' => 'array',
            'payment_checkout' => 'array',
            'amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'wa_sent_at' => 'datetime',
        ];
    }

    public function hasActiveCheckout(): bool
    {
        $c = $this->payment_checkout;
        if (! is_array($c) || empty($c['method'])) {
            return false;
        }

        if (! empty($c['expires_at'])) {
            try {
                if (now()->greaterThan(\Carbon\Carbon::parse($c['expires_at']))) {
                    return false;
                }
            } catch (\Throwable) {
                return false;
            }
        }

        return (int) ($c['amount'] ?? 0) === (int) $this->amount;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class, 'payment_link_id');
    }

    public static function generateToken(): string
    {
        return Str::random(40);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPayable(): bool
    {
        return $this->isPending() && ! $this->isExpired() && $this->amount > 0;
    }

    public function publicUrl(): string
    {
        $tenant = $this->relationLoaded('tenant') ? $this->tenant : $this->tenant()->first();
        if (! $tenant) {
            return url('/bayar/'.$this->token);
        }

        return tenant_pay_url($tenant, $this->token);
    }
}
