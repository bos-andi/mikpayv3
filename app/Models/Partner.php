<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_REJECTED = 'rejected';

    public const BANK_PENDING = 'pending';

    public const BANK_VERIFIED = 'verified';

    public const BANK_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'code',
        'status',
        'phone',
        'city',
        'promotion_plan',
        'first_percent',
        'recurring_percent',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'first_percent' => 'float',
            'recurring_percent' => 'float',
            'clicks' => 'integer',
            'approved_at' => 'datetime',
            'bank_reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'referred_by_partner_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PartnerCommission::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(PartnerWithdrawal::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function referralUrl(): string
    {
        return central_url('/r/'.$this->code);
    }

    public function registerUrl(): string
    {
        return central_url('/daftar?ref='.$this->code);
    }

    public function hasVerifiedBank(): bool
    {
        return $this->bank_status === self::BANK_VERIFIED && filled($this->account_number);
    }

    public function bankProofIsPdf(): bool
    {
        return str_ends_with(strtolower((string) $this->bank_proof_path), '.pdf');
    }

    public function ktpIsPdf(): bool
    {
        return str_ends_with(strtolower((string) $this->ktp_path), '.pdf');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_SUSPENDED => 'Dinonaktifkan',
            self::STATUS_REJECTED => 'Ditolak',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-amber-50 text-amber-700 border-amber-200',
            self::STATUS_ACTIVE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::STATUS_SUSPENDED => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }

    public function bankStatusLabel(): string
    {
        return match ($this->bank_status) {
            self::BANK_PENDING => 'Menunggu Verifikasi',
            self::BANK_VERIFIED => 'Terverifikasi',
            self::BANK_REJECTED => 'Ditolak',
            default => 'Belum diisi',
        };
    }

    public function bankStatusBadgeClass(): string
    {
        return match ($this->bank_status) {
            self::BANK_PENDING => 'bg-amber-50 text-amber-700 border-amber-200',
            self::BANK_VERIFIED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::BANK_REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }
}
