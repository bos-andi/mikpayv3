<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MikpayRouter extends Model
{
    protected $table = 'mikpay_routers';

    protected $fillable = [
        'user_id',
        'session_name',
        'host',
        'username',
        'password',
        'router_name',
        'domain',
        'currency',
        'currency_position',
        'expiry_mode',
        'expiry_days',
        'status',
        'livereport',
    ];

    protected $hidden = ['password'];

    protected static function booted(): void
    {
        // Multi-tenant sederhana: user biasa hanya melihat router miliknya sendiri.
        static::addGlobalScope('owner', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();
            if ($user && ! $user->isSuperAdmin()) {
                $builder->where('user_id', $user->id);
            }
        });

        static::creating(function (self $router) {
            if (! $router->user_id && Auth::check()) {
                $router->user_id = Auth::id();
            }
        });
    }

    public function getPasswordDecoded(): string
    {
        $decoded = base64_decode($this->password, true);
        return $decoded !== false ? $decoded : $this->password;
    }

    public function getHostParts(): array
    {
        $parts = explode(':', $this->host);
        return [
            'ip' => $parts[0] ?? '',
            'port' => (int) ($parts[1] ?? config('mikpay.router.api_port', 8728)),
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
