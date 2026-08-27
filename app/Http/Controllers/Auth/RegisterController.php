<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TunnelAccount;
use App\Models\User;
use App\Services\HostingerDnsService;
use App\Services\TunnelFirewallService;
use App\Services\TunnelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function checkSubdomain(Request $request): JsonResponse
    {
        $raw = Str::lower(trim((string) $request->query('subdomain', $request->input('subdomain', ''))));
        $result = $this->evaluateSubdomainAvailability($raw);

        return response()->json($result);
    }

    public function store(
        Request $request,
        TunnelService $tunnels,
        TunnelFirewallService $firewall,
        HostingerDnsService $dns
    ): RedirectResponse {

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
                function (string $attribute, mixed $value, \Closure $fail) {
                    $check = $this->evaluateSubdomainAvailability(Str::lower((string) $value));
                    if (! ($check['available'] ?? false)) {
                        $fail((string) ($check['message'] ?? 'Subdomain tidak tersedia.'));
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'email.unique' => 'Email sudah terdaftar. Silakan masuk.',
        ]);

        if (User::isAllowedSuperAdminEmail($data['email'])) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => 'Email ini tidak bisa dipakai untuk daftar pelanggan.']);
        }

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
                    'trial_ends_at' => wib_now()
                        ->addDays((int) config('mikpay.trial_days', 3))
                        ->toIso8601String(),
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

        // Buat DNS A record di Hostinger: {subdomain}.mikpay.link → IP server
        $dnsMessage = null;
        try {
            $tenant = $user->tenant;
            if ($tenant) {
                $dnsResult = $dns->ensureTenantSubdomain($tenant);
                $dnsMessage = $dnsResult['ok'] ?? false
                    ? ($dnsResult['message'] ?? 'DNS subdomain dibuat.')
                    : 'DNS Hostinger: '.($dnsResult['message'] ?? 'gagal (bisa pakai wildcard *.mikpay.link).');
            }
        } catch (\Throwable $e) {
            $dnsMessage = 'DNS Hostinger gagal: '.$e->getMessage();
        }

        Auth::login($user);
        $request->session()->regenerate();

        $trialDays = (int) config('mikpay.trial_days', 3);
        $trialUntil = format_wib($user->tenant?->subscriptionEndsAt());
        $success = "Pendaftaran berhasil. Trial {$trialDays} hari aktif"
            .($trialUntil ? " sampai {$trialUntil}" : '')
            .' — bonus OpenVPN sudah tersedia di menu Remote & OpenVPN.';
        if ($dnsMessage) {
            $success .= ' '.$dnsMessage;
        }

        $tenant = $user->tenant;
        if ($tenant?->subdomain) {
            return redirect()
                ->away(tenant_panel_url($tenant, '/dashboard'))
                ->with('success', $success);
        }

        return redirect()
            ->route('panel.dashboard')
            ->with('success', $success);
    }

    /**
     * @return array{available: bool, subdomain: string, message: string, reason?: string}
     */
    protected function evaluateSubdomainAvailability(string $slug): array
    {
        $slug = Str::lower(trim($slug));
        $base = (string) config('mikpay.tenant_base_domain', 'mikpay.link');
        $suffix = (string) config('mikpay.tunnel.account_suffix', 'tunnel.mikpay.link');

        if ($slug === '') {
            return [
                'available' => false,
                'subdomain' => '',
                'message' => 'Masukkan subdomain terlebih dahulu.',
                'reason' => 'empty',
            ];
        }

        if (strlen($slug) < 3) {
            return [
                'available' => false,
                'subdomain' => $slug,
                'message' => 'Minimal 3 karakter.',
                'reason' => 'too_short',
            ];
        }

        if (strlen($slug) > 30) {
            return [
                'available' => false,
                'subdomain' => $slug,
                'message' => 'Maksimal 30 karakter.',
                'reason' => 'too_long',
            ];
        }

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{1,28}[a-z0-9])?$/', $slug)) {
            return [
                'available' => false,
                'subdomain' => $slug,
                'message' => 'Hanya huruf kecil, angka, dan strip (tidak boleh diawali/diakhiri strip).',
                'reason' => 'invalid_format',
            ];
        }

        if (in_array($slug, $this->reservedSlugs(), true)) {
            return [
                'available' => false,
                'subdomain' => $slug,
                'message' => 'Subdomain ini dikhususkan sistem dan tidak bisa dipakai.',
                'reason' => 'reserved',
            ];
        }

        $pattern = (string) config('mikpay.tunnel.node_sub_pattern', '^(an|cgk)-[0-9]+$');
        if ($pattern !== '' && preg_match('/'.$pattern.'/i', $slug)) {
            return [
                'available' => false,
                'subdomain' => $slug,
                'message' => 'Subdomain ini khusus node VPN dan tidak bisa dipakai.',
                'reason' => 'vpn_node',
            ];
        }

        if (Tenant::query()->where('subdomain', $slug)->exists()) {
            return [
                'available' => false,
                'subdomain' => $slug,
                'message' => "{$slug}.{$base} sudah dipakai. Coba yang lain.",
                'reason' => 'taken',
            ];
        }

        $vpnBase = Str::lower(preg_replace('/[^a-z0-9]+/i', '', $slug) ?: '');
        if ($vpnBase !== '' && TunnelAccount::query()->where('username', $vpnBase.'@'.$suffix)->exists()) {
            return [
                'available' => false,
                'subdomain' => $slug,
                'message' => 'Username VPN dari subdomain ini sudah dipakai.',
                'reason' => 'vpn_taken',
            ];
        }

        return [
            'available' => true,
            'subdomain' => $slug,
            'message' => "{$slug}.{$base} tersedia!",
            'reason' => 'ok',
        ];
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
