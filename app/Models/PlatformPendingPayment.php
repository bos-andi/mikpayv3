<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformPendingPayment extends Model
{
    protected $fillable = [
        'payment_id',
        'tenant_id',
        'user_id',
        'package',
        'payment_channel',
        'amount',
        'proof_file',
        'xendit_invoice_id',
        'xendit_external_id',
        'xendit_invoice_url',
        'customer_note',
        'status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
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
        return $this->status === 'pending';
    }

    public function isXendit(): bool
    {
        return $this->payment_channel === 'xendit' || filled($this->xendit_invoice_id);
    }
}
