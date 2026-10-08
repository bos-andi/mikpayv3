<?php

namespace App\Services;

use App\Models\Router;
use App\Models\Tenant;
use App\Models\TunnelAccount;
use App\Models\User;

class OnboardingService
{
    /** Panduan setup hanya untuk tenant baru hasil pendaftaran, bukan superadmin/tenant lama. */
    public function isEligible(?Tenant $tenant, ?User $user = null): bool
    {
        if (! $tenant?->id) {
            return false;
        }

        $user ??= auth()->user();
        if ($user?->isSuperAdmin()) {
            return false;
        }

        return (bool) $tenant->metaGet('onboarding.pending', false);
    }

    /**
     * @return array{
     *     steps: list<array{key:string,title:string,description:string,done:bool,required:bool,url:?string,action_label:?string}>,
     *     completed:int,
     *     total:int,
     *     required_completed:int,
     *     required_total:int,
     *     progress:int,
     *     all_required_done:bool,
     *     dismissed:bool,
     *     eligible:bool
     * }
     */
    public function checklist(Tenant $tenant): array
    {
        if (! $this->isEligible($tenant)) {
            return $this->emptyChecklist();
        }

        $tunnel = TunnelAccount::query()
            ->where('tenant_id', $tenant->id)
            ->with('portMaps')
            ->first();

        $router = Router::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        $scriptDone = (bool) $tenant->metaGet('onboarding.script_confirmed', false);
        $base = (string) config('mikpay.tenant_base_domain', 'mikpay.link');
        $fqdn = $tenant->subdomain ? "{$tenant->subdomain}.{$base}" : null;

        $apiMap = $tunnel?->portMaps->firstWhere('label', 'API');
        $apiEndpoint = $apiMap?->endpoint();

        $steps = [
            [
                'key' => 'subdomain',
                'title' => 'Subdomain panel aktif',
                'description' => $fqdn
                    ? "Panel Anda: {$fqdn} — gunakan URL ini untuk login ke depan."
                    : 'Subdomain panel sudah terdaftar.',
                'done' => filled($tenant->subdomain),
                'required' => true,
                'url' => $fqdn ? 'https://'.$fqdn.'/dashboard' : null,
                'action_label' => 'Buka panel',
            ],
            [
                'key' => 'tunnel',
                'title' => 'Akun OpenVPN tunnel siap',
                'description' => $tunnel
                    ? "Username VPN: {$tunnel->username} — sudah dibuat otomatis saat daftar."
                    : 'Akun tunnel belum tersedia. Buka halaman Remote untuk membuat ulang.',
                'done' => (bool) $tunnel,
                'required' => true,
                'url' => panel_route('remote'),
                'action_label' => 'Buka Remote',
            ],
            [
                'key' => 'script',
                'title' => 'Paste script OpenVPN di MikroTik',
                'description' => 'Salin script ROS 6/7 dari halaman Remote, paste di Terminal MikroTik, lalu cek `/interface ovpn-client print` status R (running).',
                'done' => $scriptDone || (bool) ($router?->last_check_ok),
                'required' => true,
                'url' => panel_route('remote').'#ovpn-setup',
                'action_label' => 'Lihat script',
            ],
            [
                'key' => 'router',
                'title' => 'Daftarkan router MikroTik di Settings',
                'description' => $apiEndpoint
                    ? "Isi host API dengan endpoint tunnel: {$apiEndpoint} (bukan IP lokal)."
                    : 'Tambah router di Pengaturan → Router dengan kredensial API MikroTik.',
                'done' => (bool) $router,
                'required' => true,
                'url' => panel_route('settings').'?tab=mikrotik',
                'action_label' => 'Settings Router',
            ],
            [
                'key' => 'api',
                'title' => 'Test koneksi API berhasil',
                'description' => 'Setelah tunnel running, klik Test Koneksi di Settings. Status API OK = panel bisa baca PPPoE & billing.',
                'done' => (bool) ($router?->last_check_ok),
                'required' => true,
                'url' => panel_route('settings').'?tab=mikrotik',
                'action_label' => 'Test API',
            ],
            [
                'key' => 'remote',
                'title' => 'Remote Winbox / OLT via tunnel',
                'description' => 'Setelah OVPN running, Winbox bisa diakses dari port tunnel (lihat daftar port di halaman Remote).',
                'done' => (bool) ($router?->last_check_ok),
                'required' => false,
                'url' => panel_route('remote'),
                'action_label' => 'Port remote',
            ],
            [
                'key' => 'gis',
                'title' => 'Atur peta ODP (opsional)',
                'description' => 'Tambahkan OLT → ODC → ODP di Peta GIS untuk monitoring jaringan fiber.',
                'done' => $tenant->metaGet('onboarding.gis_started', false)
                    || \App\Models\Odp::query()->where('tenant_id', $tenant->id)->exists(),
                'required' => false,
                'url' => panel_route('gis'),
                'action_label' => 'Buka Peta GIS',
            ],
        ];

        $required = array_values(array_filter($steps, fn ($s) => $s['required']));
        $requiredDone = count(array_filter($required, fn ($s) => $s['done']));
        $allDone = count(array_filter($steps, fn ($s) => $s['done']));

        return [
            'steps' => $steps,
            'completed' => $allDone,
            'total' => count($steps),
            'required_completed' => $requiredDone,
            'required_total' => count($required),
            'progress' => count($required) > 0
                ? (int) round(($requiredDone / count($required)) * 100)
                : 100,
            'all_required_done' => $requiredDone >= count($required),
            'dismissed' => (bool) $tenant->metaGet('onboarding.dismissed', false),
            'eligible' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyChecklist(): array
    {
        return [
            'steps' => [],
            'completed' => 0,
            'total' => 0,
            'required_completed' => 0,
            'required_total' => 0,
            'progress' => 100,
            'all_required_done' => true,
            'dismissed' => true,
            'eligible' => false,
        ];
    }

    public function markScriptConfirmed(Tenant $tenant): void
    {
        $tenant->metaSet('onboarding.script_confirmed', true);
        $tenant->metaSet('onboarding.script_confirmed_at', wib_now()->toIso8601String());
        $tenant->save();
    }

    public function dismiss(Tenant $tenant): void
    {
        $tenant->metaSet('onboarding.dismissed', true);
        $tenant->metaSet('onboarding.dismissed_at', wib_now()->toIso8601String());
        $tenant->metaSet('onboarding.pending', false);
        $tenant->save();
    }
}
