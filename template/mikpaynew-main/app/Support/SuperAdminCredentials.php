<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

/**
 * Kredensial Super Admin hanya dari .env (bukan baris di tabel users).
 */
final class SuperAdminCredentials
{
    public const SESSION_KEY = 'superadmin_authenticated';

    public static function sessionKey(): string
    {
        return self::SESSION_KEY;
    }

    public static function validateLogin(string $login, string $password): bool
    {
        $user = (string) config('mikpay.superadmin.username', '');
        $email = (string) config('mikpay.superadmin.email', '');

        $loginOk = $login === $user || ($email !== '' && $login === $email);
        if (! $loginOk || $user === '') {
            return false;
        }

        $plain = config('mikpay.superadmin.password');
        $hash = config('mikpay.superadmin.password_hash');

        // Utama: password plain di .env (SUPERADMIN_PASSWORD). Tanpa perlu bcrypt di env.
        if (is_string($plain) && $plain !== '') {
            return hash_equals($plain, $password);
        }

        // Fallback: hash bcrypt di env jika plain sengaja tidak di-set (mis. legacy).
        if (is_string($hash) && $hash !== '') {
            return Hash::check($password, $hash);
        }

        return false;
    }

    /** Nama untuk kolom approved_by / rejected_by */
    public static function actorName(): string
    {
        $u = (string) config('mikpay.superadmin.username', '');

        return $u !== '' ? $u : 'superadmin';
    }
}
