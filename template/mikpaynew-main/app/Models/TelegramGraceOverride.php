<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramGraceOverride extends Model
{
    protected $fillable = [
        'user_id',
        'router_session',
        'customer_name',
        'grace_until',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'grace_until' => 'date',
        ];
    }
}
