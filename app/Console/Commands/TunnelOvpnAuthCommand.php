<?php

namespace App\Console\Commands;

use App\Models\TunnelAccount;
use App\Services\TunnelService;
use Illuminate\Console\Command;

class TunnelOvpnAuthCommand extends Command
{
    protected $signature = 'tunnel:ovpn-auth';

    protected $description = 'OpenVPN auth-user-pass-verify (env username/password)';

    public function handle(TunnelService $tunnels): int
    {
        $username = (string) (getenv('username') ?: '');
        $password = (string) (getenv('password') ?: '');

        if ($username === '' || $password === '') {
            return self::FAILURE;
        }

        $account = TunnelAccount::query()
            ->where('username', $username)
            ->first();

        if (! $account || ! $tunnels->accountAllowedForVpn($account)) {
            return self::FAILURE;
        }

        $plain = $account->plainPassword();
        if ($plain === '' || ! hash_equals($plain, $password)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
