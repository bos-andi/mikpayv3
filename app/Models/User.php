<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'tenant_id', 'is_active', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_TENANT_ADMIN = 'tenant_admin';

    public const ROLE_TENANT_USER = 'tenant_user';

    public const ROLE_PARTNER = 'partner';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    public function isPartner(): bool
    {
        return $this->role === self::ROLE_PARTNER;
    }

    public function isSuperAdmin(): bool
    {
        if ($this->role !== self::ROLE_SUPERADMIN) {
            return false;
        }

        // Hanya email yang di-allowlist yang boleh akses superadmin.
        $allowed = config('mikpay.superadmin_emails', ['me@andidev.id']);
        if (! is_array($allowed) || $allowed === []) {
            $allowed = ['me@andidev.id'];
        }

        return in_array(strtolower(trim((string) $this->email)), $allowed, true);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }

    public function requiresEmailVerification(): bool
    {
        return ! $this->isSuperAdmin() && ! $this->hasVerifiedEmail();
    }

    public function isTenantAdmin(): bool
    {
        return $this->role === self::ROLE_TENANT_ADMIN;
    }

    public static function isAllowedSuperAdminEmail(string $email): bool
    {
        $allowed = config('mikpay.superadmin_emails', ['me@andidev.id']);
        if (! is_array($allowed) || $allowed === []) {
            $allowed = ['me@andidev.id'];
        }

        return in_array(strtolower(trim($email)), $allowed, true);
    }
}
