<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantWithdrawal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    /** Status yang masih "memakan" saldo tenant. */
    public const RESERVED_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
    ];

    public const METHOD_XENDIT = 'xendit';

    public const METHOD_MANUAL = 'manual';

    protected $fillable = [
        'reference',
        'tenant_id',
        'user_id',
        'payout_account_id',
        'amount',
        'fee',
        'net_amount',
        'bank_code',
        'bank_name',
        'account_number',
        'account_holder',
        'note',
        'status',
        'method',
        'xendit_disbursement_id',
        'xendit_external_id',
        'xendit_status',
        'failure_code',
        'transfer_reference',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'fee' => 'integer',
            'net_amount' => 'integer',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function maskedAccountNumber(): string
    {
        $n = (string) $this->account_number;

        return strlen($n) > 4 ? str_repeat('•', strlen($n) - 4).substr($n, -4) : $n;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Konfirmasi',
            self::STATUS_PROCESSING => 'Sedang Ditransfer',
            self::STATUS_COMPLETED => 'Berhasil',
            self::STATUS_FAILED => 'Gagal',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/30',
            self::STATUS_PROCESSING => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:border-sky-500/30',
            self::STATUS_COMPLETED => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/30',
            self::STATUS_FAILED, self::STATUS_REJECTED => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/30',
            default => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        };
    }
}
