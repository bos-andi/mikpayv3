<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaapiReminderDispatch extends Model
{
    public const KIND_H3 = 'h3';

    public const KIND_H0 = 'h0';

    public const KIND_OVERDUE = 'overdue';

    protected $table = 'waapi_reminder_dispatches';

    protected $fillable = [
        'tenant_id',
        'router_id',
        'customer_name',
        'kind',
        'dispatch_date',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'dispatch_date' => 'date',
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
}
