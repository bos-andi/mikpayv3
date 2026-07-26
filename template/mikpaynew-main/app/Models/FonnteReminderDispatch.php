<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FonnteReminderDispatch extends Model
{
    public const KIND_H3 = 'h3';

    public const KIND_H0 = 'h0';

    public const KIND_OVERDUE = 'overdue';

    protected $fillable = [
        'user_id',
        'customer_name',
        'kind',
        'dispatch_date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
