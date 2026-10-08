<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TunnelFirewallService;
use App\Services\TunnelService;
use Illuminate\Console\Command;

class SyncTenantTunnelAccessCommand extends Command
{
    protected $signature = 'tunnel:sync-subscription-access';

    protected $description = 'Nonaktifkan/aktifkan tunnel bundle sesuai status langganan tenant';

    public function handle(TunnelService $tunnels, TunnelFirewallService $firewall): int
    {
        $changed = 0;

        Tenant::query()
            ->where('plan', '!=', 'tunnel')
            ->whereHas('tunnelAccounts')
            ->orderBy('id')
            ->chunkById(50, function ($tenants) use ($tunnels, &$changed) {
                foreach ($tenants as $tenant) {
                    if ($tunnels->syncAccessForTenant($tenant, syncFirewall: false)) {
                        $changed++;
                    }
                }
            });

        if ($changed > 0) {
            $firewall->sync();
        }

        $this->info("Tunnel access synced for {$changed} tenant(s).");

        return self::SUCCESS;
    }
}
