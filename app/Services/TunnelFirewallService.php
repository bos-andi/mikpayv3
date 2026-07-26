<?php

namespace App\Services;

use App\Models\TunnelAccount;
use App\Models\TunnelPortMap;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class TunnelFirewallService
{
    public const NAT_CHAIN = 'MIKPAY_DNAT';

    public const FWD_CHAIN = 'MIKPAY_FWD';

    public function sync(): array
    {
        $this->ensureChains();

        $this->runIptables(['-t', 'nat', '-F', self::NAT_CHAIN]);
        $this->runIptables(['-F', self::FWD_CHAIN]);
        $this->seedForwardDefaults();

        $added = 0;
        $accounts = TunnelAccount::query()
            ->with('portMaps')
            ->where('status', 'active')
            ->whereNotNull('tunnel_ip')
            ->where('tunnel_ip', '!=', '')
            ->get();

        foreach ($accounts as $account) {
            foreach ($account->portMaps as $map) {
                if (! $map->is_enabled) {
                    continue;
                }
                $this->addPortForward($account->tunnel_ip, $map);
                $added++;
            }
        }

        Log::info('tunnel.firewall.synced', ['rules' => $added]);

        return ['accounts' => $accounts->count(), 'rules' => $added];
    }

    public function ensureChains(): void
    {
        $this->createChainIfMissing('nat', self::NAT_CHAIN);
        $this->createChainIfMissing('filter', self::FWD_CHAIN);

        // Jump from PREROUTING / FORWARD into our chains (idempotent).
        if (! $this->hasRule('nat', ['PREROUTING', '-j', self::NAT_CHAIN])) {
            $this->runIptables(['-t', 'nat', '-A', 'PREROUTING', '-j', self::NAT_CHAIN]);
        }
        if (! $this->hasRule('filter', ['FORWARD', '-j', self::FWD_CHAIN])) {
            $this->runIptables(['-A', 'FORWARD', '-j', self::FWD_CHAIN]);
        }

        $this->seedForwardDefaults();

        // MASQUERADE tunnel clients out to internet (optional; needed if router needs net via tunnel).
        $cidr = (string) config('mikpay.tunnel.vpn_cidr', '10.9.0.0/24');
        $iface = (string) config('mikpay.tunnel.public_iface', 'eth0');
        if (! $this->hasRule('nat', ['POSTROUTING', '-s', $cidr, '-o', $iface, '-j', 'MASQUERADE'])) {
            $this->runIptables(['-t', 'nat', '-A', 'POSTROUTING', '-s', $cidr, '-o', $iface, '-j', 'MASQUERADE']);
        }
    }

    protected function seedForwardDefaults(): void
    {
        $cidr = (string) config('mikpay.tunnel.vpn_cidr', '10.9.0.0/24');

        $this->runIptables(['-A', self::FWD_CHAIN, '-m', 'conntrack', '--ctstate', 'RELATED,ESTABLISHED', '-j', 'ACCEPT']);
        $this->runIptables(['-A', self::FWD_CHAIN, '-s', $cidr, '-j', 'ACCEPT']);
        $this->runIptables(['-A', self::FWD_CHAIN, '-d', $cidr, '-j', 'ACCEPT']);
    }

    protected function addPortForward(string $tunnelIp, TunnelPortMap $map): void
    {
        $remote = (int) $map->remote_port;
        $target = (int) $map->target_port;
        $proto = strtolower((string) ($map->protocol ?: 'tcp'));
        if (! in_array($proto, ['tcp', 'udp'], true)) {
            $proto = 'tcp';
        }

        // DNAT: public:remote_port → tunnel_ip:target_port
        $this->runIptables([
            '-t', 'nat', '-A', self::NAT_CHAIN,
            '-p', $proto, '--dport', (string) $remote,
            '-j', 'DNAT', '--to-destination', $tunnelIp.':'.$target,
        ]);

        $this->runIptables([
            '-A', self::FWD_CHAIN,
            '-p', $proto, '-d', $tunnelIp, '--dport', (string) $target,
            '-j', 'ACCEPT',
        ]);
    }

    protected function createChainIfMissing(string $table, string $chain): void
    {
        $check = $table === 'filter'
            ? new Process(['iptables', '-nL', $chain])
            : new Process(['iptables', '-t', $table, '-nL', $chain]);
        $check->run();
        if ($check->isSuccessful()) {
            return;
        }

        if ($table === 'filter') {
            $this->runIptables(['-N', $chain]);
        } else {
            $this->runIptables(['-t', $table, '-N', $chain]);
        }
    }

    /**
     * @param  list<string>  $spec  without leading iptables/-t
     */
    protected function hasRule(string $table, array $spec): bool
    {
        $args = $table === 'filter'
            ? array_merge(['-C'], $spec)
            : array_merge(['-t', $table, '-C'], $spec);
        $p = new Process(array_merge(['iptables'], $args));
        $p->run();

        return $p->isSuccessful();
    }

    /**
     * @param  list<string>  $args
     */
    protected function runIptables(array $args): void
    {
        $p = new Process(array_merge(['iptables'], $args));
        $p->setTimeout(15);
        $p->run();
        if (! $p->isSuccessful()) {
            Log::warning('tunnel.firewall.iptables_failed', [
                'args' => $args,
                'err' => $p->getErrorOutput(),
            ]);
        }
    }
}
