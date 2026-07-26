<?php

namespace App\Console\Commands;

use App\Models\TunnelAccount;
use App\Services\TunnelFirewallService;
use App\Services\TunnelService;
use Illuminate\Console\Command;

class TunnelOvpnConnectCommand extends Command
{
    protected $signature = 'tunnel:ovpn-connect {config_file : Path file config dari OpenVPN client-connect}';

    protected $description = 'OpenVPN client-connect: assign tunnel IP + sync DNAT';

    public function handle(TunnelService $tunnels, TunnelFirewallService $firewall): int
    {
        $username = (string) (getenv('username') ?: getenv('common_name') ?: '');
        $configFile = (string) $this->argument('config_file');

        if ($username === '' || $configFile === '') {
            return self::FAILURE;
        }

        $account = TunnelAccount::query()
            ->where('username', $username)
            ->whereIn('status', ['active', 'pending'])
            ->first();

        if (! $account) {
            return self::FAILURE;
        }

        $ip = $account->tunnel_ip ?: $tunnels->allocateTunnelIp($account);
        if (! $ip) {
            return self::FAILURE;
        }

        if ($account->tunnel_ip !== $ip) {
            $account->forceFill(['tunnel_ip' => $ip])->save();
        }

        $account->forceFill([
            'status' => 'active',
            'connected_at' => now(),
        ])->save();

        $mask = (string) config('mikpay.tunnel.vpn_netmask', '255.255.255.0');
        $body = "ifconfig-push {$ip} {$mask}\n";
        if (@file_put_contents($configFile, $body) === false) {
            return self::FAILURE;
        }

        $firewall->sync();

        return self::SUCCESS;
    }
}
