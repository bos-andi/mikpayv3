<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'tenant_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_TENANT_ADMIN = 'tenant_admin';

    public const ROLE_TENANT_USER = 'tenant_user';

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
