<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherOrder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    /** Status yang uangnya sudah diterima (masuk saldo tenant). */
    public const PAID_STATUSES = [self::STATUS_PAID, self::STATUS_FULFILLED, self::STATUS_FAILED];

    protected $fillable = [
        'tenant_id',
        'router_id',
        'item_id',
        'reference',
        'token',
        'item_label',
        'profile',
        'limit_uptime',
        'limit_bytes',
        'price',
        'fee',
        'service_fee',
        'total',
        'phone',
        'status',
        'voucher_code',
        'voucher_password',
        'xendit_qr_id',
        'xendit_reference_id',
        'qr_string',
        'expires_at',
        'paid_at',
        'fulfilled_at',
        'fulfill_attempts',
        'last_error',
        'wa_sent_at',
        'buyer_ip',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'fee' => 'integer',
            'service_fee' => 'integer',
            'total' => 'integer',
            'limit_bytes' => 'integer',
            'fulfill_attempts' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'wa_sent_at' => 'datetime',
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(VoucherShopItem::class, 'item_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaidLike(): bool
    {
        return in_array($this->status, self::PAID_STATUSES, true);
    }

    public function isPaymentExpired(): bool
    {
        return $this->isPending() && $this->expires_at && $this->expires_at->isPast();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu bayar',
            self::STATUS_PAID => 'Membuat voucher',
            self::STATUS_FULFILLED => 'Sukses',
            self::STATUS_FAILED => 'Gagal buat voucher',
            self::STATUS_EXPIRED => 'Kedaluwarsa',
            default => ucfirst((string) $this->status),
        };
    }
}
