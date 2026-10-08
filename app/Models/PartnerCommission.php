<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCommission extends Model
{
    public const KIND_FIRST = 'first';

    public const KIND_RECURRING = 'recurring';

    public const STATUS_EARNED = 'earned';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'partner_id',
        'tenant_id',
        'tenant_name',
        'platform_payment_id',
        'package',
        'kind',
        'base_amount',
        'percent',
        'amount',
        'status',
        'available_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'integer',
            'percent' => 'float',
            'amount' => 'integer',
            'available_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PlatformPendingPayment::class, 'platform_payment_id');
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_EARNED && $this->available_at->lessThanOrEqualTo(now());
    }

    public function kindLabel(): string
    {
        return $this->kind === self::KIND_FIRST ? 'Pembayaran pertama' : 'Perpanjangan';
    }

    public function statusLabel(): string
    {
        if ($this->isCancelled()) {
            return 'Dibatalkan';
        }

        return $this->isAvailable() ? 'Bisa ditarik' : 'Tertahan';
    }

    public function statusBadgeClass(): string
    {
        if ($this->isCancelled()) {
            return 'bg-rose-50 text-rose-700 border-rose-200';
        }

        return $this->isAvailable()
            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
            : 'bg-amber-50 text-amber-700 border-amber-200';
    }
}
