<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TunnelAccount;
use App\Models\TunnelEntitlement;
use App\Models\TunnelNode;
use App\Models\TunnelPortMap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TunnelService
{
    /**
     * Pastikan tenant MikPay aktif punya 1 akun OpenVPN gratis (bundle).
     */
    public function ensureMikpayBundle(Tenant $tenant): TunnelAccount
    {
        if (($tenant->plan ?? '') !== 'tunnel' && ! $tenant->subscriptionIsActive()) {
            throw new \RuntimeException('Langganan tidak aktif. Perpanjang langganan MikPay untuk menggunakan tunnel remote.');
        }

        $existing = TunnelAccount::query()
            ->where('tenant_id', $tenant->id)
            ->whereHas('entitlements', function ($q) {
                $q->where('source', TunnelEntitlement::SOURCE_MIKPAY_BUNDLE)
                    ->where('status', 'active');
            })
            ->with(['portMaps', 'entitlement', 'node'])
            ->first();

        if ($existing) {
            if ($tenant->subscriptionIsActive() && $existing->status !== 'active') {
                $this->enableForTenant($tenant);
                $existing->refresh();
            }
            $this->syncAccountHostFromNode($existing);

            return $existing->loadMissing(['portMaps', 'entitlement', 'node']);
        }

        // Coba aktifkan kembali entitlement yang sempat dinonaktifkan saat langganan habis.
        $disabledBundle = TunnelAccount::query()
            ->where('tenant_id', $tenant->id)
            ->whereHas('entitlements', function ($q) {
                $q->where('source', TunnelEntitlement::SOURCE_MIKPAY_BUNDLE);
            })
            ->with(['portMaps', 'entitlement', 'node'])
            ->first();

        if ($disabledBundle && $tenant->subscriptionIsActive()) {
            $this->enableForTenant($tenant);
            $disabledBundle->refresh();

            return $disabledBundle->loadMissing(['portMaps', 'entitlement', 'node']);
        }

        return $this->provisionAccount($tenant, TunnelEntitlement::SOURCE_MIKPAY_BUNDLE, [
            'label' => 'MikPay Free Tunnel',
        ]);
    }

    /**
     * @param  array{label?: string, ends_at?: mixed, user_id?: int|null, node_id?: int|null, username_slug?: string|null}  $options
     */
    public function provisionAccount(Tenant $tenant, string $source, array $options = []): TunnelAccount
    {
        return DB::transaction(function () use ($tenant, $source, $options) {
            $node = ! empty($options['node_id'])
                ? TunnelNode::query()->find($options['node_id'])
                : TunnelNode::defaultNode();

            if (! $node) {
                throw new \RuntimeException('Belum ada tunnel node aktif. Buat node (contoh an-1.mikpay.link) dulu.');
            }

            $username = $this->makeUsername($tenant, $options['username_slug'] ?? null);
            $password = Str::password(12, symbols: false);

            $account = new TunnelAccount([
                'tenant_id' => $tenant->id,
                'user_id' => $options['user_id'] ?? null,
                'tunnel_node_id' => $node->id,
                'username' => $username,
                'protocol' => 'openvpn',
                'server_host' => $node->hostname,
                'server_port' => (int) $node->ovpn_port,
                'tunnel_ip' => null,
                'status' => $tenant->status === 'active' ? 'active' : 'pending',
                'label' => $options['label'] ?? 'OpenVPN Tunnel',
            ]);
            $account->setPlainPassword($password);
            $account->save();
            $account->forceFill(['tunnel_ip' => $this->allocateTunnelIp($account)])->save();

            foreach ($this->defaultPortTargets() as $map) {
                TunnelPortMap::query()->create([
                    'tunnel_account_id' => $account->id,
                    'label' => $map['label'],
                    'remote_port' => $this->allocateRemotePort($node),
                    'target_port' => $map['target_port'],
                    'protocol' => 'tcp',
                    'is_enabled' => true,
                ]);
            }

            TunnelEntitlement::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $options['user_id'] ?? null,
                'tunnel_account_id' => $account->id,
                'source' => $source,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $options['ends_at'] ?? null,
                'meta' => [
                    'note' => $source === TunnelEntitlement::SOURCE_MIKPAY_BUNDLE
                        ? 'Gratis selama langganan MikPay aktif'
                        : 'Langganan MikPay Tunnel',
                    'node' => $node->code,
                ],
            ]);

            $tenant->forceFill([
                'remote_host' => $node->hostname,
                'vpn_status' => 'online',
            ])->save();

            return $account->load(['portMaps', 'entitlement', 'node']);
        });
    }

    /**
     * Script setup pertama kali MikPay di MikroTik (asumsi belum pernah dikonfig).
     * Satu kali paste: OVPN + firewall + service + NAT OLT/Router Client.
     *
     * @return array{ros6: string, ros7: string, interface_name: string}
     */
    public function buildOpenVpnScripts(TunnelAccount $account): array
    {
        $account->loadMissing(['portMaps', 'node']);

        if (! $account->tunnel_ip) {
            $account->forceFill(['tunnel_ip' => $this->allocateTunnelIp($account)])->save();
        }

        $iface = 'ovpn-mikpay';
        $user = $account->username;
        $pass = $account->plainPassword();
        $tunnelIp = (string) $account->tunnel_ip;
        $host = (string) (config('mikpay.tunnel.connect_host')
            ?: ($account->node?->hostname ?: $account->server_host));
        $port = (int) ($account->node?->ovpn_port ?: $account->server_port);

        $commentParts = $account->portMaps
            ->map(fn (TunnelPortMap $m) => $m->remote_port.'->'.$m->target_port)
            ->implode(',');
        $ifaceComment = 'MikPay tunnel '.$commentParts;

        // RouterOS v6 tidak mengenal protocol= (UDP baru ada di v7), use-peer-dns= (baru 6.47+),
        // verify-server-certificate= dan tls-version= → parameter itu hanya untuk script v7.
        $common = ' connect-to='.$this->quote($host)
            .' port='.$port
            .' mode=ip'
            .' user='.$this->quote($user)
            .' password='.$this->quote($pass)
            .' profile=default'
            .' certificate=none'
            .' auth=sha1'
            .' cipher=blowfish128'
            .' add-default-route=no'
            .' name='.$this->quote($iface)
            .' comment='.$this->quote($ifaceComment)
            .' disabled=no';

        $ovpn6 = '/interface ovpn-client add'.$common;
        $ovpn7 = '/interface ovpn-client add'.$common
            .' protocol=tcp'
            .' use-peer-dns=no'
            .' verify-server-certificate=no'
            .' tls-version=any';

        $allowTunnel = '/ip firewall filter add chain=input action=accept in-interface='.$iface
            .' comment='.$this->quote('MikPay allow tunnel');

        $olt = $account->portMaps->firstWhere('label', 'OLT');
        $router = $account->portMaps->firstWhere('label', 'Router Client');
        $oltPort = (int) ($olt?->target_port ?: 25);
        $routerPort = (int) ($router?->target_port ?: 19);

        // Setup pertama kali - aman diulang (hapus rule MikPay lama dulu).
        $lines = [
            '# ==========================================================',
            '# MikPay - Script setup OpenVPN (salin-tempel ke Terminal MikroTik)',
            '# Aman dijalankan ulang: rule MikPay lama diganti otomatis',
            '# ==========================================================',
            '',
            '# --- MikPay 1) OpenVPN Client ---',
            ':do { /interface ovpn-client remove [find name='.$this->quote($iface).'] } on-error={}',
            '{OVPN}',
            '',
            '# --- MikPay 2) Firewall izinkan akses dari tunnel ---',
            ':do { /ip firewall filter remove [find comment~'.$this->quote('MikPay').'] } on-error={}',
            // place-before=0 gagal kalau tabel filter masih kosong → fallback tanpa place-before.
            ':do { '.$allowTunnel.' place-before=0 } on-error={ '.$allowTunnel.' }',
            '',
            '# --- MikPay 3) Aktifkan service remote (Winbox / API / WWW) ---',
            '/ip service set winbox disabled=no address=0.0.0.0/0',
            '/ip service set api disabled=no address=0.0.0.0/0',
            '/ip service set www disabled=no address=0.0.0.0/0',
            '',
            '# --- MikPay 4) NAT remote OLT & Router Client ---',
            '# PENTING: ganti to-addresses ke IP LAN perangkat Anda',
            ':do { /ip firewall nat remove [find comment~'.$this->quote('MikPay').'] } on-error={}',
            '/ip firewall nat add chain=dstnat dst-address='.$tunnelIp
                .' protocol=tcp dst-port='.$oltPort
                .' action=dst-nat to-addresses=192.168.1.2 to-ports=80'
                .' comment='.$this->quote('MikPay REMOTE OLT - ganti to-addresses'),
            '/ip firewall nat add chain=dstnat dst-address='.$tunnelIp
                .' protocol=tcp dst-port='.$routerPort
                .' action=dst-nat to-addresses=192.168.19.25 to-ports=80'
                .' comment='.$this->quote('MikPay REMOTE ROUTER CLIENT - ganti to-addresses'),
            '',
            '# --- MikPay selesai ---',
            ':put '.$this->quote('MikPay OK. Cek: /interface ovpn-client print (harus R). Lalu ganti IP OLT/Router di NAT.'),
        ];

        $ros6 = implode("\n", array_map(
            fn (string $line) => $line === '{OVPN}' ? $ovpn6 : $line,
            $lines,
        ));
        $ros7 = implode("\n", array_map(
            fn (string $line) => $line === '{OVPN}' ? $ovpn7 : $line,
            $lines,
        ));

        return [
            'ros6' => $ros6,
            'ros7' => $ros7,
            'interface_name' => $iface,
        ];
    }

    /**
     * Alokasi IP tunnel statis di subnet OpenVPN MikPay (default 10.9.0.0/24).
     */
    public function allocateTunnelIp(TunnelAccount $account, bool $checkLiveSessions = true): string
    {
        if ($account->tunnel_ip) {
            return $account->tunnel_ip;
        }

        $base = (string) config('mikpay.tunnel.vpn_network', '10.9.0');
        $start = (int) config('mikpay.tunnel.vpn_client_start', 2);
        $end = (int) config('mikpay.tunnel.vpn_client_end', 254);

        $used = TunnelAccount::query()
            ->whereNotNull('tunnel_ip')
            ->pluck('tunnel_ip')
            ->all();

        // IP yang masih dipegang sesi lama (mis. router tenant terhapus yang belum putus) jangan dibagikan.
        $connected = $checkLiveSessions ? (app(OpenVpnManagementService::class)->connectedClients() ?? []) : [];
        foreach ($connected as $client) {
            if ($client['virtual_address'] !== '') {
                $used[] = $client['virtual_address'];
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $ip = $base.'.'.$i;
            if (! in_array($ip, $used, true)) {
                return $ip;
            }
        }

        throw new \RuntimeException('Pool IP tunnel OpenVPN sudah penuh.');
    }

    /**
     * URL remote utama (biasanya API/Winbox) untuk kolom List VPN.
     */
    public function primaryRemoteUrl(TunnelAccount $account): ?string
    {
        $account->loadMissing(['portMaps', 'node']);
        $map = $account->portMaps->firstWhere('label', 'API')
            ?? $account->portMaps->firstWhere('label', 'Winbox')
            ?? $account->portMaps->first();

        return $map?->endpoint();
    }

    public function disableForTenant(Tenant $tenant, string $source = TunnelEntitlement::SOURCE_MIKPAY_BUNDLE): bool
    {
        $changed = false;
        $entitlements = TunnelEntitlement::query()
            ->where('tenant_id', $tenant->id)
            ->where('source', $source)
            ->where('status', 'active')
            ->get();

        foreach ($entitlements as $ent) {
            $ent->forceFill(['status' => 'expired', 'ends_at' => now()])->save();
            $changed = true;
            $account = $ent->account;
            if ($account && ! $this->hasOtherActiveEntitlement($account, $ent->id)) {
                $account->forceFill(['status' => 'disabled'])->save();
                app(OpenVpnManagementService::class)->kill((string) $account->username);
            }
        }

        if ($changed) {
            $tenant->forceFill(['vpn_status' => 'offline'])->save();
        }

        return $changed;
    }

    public function enableForTenant(Tenant $tenant, string $source = TunnelEntitlement::SOURCE_MIKPAY_BUNDLE): bool
    {
        if (($tenant->plan ?? '') !== 'tunnel' && ! $tenant->subscriptionIsActive()) {
            return false;
        }

        $changed = false;

        $entitlements = TunnelEntitlement::query()
            ->where('tenant_id', $tenant->id)
            ->where('source', $source)
            ->where('status', 'expired')
            ->get();

        foreach ($entitlements as $ent) {
            $ent->forceFill(['status' => 'active', 'ends_at' => null])->save();
            $changed = true;
            $account = $ent->account;
            if ($account && $account->status !== 'active') {
                $account->forceFill(['status' => 'active'])->save();
            }
        }

        $activeEnt = TunnelEntitlement::query()
            ->where('tenant_id', $tenant->id)
            ->where('source', $source)
            ->where('status', 'active')
            ->with('account')
            ->first();

        if ($activeEnt?->account && $activeEnt->account->status !== 'active') {
            $activeEnt->account->forceFill(['status' => 'active'])->save();
            $changed = true;
        }

        if ($changed && $activeEnt?->account?->node) {
            $tenant->forceFill([
                'remote_host' => $activeEnt->account->node->hostname,
                'vpn_status' => 'online',
            ])->save();
        }

        return $changed;
    }

    /**
     * Sinkronkan akses tunnel bundle MikPay dengan status langganan tenant.
     */
    public function syncAccessForTenant(Tenant $tenant, bool $syncFirewall = true): bool
    {
        if (($tenant->plan ?? '') === 'tunnel') {
            return false;
        }

        $changed = $tenant->subscriptionIsActive()
            ? $this->enableForTenant($tenant)
            : $this->disableForTenant($tenant);

        if ($changed && $syncFirewall) {
            app(TunnelFirewallService::class)->sync();
        }

        return $changed;
    }

    /**
     * Cek apakah akun tunnel boleh connect/auth OpenVPN (langganan + entitlement).
     */
    public function accountAllowedForVpn(TunnelAccount $account): bool
    {
        if (! in_array($account->status, ['active', 'pending'], true)) {
            return false;
        }

        $account->loadMissing('tenant');
        $tenant = $account->tenant;
        if ($tenant?->awaitingEmailVerification()) {
            return false;
        }
        if ($tenant && ($tenant->plan ?? '') !== 'tunnel' && ! $tenant->subscriptionIsActive()) {
            return false;
        }

        return $account->entitlements()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();
    }

    protected function syncAccountHostFromNode(TunnelAccount $account): void
    {
        $account->loadMissing('node');
        if (! $account->node) {
            return;
        }

        if ($account->server_host !== $account->node->hostname
            || (int) $account->server_port !== (int) $account->node->ovpn_port) {
            $account->forceFill([
                'server_host' => $account->node->hostname,
                'server_port' => $account->node->ovpn_port,
            ])->save();
        }
    }

    protected function hasOtherActiveEntitlement(TunnelAccount $account, int $exceptId): bool
    {
        return TunnelEntitlement::query()
            ->where('tunnel_account_id', $account->id)
            ->where('id', '!=', $exceptId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();
    }

    protected function makeUsername(Tenant $tenant, ?string $slug = null): string
    {
        $base = Str::lower(preg_replace('/[^a-z0-9]+/i', '', $slug ?: $tenant->subdomain) ?: 'tenant');
        $domain = (string) config('mikpay.tunnel.account_suffix', 'tunnel.mikpay.link');
        $username = $base.'@'.$domain;

        $i = 0;
        while (TunnelAccount::query()->where('username', $username)->exists()) {
            $i++;
            $username = $base.$i.'@'.$domain;
        }

        return $username;
    }

    /**
     * Port publik tidak pernah dipakai ulang (selama rentang belum habis), supaya alamat remote
     * tenant yang sudah dihapus tidak mengarah ke perangkat tenant baru.
     */
    protected function allocateRemotePort(TunnelNode $node): int
    {
        $node = TunnelNode::query()->whereKey($node->id)->lockForUpdate()->firstOrFail();
        $min = (int) $node->port_range_start;
        $max = (int) $node->port_range_end;

        $lastInUse = (int) TunnelPortMap::query()
            ->whereHas('account', fn ($q) => $q->where('tunnel_node_id', $node->id))
            ->max('remote_port');
        $last = max($lastInUse, (int) $node->last_allocated_port);

        $next = $last >= $min ? $last + 1 : $min;

        if ($next <= $max) {
            $node->forceFill(['last_allocated_port' => $next])->save();

            return $next;
        }

        Log::warning('tunnel.port_range_exhausted', ['node' => $node->code, 'max' => $max]);

        for ($p = $min; $p <= $max; $p++) {
            $exists = TunnelPortMap::query()
                ->where('remote_port', $p)
                ->whereHas('account', fn ($q) => $q->where('tunnel_node_id', $node->id))
                ->exists();
            if (! $exists) {
                return $p;
            }
        }

        throw new \RuntimeException('Pool port node '.$node->code.' sudah penuh.');
    }

    /**
     * @return list<array{label: string, target_port: int}>
     */
    protected function defaultPortTargets(): array
    {
        return [
            ['label' => 'Winbox', 'target_port' => 8291],
            ['label' => 'API', 'target_port' => 8728],
            ['label' => 'OLT', 'target_port' => 25],
            ['label' => 'Router Client', 'target_port' => 19],
        ];
    }

    protected function quote(string $value): string
    {
        return '"'.str_replace('"', '\\"', $value).'"';
    }
}
