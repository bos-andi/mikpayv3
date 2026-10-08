<?php

namespace App\Console\Commands;

use App\Models\TunnelAccount;
use App\Services\OpenVpnManagementService;
use App\Services\TunnelService;
use Illuminate\Console\Command;

class TunnelKickStaleSessionsCommand extends Command
{
    protected $signature = 'tunnel:kick-stale-sessions';

    protected $description = 'Putus sesi OpenVPN milik akun yang sudah dihapus atau tidak aktif';

    public function handle(OpenVpnManagementService $management, TunnelService $tunnels): int
    {
        $clients = $management->connectedClients();
        if ($clients === null) {
            $this->warn('Management interface OpenVPN tidak bisa dihubungi.');

            return self::FAILURE;
        }

        $kicked = 0;
        foreach ($clients as $client) {
            $username = $client['common_name'];
            if ($username === '' || $username === 'UNDEF') {
                continue;
            }

            $account = TunnelAccount::query()->where('username', $username)->first();
            $allowed = $account
                && $tunnels->accountAllowedForVpn($account)
                && ($account->tunnel_ip === null || $account->tunnel_ip === $client['virtual_address']);

            if (! $allowed && $management->kill($username)) {
                $kicked++;
                $this->line("Diputus: {$username} ({$client['virtual_address']})");
            }
        }

        $this->info('Tersambung: '.count($clients).' · diputus: '.$kicked);

        return self::SUCCESS;
    }
}
