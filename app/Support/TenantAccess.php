<?php

namespace App\Support;

/**
 * Hak akses menu panel tenant untuk staf (role tenant_user).
 * Admin tenant & superadmin selalu punya akses penuh.
 */
class TenantAccess
{
    /** Hanya admin tenant: langganan, payment gateway, setup awal, manajemen user. */
    public const ADMIN = 'admin';

    /**
     * @var array<string, array{label: string, desc: string, icon: string, group: string}>
     */
    public const MODULES = [
        'customers' => ['label' => 'Data Pelanggan', 'desc' => 'Lihat, tambah, edit, isolir & buka pelanggan', 'icon' => 'fa-users', 'group' => 'Operasional'],
        'billing' => ['label' => 'Billing & Tagihan', 'desc' => 'Catat pembayaran, link bayar, janji bayar', 'icon' => 'fa-file-invoice-dollar', 'group' => 'Operasional'],
        'vouchers' => ['label' => 'Hotspot & Voucher', 'desc' => 'Generate, cetak & kelola voucher hotspot', 'icon' => 'fa-ticket', 'group' => 'Operasional'],
        'tickets' => ['label' => 'Tiket Gangguan', 'desc' => 'Buat & tangani tiket gangguan', 'icon' => 'fa-headset', 'group' => 'Operasional'],
        'gis' => ['label' => 'Peta GIS & ODP', 'desc' => 'Kelola titik ODP & port', 'icon' => 'fa-map-location-dot', 'group' => 'Jaringan'],
        'remote' => ['label' => 'Remote, VPN & OLT', 'desc' => 'Remote router & kontrol OLT', 'icon' => 'fa-server', 'group' => 'Jaringan'],
        'finance' => ['label' => 'Laporan Keuangan', 'desc' => 'Laba rugi, neraca, buku kas (tanpa tarik dana)', 'icon' => 'fa-wallet', 'group' => 'Keuangan'],
        'settings_router' => ['label' => 'Pengaturan Router MikroTik', 'desc' => 'Tambah, edit & test router', 'icon' => 'fa-network-wired', 'group' => 'Pengaturan'],
        'settings_billing' => ['label' => 'Pengaturan Billing & Isolir', 'desc' => 'Masa tenggang isolir', 'icon' => 'fa-calendar-days', 'group' => 'Pengaturan'],
        'settings_whatsapp' => ['label' => 'Pengaturan WhatsApp Gateway', 'desc' => 'Koneksi & template notifikasi WA', 'icon' => 'fa-comment-dots', 'group' => 'Pengaturan'],
    ];

    /**
     * @var array<string, array{label: string, desc: string, icon: string, modules: list<string>}>
     */
    public const PRESETS = [
        'kasir' => ['label' => 'Kasir / Penagih', 'desc' => 'Urus pelanggan & tagihan', 'icon' => 'fa-cash-register', 'modules' => ['customers', 'billing']],
        'teknisi' => ['label' => 'Teknisi', 'desc' => 'Jaringan, ODP & tiket gangguan', 'icon' => 'fa-screwdriver-wrench', 'modules' => ['customers', 'tickets', 'gis', 'remote']],
        'voucher' => ['label' => 'Operator Voucher', 'desc' => 'Khusus hotspot & voucher', 'icon' => 'fa-ticket', 'modules' => ['vouchers']],
        'custom' => ['label' => 'Custom', 'desc' => 'Pilih menu sendiri', 'icon' => 'fa-sliders', 'modules' => []],
    ];

    /** @var array<string, string> tab pengaturan => modul */
    public const SETTINGS_TABS = [
        'mikrotik' => 'settings_router',
        'payment' => self::ADMIN,
        'billing' => 'settings_billing',
        'whatsapp' => 'settings_whatsapp',
    ];

    /** @return list<string> */
    public static function moduleKeys(): array
    {
        return array_keys(self::MODULES);
    }

    /**
     * @param  mixed  $modules
     * @return list<string>
     */
    public static function sanitize($modules): array
    {
        if (! is_array($modules)) {
            return [];
        }

        $valid = self::moduleKeys();

        return array_values(array_filter($valid, fn (string $key) => in_array($key, $modules, true)));
    }

    /** @param  list<string>  $modules */
    public static function presetFor(array $modules): string
    {
        $sorted = self::sanitize($modules);
        foreach (self::PRESETS as $key => $preset) {
            if ($key === 'custom') {
                continue;
            }
            if (self::sanitize($preset['modules']) === $sorted) {
                return $key;
            }
        }

        return 'custom';
    }
}
