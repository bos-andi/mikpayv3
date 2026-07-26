<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramPppConnectionState extends Model
{
    protected $fillable = [
        'user_id',
        'router_session',
        'secret_name',
        'last_state',
        'last_seen_online_at',
        'last_notified_state',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_online_at' => 'datetime',
            'last_notified_at' => 'datetime',
        ];
    }
}
