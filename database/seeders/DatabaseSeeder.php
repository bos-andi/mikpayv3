<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->updateOrCreate(
            ['subdomain' => 'annetwifi'],
            [
                'name' => 'ANNET WIFI',
                'status' => 'active',
                'plan' => 'pro',
                'vpn_status' => 'online',
                'remote_host' => config('mikpay.tunnel.default_node_host', 'an-1.mikpay.link'),
                'router_label' => 'RB4011-iGS+ (10.10.0.1)',
            ]
        );

        Tenant::query()->updateOrCreate(
            ['subdomain' => 'sarpannet'],
            [
                'name' => 'Sarpan Net',
                'status' => 'active',
                'plan' => 'pro',
                'vpn_status' => 'online',
                'remote_host' => 'sarpannet.remote.mikpay.link',
                'router_label' => 'CCR2004 (10.10.0.1)',
            ]
        );

        Tenant::query()->updateOrCreate(
            ['subdomain' => 'cl45'],
            [
                'name' => 'Demo ISP Network',
                'status' => 'active',
                'plan' => 'pro',
                'vpn_status' => 'online',
                'remote_host' => 'cl45.remote.mikpay.link',
                'router_label' => 'RB4011-iGS+ (10.10.0.1)',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'me@andidev.id'],
            [
                'name' => 'Andi Dev',
                'password' => '@Andidev.id19_mp',
                'role' => User::ROLE_SUPERADMIN,
                'tenant_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
