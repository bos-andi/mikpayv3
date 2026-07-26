<?php

namespace App\Http\Controllers\Tunnel;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TunnelAccount;
use App\Models\TunnelEntitlement;
use App\Models\User;
use App\Services\TunnelFirewallService;
use App\Services\TunnelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TunnelRegisterController extends Controller
{
    public function create(): View
    {
        return view('tunnel.register', [
            'accountSuffix' => (string) config('mikpay.tunnel.account_suffix', 'tunnel.mikpay.link'),
            'defaultNodeHost' => \App\Models\TunnelNode::defaultNode()?->hostname
                ?? config('mikpay.tunnel.default_node_host', 'an-1.mikpay.link'),
        ]);
    }

    public function store(Request $request, TunnelService $tunnels, TunnelFirewallService $firewall): RedirectResponse
    {
        $suffix = (string) config('mikpay.tunnel.account_suffix', 'tunnel.mikpay.link');
        $reserved = $this->reservedSlugs();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9]{3,30}$/',
                Rule::notIn($reserved),
                function (string $attribute, mixed $value, \Closure $fail) use ($suffix) {
                    $slug = Str::lower((string) $value);
                    if (Tenant::query()->where('subdomain', $slug)->exists()) {
                        $fail('Username sudah dipakai. Pilih yang lain.');
                    }
                    if (TunnelAccount::query()->where('username', $slug.'@'.$suffix)->exists()) {
                        $fail('Username VPN sudah dipakai. Pilih yang lain.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'username.regex' => 'Username hanya huruf kecil dan angka (tanpa spasi/simbol).',
            'username.not_in' => 'Username ini tidak tersedia.',
            'email.unique' => 'Email sudah terdaftar. Silakan masuk.',
        ]);

        $slug = Str::lower($data['username']);

        $user = DB::transaction(function () use ($data, $slug, $tunnels) {
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'subdomain' => $slug,
                'status' => 'active',
                'plan' => 'tunnel',
                'vpn_status' => 'provisioning',
                'meta' => array_filter([
                    'product' => 'tunnel',
                    'phone' => $data['phone'] ?? null,
                    'registered_via' => 'tunnel.mikpay.link',
                ]),
            ]);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => User::ROLE_TENANT_ADMIN,
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]);

            $tunnels->provisionAccount($tenant, TunnelEntitlement::SOURCE_TUNNEL_PAID, [
                'user_id' => $user->id,
                'label' => 'MikPay Tunnel',
                'username_slug' => $slug,
            ]);

            return $user;
        });

        try {
            $firewall->sync();
        } catch (\Throwable) {
            // Non-fatal: cron/connect akan sync ulang.
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('tunnel.dashboard')
            ->with('success', 'Pendaftaran berhasil. Akun OpenVPN Anda sudah siap — salin script ke MikroTik.');
    }

    /**
     * @return list<string>
     */
    protected function reservedSlugs(): array
    {
        return [
            'www', 'app', 'user', 'mail', 'api', 'admin', 'remote', 'tunnel',
            'cdn', 'static', 'mikpay', 'demo', 'root', 'support', 'billing',
            'an', 'cgk', 'vpn', 'ovpn', 'openvpn',
        ];
    }
}
