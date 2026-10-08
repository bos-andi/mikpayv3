<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Services\HostingerDnsService;
use App\Services\TunnelFirewallService;
use App\Services\TunnelService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(
        protected TunnelService $tunnels,
        protected TunnelFirewallService $firewall,
        protected HostingerDnsService $dns,
        protected PartnerProgramService $partners,
    ) {}

    /**
     * @param  callable(string, string, ?string): void  $progress  ($step, $status, $message)
     * @return array{user: User, redirect: string, success: string}
     */
    public function register(array $input, callable $progress): array
    {
        $progress('validate', 'running', 'Memvalidasi data formulir…');

        $data = validator($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
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
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'referral_code' => [
                'nullable',
                'string',
                'max:30',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (filled($value) && ! $this->partners->findActiveByCode((string) $value)) {
                        $fail('Kode referral tidak ditemukan atau sudah tidak aktif. Kosongkan jika tidak punya.');
                    }
                },
            ],
        ], [
            'email.unique' => 'Email sudah terdaftar. Silakan masuk.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex' => 'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx.',
        ])->validate();

        if (User::isAllowedSuperAdminEmail($data['email'])) {
            throw ValidationException::withMessages([
                'email' => 'Email ini tidak bisa dipakai untuk daftar pelanggan.',
            ]);
        }

        $progress('validate', 'done');
        $slug = Str::lower($data['subdomain']);
        $trialDays = (int) config('mikpay.trial_days', 3);

        $progress('tenant', 'running', "Membuat panel {$slug}.".config('mikpay.tenant_base_domain', 'mikpay.link').'…');

        $user = DB::transaction(function () use ($data, $slug, $trialDays, $progress) {
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
                    'trial_ends_at' => wib_now()->addDays($trialDays)->toIso8601String(),
                    'onboarding' => ['pending' => true],
                    'email_verification_pending' => true,
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

            if (filled($data['referral_code'] ?? null)) {
                $this->partners->attachReferral($tenant, $data['referral_code'], $data['email'], $data['phone'] ?? null);
            }

            $progress('tenant', 'done');
            $progress('trial', 'running', "Mengaktifkan trial {$trialDays} hari…");
            $progress('trial', 'done');

            $progress('tunnel', 'running', 'Menyiapkan akun OpenVPN & port remote…');
            $this->tunnels->ensureMikpayBundle($tenant);
            $progress('tunnel', 'done');

            return $user->load('tenant');
        });

        $progress('firewall', 'running', 'Sinkronisasi firewall tunnel…');
        try {
            $this->firewall->sync();
            $progress('firewall', 'done');
        } catch (\Throwable $e) {
            $progress('firewall', 'done', 'Lewati — firewall akan disinkron ulang otomatis.');
        }

        $dnsMessage = null;
        $progress('dns', 'running', 'Mendaftarkan DNS subdomain…');
        try {
            $tenant = $user->tenant;
            if ($tenant) {
                $dnsResult = $this->dns->ensureTenantSubdomain($tenant);
                $dnsMessage = $dnsResult['ok'] ?? false
                    ? ($dnsResult['message'] ?? 'DNS subdomain dibuat.')
                    : 'DNS: '.($dnsResult['message'] ?? 'gagal (wildcard *.mikpay.link tetap bisa dipakai).');
            }
            $progress('dns', 'done', $dnsMessage);
        } catch (\Throwable $e) {
            $progress('dns', 'done', 'DNS dilewati — wildcard *.mikpay.link tetap bisa dipakai.');
        }

        $progress('login', 'running', 'Mengirim email verifikasi ke '.$user->email.'…');
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }
        Auth::login($user);
        request()->session()->regenerate();
        $progress('login', 'done');

        $success = "Pendaftaran berhasil! Buka email {$user->email} dan klik tautan verifikasi untuk mengaktifkan panel & trial {$trialDays} hari.";
        $redirect = central_url('/email/verifikasi');

        $progress('done', 'done', 'Pendaftaran selesai!');

        return [
            'user' => $user,
            'redirect' => $redirect,
            'success' => $success,
        ];
    }

    /**
     * @return array{available: bool, subdomain: string, message: string, reason?: string}
     */
    public function evaluateSubdomainAvailability(string $slug): array
    {
        $slug = Str::lower(trim($slug));
        $base = (string) config('mikpay.tenant_base_domain', 'mikpay.link');
        $suffix = (string) config('mikpay.tunnel.account_suffix', 'tunnel.mikpay.link');

        if ($slug === '') {
            return ['available' => false, 'subdomain' => '', 'message' => 'Masukkan subdomain terlebih dahulu.', 'reason' => 'empty'];
        }

        if (strlen($slug) < 3) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Minimal 3 karakter.', 'reason' => 'too_short'];
        }

        if (strlen($slug) > 30) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Maksimal 30 karakter.', 'reason' => 'too_long'];
        }

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{1,28}[a-z0-9])?$/', $slug)) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Hanya huruf kecil, angka, dan strip (tidak boleh diawali/diakhiri strip).', 'reason' => 'invalid_format'];
        }

        if (in_array($slug, $this->reservedSlugs(), true)) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Subdomain ini dikhususkan sistem dan tidak bisa dipakai.', 'reason' => 'reserved'];
        }

        $pattern = (string) config('mikpay.tunnel.node_sub_pattern', '^(an|cgk)-[0-9]+$');
        if ($pattern !== '' && preg_match('/'.$pattern.'/i', $slug)) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Subdomain ini khusus node VPN dan tidak bisa dipakai.', 'reason' => 'vpn_node'];
        }

        if (Tenant::query()->where('subdomain', $slug)->exists()) {
            return ['available' => false, 'subdomain' => $slug, 'message' => "{$slug}.{$base} sudah dipakai. Coba yang lain.", 'reason' => 'taken'];
        }

        $vpnBase = Str::lower(preg_replace('/[^a-z0-9]+/i', '', $slug) ?: '');
        if ($vpnBase !== '' && \App\Models\TunnelAccount::query()->where('username', $vpnBase.'@'.$suffix)->exists()) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Username VPN dari subdomain ini sudah dipakai.', 'reason' => 'vpn_taken'];
        }

        return ['available' => true, 'subdomain' => $slug, 'message' => "{$slug}.{$base} tersedia!", 'reason' => 'ok'];
    }

    /** @return list<string> */
    protected function reservedSlugs(): array
    {
        return [
            'www', 'app', 'user', 'mail', 'api', 'admin', 'remote', 'tunnel',
            'cdn', 'static', 'mikpay', 'demo', 'root', 'support', 'billing',
            'an', 'cgk', 'vpn', 'ovpn', 'openvpn', 'superadmin', 'panel',
        ];
    }
}
