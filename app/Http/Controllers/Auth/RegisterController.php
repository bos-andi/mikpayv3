<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TunnelAccount;
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

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'hideNavbar' => true,
            'hideFooter' => true,
            'tenantBase' => (string) config('mikpay.tenant_base_domain', 'mikpay.link'),
            'accountSuffix' => (string) config('mikpay.tunnel.account_suffix', 'tunnel.mikpay.link'),
        ]);
    }

    public function store(Request $request, TunnelService $tunnels, TunnelFirewallService $firewall): RedirectResponse
    {
        $reserved = $this->reservedSlugs();
        $suffix = (string) config('mikpay.tunnel.account_suffix', 'tunnel.mikpay.link');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            // Subdomain = basis username VPN; dikunci permanen setelah daftar (tidak ada form ubah).
            'subdomain' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9](?:[a-z0-9-]{1,28}[a-z0-9])?$/',
                Rule::notIn($reserved),
                Rule::unique('tenants', 'subdomain'),
                function (string $attribute, mixed $value, \Closure $fail) {
                    $slug = Str::lower((string) $value);
                    $pattern = (string) config('mikpay.tunnel.node_sub_pattern', '^(an|cgk)-[0-9]+$');
                    if ($pattern !== '' && preg_match('/'.$pattern.'/i', $slug)) {
                        $fail('Subdomain ini khusus node VPN dan tidak bisa dipakai.');
                    }
                },
                function (string $attribute, mixed $value, \Closure $fail) use ($suffix) {
                    $base = Str::lower(preg_replace('/[^a-z0-9]+/i', '', (string) $value) ?: '');
                    if ($base !== '' && TunnelAccount::query()->where('username', $base.'@'.$suffix)->exists()) {
                        $fail('Username VPN dari subdomain ini sudah dipakai. Pilih subdomain lain.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'subdomain.regex' => 'Subdomain hanya huruf kecil, angka, dan strip (tidak boleh diawali/diakhiri strip).',
            'subdomain.unique' => 'Subdomain sudah dipakai. Pilih yang lain.',
            'subdomain.not_in' => 'Subdomain ini tidak tersedia.',
            'email.unique' => 'Email sudah terdaftar. Silakan masuk.',
        ]);

        $slug = Str::lower($data['subdomain']);

        $user = DB::transaction(function () use ($data, $slug, $tunnels) {
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'subdomain' => $slug,
                'status' => 'active',
                'plan' => 'trial',
                'vpn_status' => 'provisioning',
                'meta' => array_filter([
                    'product' => 'mikpay',
                    'phone' => $data['phone'] ?? null,
                    'registered_via' => 'mikpay.link',
                    'trial_ends_at' => now()->addDays(14)->toIso8601String(),
                ]),
            ]);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => User::ROLE_TENANT_ADMIN,
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Bonus 1 OpenVPN gratis untuk langganan MikPay.
            $tunnels->ensureMikpayBundle($tenant);

            return $user->load('tenant');
        });

        try {
            $firewall->sync();
        } catch (\Throwable) {
            // Non-fatal
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('panel.dashboard')
            ->with('success', 'Pendaftaran berhasil. Trial 14 hari aktif — bonus OpenVPN sudah tersedia di menu Remote & OpenVPN.');
    }

    /**
     * @return list<string>
     */
    protected function reservedSlugs(): array
    {
        return [
            'www', 'app', 'user', 'mail', 'api', 'admin', 'remote', 'tunnel',
            'cdn', 'static', 'mikpay', 'demo', 'root', 'support', 'billing',
            'an', 'cgk', 'vpn', 'ovpn', 'openvpn', 'superadmin', 'panel',
        ];
    }
}
