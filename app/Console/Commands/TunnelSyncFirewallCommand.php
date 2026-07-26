<?php

namespace App\Console\Commands;

use App\Services\TunnelFirewallService;
use Illuminate\Console\Command;

class TunnelSyncFirewallCommand extends Command
{
    protected $signature = 'tunnel:sync-firewall';

    protected $description = 'Rebuild iptables DNAT rules dari tunnel_port_maps + tunnel_ip';

    public function handle(TunnelFirewallService $firewall): int
    {
        $result = $firewall->sync();
        $this->info('Firewall synced: '.$result['accounts'].' akun, '.$result['rules'].' rules.');

        return self::SUCCESS;
    }
}
