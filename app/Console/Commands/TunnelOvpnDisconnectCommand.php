<?php

namespace App\Console\Commands;

use App\Models\TunnelAccount;
use Illuminate\Console\Command;

class TunnelOvpnDisconnectCommand extends Command
{
    protected $signature = 'tunnel:ovpn-disconnect';

    protected $description = 'OpenVPN client-disconnect: catat disconnect (DNAT tetap sampai reconnect/sync)';

    public function handle(): int
    {
        $username = (string) (getenv('username') ?: getenv('common_name') ?: '');
        if ($username === '') {
            return self::SUCCESS;
        }

        TunnelAccount::query()
            ->where('username', $username)
            ->update(['connected_at' => null]);

        return self::SUCCESS;
    }
}
