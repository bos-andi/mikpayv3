<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'business_name',
        'business_address',
        'whatsapp',
        'telegram_bot_token',
        'telegram_chat_id',
        'telegram_router_session',
        'telegram_enabled',
        'telegram_notify_connection',
        'telegram_last_update_id',
        'telegram_test_last_status',
        'telegram_test_last_message',
        'telegram_test_last_at',
        'password',
        'role',
        'is_super_admin',
        'status',
        'subscription_package',
        'subscription_status',
        'subscription_start',
        'subscription_end',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'telegram_bot_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'telegram_enabled' => 'boolean',
            'telegram_notify_connection' => 'boolean',
            'telegram_last_update_id' => 'integer',
            'telegram_test_last_at' => 'datetime',
            'subscription_start' => 'date',
            'subscription_end' => 'date',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin || $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->isSuperAdmin();
    }

    public function pendingPayments()
    {
        return $this->hasMany(PendingPayment::class);
    }

    public function fonnteSetting()
    {
        return $this->hasOne(FonnteSetting::class);
    }

    public function mikpayRouters()
    {
        return $this->hasMany(MikpayRouter::class, 'user_id')->orderBy('session_name');
    }
}
