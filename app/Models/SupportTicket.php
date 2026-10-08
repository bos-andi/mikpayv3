<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    /** @var list<string> */
    public const OPEN_STATUSES = [self::STATUS_OPEN, self::STATUS_IN_PROGRESS];

    protected $fillable = [
        'tenant_id',
        'code',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'router_id',
        'odp_id',
        'odp_port_id',
        'customer_name',
        'location_label',
        'created_by_user_id',
        'assigned_to_user_id',
        'resolved_at',
        'closed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'meta' => 'array',
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

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function odpPort(): BelongsTo
    {
        return $this->belongsTo(OdpPort::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(SupportTicketUpdate::class, 'ticket_id')->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Baru',
            self::STATUS_IN_PROGRESS => 'Dikerjakan',
            self::STATUS_RESOLVED => 'Selesai',
            self::STATUS_CLOSED => 'Ditutup',
            default => strtoupper($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'bg-amber-500/10 text-amber-600 border-amber-200',
            self::STATUS_IN_PROGRESS => 'bg-sky-500/10 text-sky-600 border-sky-200',
            self::STATUS_RESOLVED => 'bg-emerald-500/10 text-emerald-600 border-emerald-200',
            self::STATUS_CLOSED => 'bg-slate-500/10 text-slate-500 border-slate-200',
            default => 'bg-slate-500/10 text-slate-500 border-slate-200',
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'urgent' => 'Mendesak',
            'high' => 'Tinggi',
            'low' => 'Rendah',
            default => 'Normal',
        };
    }

    public static function nextCode(int $tenantId): string
    {
        $year = wib_now()->format('Y');
        $prefix = "TKT-{$year}-";
        $last = self::query()
            ->where('tenant_id', $tenantId)
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('code');

        $seq = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
