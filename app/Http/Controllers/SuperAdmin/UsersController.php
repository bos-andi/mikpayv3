<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformPendingPayment;
use App\Models\Tenant;
use App\Models\TunnelAccount;
use App\Models\User;
use App\Services\HostingerDnsService;
use App\Services\OpenVpnManagementService;
use App\Services\TunnelFirewallService;
use App\Services\TunnelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $tenants = Tenant::query()
            ->with(['users' => fn ($query) => $query->orderBy('id')])
            ->withCount(['users', 'routers', 'tunnelAccounts'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('subdomain', 'like', $like)
                        ->orWhereHas('users', function ($u) use ($like) {
                            $u->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                });
            })
            ->orderBy('name')
            ->get();

        $orphanUsers = User::query()
            ->whereNull('tenant_id')
            ->whereNotIn('role', [User::ROLE_SUPERADMIN, User::ROLE_PARTNER])
            ->orderBy('id')
            ->get();

        $superadmins = User::query()
            ->where('role', User::ROLE_SUPERADMIN)
            ->orderBy('id')
            ->get();

        return view('superadmin.users', [
            'tenants' => $tenants,
            'orphanUsers' => $orphanUsers,
            'superadmins' => $superadmins,
            'q' => $q,
            'tenantCount' => Tenant::query()->count(),
            'activeTenantCount' => Tenant::query()->where('status', 'active')->count(),
            'userCount' => User::query()->where('role', '!=', User::ROLE_SUPERADMIN)->count(),
            'tenantBase' => (string) config('mikpay.tenant_base_domain', 'mikpay.link'),
            'openTenantId' => (int) $request->query('tenant', 0),
        ]);
    }

    public function updateTenant(Request $request, Tenant $tenant, HostingerDnsService $dns): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'status' => ['required', 'string', Rule::in(['active', 'trial', 'inactive'])],
            'plan' => ['required', 'string', 'max:40'],
            'subdomain' => [
                'required',
                'string',
                'min:3',
                'max:30',
                function (string $attribute, mixed $value, \Closure $fail) use ($tenant) {
                    $slug = Str::lower(trim((string) $value));
                    $check = $this->evaluateSubdomainAvailability($slug, $tenant->id);
                    if (! ($check['available'] ?? false)) {
                        $fail((string) ($check['message'] ?? 'Subdomain tidak valid.'));
                    }
                },
            ],
        ], [], [
            'name' => 'Nama tenant',
            'status' => 'Status',
            'plan' => 'Paket',
            'subdomain' => 'Subdomain',
        ]);

        $oldSub = strtolower((string) $tenant->subdomain);
        $newSub = Str::lower(trim($data['subdomain']));
        $messages = [];

        $tenant->forceFill([
            'name' => $data['name'],
            'status' => $data['status'],
            'plan' => $data['plan'],
            'subdomain' => $newSub,
        ])->save();

        if ($oldSub !== $newSub) {
            try {
                if ($oldSub !== '') {
                    $dns->deleteARecord($oldSub);
                }
                $dnsResult = $dns->ensureTenantSubdomain($tenant->fresh());
                $messages[] = ($dnsResult['ok'] ?? false)
                    ? 'DNS subdomain diperbarui.'
                    : ('DNS: '.($dnsResult['message'] ?? 'gagal update, wildcard masih aktif.'));
            } catch (\Throwable $e) {
                $messages[] = 'DNS gagal: '.$e->getMessage();
            }
        }

        $success = "Tenant {$tenant->name} diperbarui.";
        if ($messages !== []) {
            $success .= ' '.implode(' ', $messages);
        }

        return redirect()
            ->route('superadmin.users', array_filter([
                'q' => $request->query('q'),
                'tenant' => $tenant->id,
            ]))
            ->with('success', $success);
    }

    public function toggleTenantStatus(Tenant $tenant, TunnelService $tunnels): RedirectResponse
    {
        if ($tenant->status === 'inactive') {
            $tenant->status = ($tenant->plan === 'trial' && ! $tenant->subscriptionExpired()) ? 'trial' : 'active';
            $label = 'diaktifkan';
        } else {
            $tenant->status = 'inactive';
            $label = 'dinonaktifkan';
        }
        $tenant->save();

        $tunnels->syncAccessForTenant($tenant->fresh());

        return back()->with('success', "Tenant {$tenant->name} {$label}.");
    }

    public function injectTenantBalance(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1000', 'max:50000000'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], [
            'amount' => 'Nominal saldo',
            'note' => 'Catatan',
        ]);

        $amount = (int) $data['amount'];
        $admin = auth()->user();
        $note = trim((string) ($data['note'] ?? ''));
        $adminLabel = $admin?->email ?: 'superadmin';
        $ledgerNote = 'Inject saldo superadmin'.($note !== '' ? ': '.$note : '');

        DB::transaction(function () use ($tenant, $amount, $note, $adminLabel, $ledgerNote, $admin) {
            $tenant->addPlatformBalance($amount, $ledgerNote);
            $tenant->addBonusPlatformBalance($amount);

            do {
                $paymentId = 'MPA'.strtoupper(bin2hex(random_bytes(4))).now()->format('ymd');
            } while (PlatformPendingPayment::query()->where('payment_id', $paymentId)->exists());

            PlatformPendingPayment::query()->create([
                'payment_id' => $paymentId,
                'tenant_id' => $tenant->id,
                'user_id' => $admin?->id,
                'package' => 'deposit',
                'purpose' => PlatformPendingPayment::PURPOSE_DEPOSIT,
                'payment_channel' => 'superadmin',
                'amount' => $amount,
                'customer_note' => $note !== '' ? $note : null,
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $adminLabel,
            ]);
        });

        return redirect()
            ->route('superadmin.users', array_filter([
                'q' => $request->input('q'),
                'tenant' => $tenant->id,
            ]))
            ->with('success', 'Saldo '.$tenant->name.' ditambah Rp '.number_format($amount, 0, ',', '.').'. Saldo sekarang Rp '.number_format($tenant->fresh()->platformBalance(), 0, ',', '.'));
    }

    public function destroyTenant(Request $request, Tenant $tenant, HostingerDnsService $dns, TunnelFirewallService $firewall, OpenVpnManagementService $management): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $protectedSlug = Str::lower(trim((string) config('mikpay.superadmin_panel_tenant', '')));
        if ($protectedSlug !== '' && Str::lower((string) $tenant->subdomain) === $protectedSlug) {
            $message = "Tenant {$tenant->name} adalah tenant operasional platform dan tidak bisa dihapus.";

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $message], 403);
            }

            return back()->with('error', $message);
        }

        $name = $tenant->name;
        $sub = strtolower((string) $tenant->subdomain);
        $tenantId = (int) $tenant->id;
        $fqdn = $sub !== '' ? $sub.'.'.config('mikpay.tenant_base_domain', 'mikpay.link') : null;

        $steps = [];
        $mark = function (string $key, string $label, bool $ok, string $detail = '') use (&$steps): void {
            $steps[] = [
                'key' => $key,
                'label' => $label,
                'ok' => $ok,
                'detail' => $detail,
            ];
        };

        try {
            $userCount = User::query()->where('tenant_id', $tenantId)->count();
            $accounts = TunnelAccount::query()->where('tenant_id', $tenantId)->with('portMaps')->get();
            $portCount = (int) $accounts->sum(fn (TunnelAccount $a) => $a->portMaps->count());
            $routerCount = $tenant->routers()->count();
            $tunnelUsernames = $accounts->pluck('username')->filter()->values()->all();

            DB::transaction(function () use ($tenant, $tenantId, $accounts) {
                User::query()->where('tenant_id', $tenantId)->delete();

                foreach ($accounts as $account) {
                    $account->portMaps()->delete();
                    $account->entitlements()->delete();
                    $account->delete();
                }

                // Data operasional ikut cascade (router, billing, GIS, kas, tiket, OLT, dll).
                $tenant->delete();
            });

            $mark('users', 'Hapus akun login panel', true, $userCount.' user dihapus');
            $vpnDetail = $accounts->count().' tunnel · '.$portCount.' port map';
            if ($tunnelUsernames !== []) {
                $vpnDetail .= ' · VPN bebas: '.implode(', ', array_slice($tunnelUsernames, 0, 3))
                    .(count($tunnelUsernames) > 3 ? '…' : '');
            }
            $mark('tunnels', 'Hapus OpenVPN & port remote', true, $vpnDetail);
            $mark(
                'data',
                'Hapus data tenant (router, billing, GIS, keuangan)',
                true,
                $routerCount.' router + data terkait'
            );

            $killed = 0;
            foreach ($tunnelUsernames as $username) {
                if ($management->kill((string) $username)) {
                    $killed++;
                }
            }
            $mark(
                'sessions',
                'Putus sesi VPN yang masih tersambung',
                true,
                $killed > 0 ? $killed.' sesi diputus' : 'Tidak ada sesi aktif'
            );

            $firewallOk = true;
            $firewallDetail = 'Firewall di-sync ulang';
            try {
                $fw = $firewall->sync();
                $firewallDetail = ! empty($fw['skipped'])
                    ? 'Port remote ditutup otomatis oleh server dalam ≤ 1 menit'
                    : 'Aturan aktif: '.((int) ($fw['rules'] ?? 0));
            } catch (\Throwable $e) {
                $firewallOk = false;
                $firewallDetail = $e->getMessage();
            }
            $mark('firewall', 'Bersihkan firewall tunnel server', $firewallOk, $firewallDetail);

            $dnsOk = true;
            $dnsDetail = $fqdn ? "Record A {$fqdn}" : 'Tidak ada subdomain';
            if ($sub !== '') {
                try {
                    $del = $dns->deleteARecord($sub);
                    $dnsOk = (bool) ($del['ok'] ?? false);
                    if (! $dnsOk) {
                        $dnsDetail = (string) ($del['message'] ?? 'Gagal hapus DNS (wildcard mungkin masih aktif)');
                    } else {
                        $dnsDetail = "A record {$fqdn} dihapus";
                    }
                } catch (\Throwable $e) {
                    $dnsOk = false;
                    $dnsDetail = $e->getMessage();
                }
            }
            $mark('dns', 'Hapus DNS subdomain Hostinger', $dnsOk, $dnsDetail);

            $mark('done', 'Tenant dihapus permanen', true, $name);
        } catch (\Throwable $e) {
            $mark('error', 'Penghapusan gagal', false, $e->getMessage());

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Gagal menghapus tenant: '.$e->getMessage(),
                    'tenant' => $name,
                    'subdomain' => $sub,
                    'fqdn' => $fqdn,
                    'steps' => $steps,
                ], 500);
            }

            return redirect()
                ->route('superadmin.users', array_filter(['q' => $request->query('q')]))
                ->with('error', 'Gagal menghapus tenant: '.$e->getMessage());
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => "Tenant {$name} dan semua konfigurasinya dihapus.",
                'tenant' => $name,
                'subdomain' => $sub,
                'fqdn' => $fqdn,
                'steps' => $steps,
                'redirect' => route('superadmin.users', array_filter(['q' => $request->query('q')])),
            ]);
        }

        $dnsNote = collect($steps)->firstWhere('key', 'dns');
        $extra = ($dnsNote['ok'] ?? false) ? ' DNS subdomain dihapus.' : '';

        return redirect()
            ->route('superadmin.users', array_filter(['q' => $request->query('q')]))
            ->with('success', "Tenant {$name} dan semua akunnya dihapus.{$extra}");
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        if ($user->isSuperAdmin() && (int) $user->id !== (int) auth()->id()) {
            return back()->with('error', 'Tidak bisa mengubah superadmin lain dari sini.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in([
                User::ROLE_TENANT_ADMIN,
                User::ROLE_TENANT_USER,
            ])],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], [], [
            'name' => 'Nama',
            'email' => 'Email',
            'role' => 'Role',
            'password' => 'Password',
        ]);

        if ($user->role === User::ROLE_SUPERADMIN || User::isAllowedSuperAdminEmail($user->email)) {
            return back()->with('error', 'Akun superadmin tidak bisa diubah dari sini.');
        }

        if ($data['role'] === User::ROLE_SUPERADMIN || User::isAllowedSuperAdminEmail($data['email'])) {
            return back()->with('error', 'Tidak bisa menjadikan user ini superadmin.');
        }

        if (! $user->tenant_id) {
            return back()->with('error', 'User tanpa tenant hanya boleh dikelola khusus, bukan dari form tenant.');
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        $user->is_active = $request->boolean('is_active');

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()
            ->route('superadmin.users', array_filter([
                'q' => $request->query('q'),
                'tenant' => $user->tenant_id,
            ]))
            ->with('success', "User {$user->email} diperbarui.");
    }

    public function toggleUserActive(User $user): RedirectResponse
    {
        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'Tidak bisa menonaktifkan akun sendiri.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Tidak bisa menonaktifkan superadmin dari sini.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $label = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('superadmin.users', array_filter([
                'q' => request()->query('q'),
                'tenant' => $user->tenant_id,
            ]))
            ->with('success', "User {$user->email} {$label}.");
    }

    public function verifyUserEmail(User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            \App\Http\Controllers\Auth\EmailVerificationController::activateAfterVerification($user);
        }

        return redirect()
            ->route('superadmin.users', array_filter([
                'q' => request()->query('q'),
                'tenant' => $user->tenant_id,
            ]))
            ->with('success', "Email {$user->email} ditandai terverifikasi secara manual.");
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        if ($user->isSuperAdmin() || User::isAllowedSuperAdminEmail($user->email)) {
            return back()->with('error', 'Tidak bisa menghapus akun superadmin.');
        }

        $email = $user->email;
        $tenantId = $user->tenant_id;
        $user->delete();

        return redirect()
            ->route('superadmin.users', array_filter([
                'q' => $request->query('q'),
                'tenant' => $tenantId,
            ]))
            ->with('success', "User {$email} dihapus.");
    }

    public function storeTenantUser(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'string', Rule::in([User::ROLE_TENANT_ADMIN, User::ROLE_TENANT_USER])],
        ], [], [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Password',
            'role' => 'Role',
        ]);

        if (User::isAllowedSuperAdminEmail($data['email'])) {
            return back()->with('error', 'Email ini khusus superadmin dan tidak bisa dijadikan user tenant.');
        }

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('superadmin.users', array_filter([
                'q' => $request->query('q'),
                'tenant' => $tenant->id,
            ]))
            ->with('success', "User {$data['email']} ditambahkan ke {$tenant->name}.");
    }

    /**
     * @return array{available: bool, subdomain: string, message: string, reason?: string}
     */
    protected function evaluateSubdomainAvailability(string $slug, ?int $ignoreTenantId = null): array
    {
        $slug = Str::lower(trim($slug));
        $base = (string) config('mikpay.tenant_base_domain', 'mikpay.link');
        $suffix = (string) config('mikpay.tunnel.account_suffix', 'tunnel.mikpay.link');

        if ($slug === '' || strlen($slug) < 3) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Subdomain minimal 3 karakter.', 'reason' => 'too_short'];
        }
        if (strlen($slug) > 30) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Subdomain maksimal 30 karakter.', 'reason' => 'too_long'];
        }
        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{1,28}[a-z0-9])?$/', $slug)) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Format subdomain tidak valid.', 'reason' => 'invalid_format'];
        }

        $reserved = [
            'www', 'app', 'user', 'mail', 'api', 'admin', 'remote', 'tunnel',
            'cdn', 'static', 'mikpay', 'demo', 'root', 'support', 'billing',
            'an', 'cgk', 'vpn', 'ovpn', 'openvpn', 'superadmin', 'panel',
        ];
        if (in_array($slug, $reserved, true)) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Subdomain dikhususkan sistem.', 'reason' => 'reserved'];
        }

        $pattern = (string) config('mikpay.tunnel.node_sub_pattern', '^(an|cgk)-[0-9]+$');
        if ($pattern !== '' && preg_match('/'.$pattern.'/i', $slug)) {
            return ['available' => false, 'subdomain' => $slug, 'message' => 'Subdomain khusus node VPN.', 'reason' => 'vpn_node'];
        }

        $taken = Tenant::query()
            ->where('subdomain', $slug)
            ->when($ignoreTenantId, fn ($q) => $q->where('id', '!=', $ignoreTenantId))
            ->exists();
        if ($taken) {
            return ['available' => false, 'subdomain' => $slug, 'message' => "{$slug}.{$base} sudah dipakai.", 'reason' => 'taken'];
        }

        $vpnBase = Str::lower(preg_replace('/[^a-z0-9]+/i', '', $slug) ?: '');
        if ($vpnBase !== '') {
            $vpnTaken = TunnelAccount::query()
                ->where('username', $vpnBase.'@'.$suffix)
                ->when($ignoreTenantId, function ($q) use ($ignoreTenantId) {
                    $q->where(function ($inner) use ($ignoreTenantId) {
                        $inner->whereNull('tenant_id')->orWhere('tenant_id', '!=', $ignoreTenantId);
                    });
                })
                ->exists();
            if ($vpnTaken) {
                return ['available' => false, 'subdomain' => $slug, 'message' => 'Username VPN dari subdomain ini sudah dipakai.', 'reason' => 'vpn_taken'];
            }
        }

        return ['available' => true, 'subdomain' => $slug, 'message' => 'OK', 'reason' => 'ok'];
    }
}
