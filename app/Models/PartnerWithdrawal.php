<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerWithdrawal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const METHOD_XENDIT = 'xendit';

    public const METHOD_MANUAL = 'manual';

    protected $fillable = [
        'reference',
        'partner_id',
        'amount',
        'bank_code',
        'bank_name',
        'account_number',
        'account_holder',
        'note',
        'status',
        'method',
        'xendit_external_id',
        'xendit_disbursement_id',
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
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Jadwal Pencairan',
            self::STATUS_PROCESSING => 'Sedang Ditransfer',
            self::STATUS_COMPLETED => 'Sudah Ditransfer',
            self::STATUS_FAILED => 'Transfer Gagal',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-amber-50 text-amber-700 border-amber-200',
            self::STATUS_PROCESSING => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::STATUS_COMPLETED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::STATUS_CANCELLED => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }

    public function maskedAccount(): string
    {
        $n = (string) $this->account_number;

        return strlen($n) > 4 ? str_repeat('•', strlen($n) - 4).substr($n, -4) : $n;
    }
}
