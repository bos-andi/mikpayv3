<?php

namespace App\Console\Commands;

use App\Models\TunnelAccount;
use Illuminate\Console\Command;

class TunnelOvpnAuthCommand extends Command
{
    protected $signature = 'tunnel:ovpn-auth';

    protected $description = 'OpenVPN auth-user-pass-verify (env username/password)';

    public function handle(): int
    {
        $username = (string) (getenv('username') ?: '');
        $password = (string) (getenv('password') ?: '');

        if ($username === '' || $password === '') {
            return self::FAILURE;
        }

        $account = TunnelAccount::query()
            ->where('username', $username)
            ->whereIn('status', ['active', 'pending'])
            ->first();

        if (! $account) {
            return self::FAILURE;
        }

        $plain = $account->plainPassword();
        if ($plain === '' || ! hash_equals($plain, $password)) {
            return self::FAILURE;
        }

        // Entitlement masih aktif?
        $ok = $account->entitlements()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
