<?php

namespace App\Support;

/**
 * Template & toggle reminder WhatsApp (WaAPI) per tenant.
 */
class WaapiTemplates
{
    /** @return array<string, array{title: string, desc: string, auto: bool, default: string}> */
    public static function definitions(): array
    {
        return [
            'billing_link' => [
                'title' => 'Reminder tunggakan + link bayar',
                'desc' => 'Dipakai tombol WA di Billing (kirim manual). Selalu tersedia.',
                'auto' => false,
                'default' => "Halo *{nama}*, tagihan *{periode}* sebesar *{nominal}* belum lunas.\nBayar di sini (tanpa login):\n{link}\nAbaikan jika sudah bayar.",
            ],
            'h3' => [
                'title' => 'Pengingat H-3',
                'desc' => 'Otomatis 3 hari sebelum jatuh tempo.',
                'auto' => true,
                'default' => "Yth. *{nama}*,\nTagihan WiFi periode *{periode}* sebesar *{nominal}* akan jatuh tempo 3 hari lagi.\nBayar sekarang agar layanan tetap lancar:\n{link}\nTerima kasih 🙏",
            ],
            'h0' => [
                'title' => 'Pengingat H-0 (jatuh tempo)',
                'desc' => 'Otomatis pada hari jatuh tempo.',
                'auto' => true,
                'default' => "Halo *{nama}*,\nHari ini adalah jatuh tempo tagihan *{periode}* (*{nominal}*).\nSegera bayar agar tidak terisolir:\n{link}",
            ],
            'overdue' => [
                'title' => 'Tunggakan / siap isolir',
                'desc' => 'Otomatis setelah lewat jatuh tempo (masih menunggak).',
                'auto' => true,
                'default' => "*PERINGATAN*\nYth. *{nama}*,\nTagihan *{periode}* (*{nominal}*) masih belum lunas. Layanan berisiko *isolir*.\nBayar segera:\n{link}\nHubungi admin jika sudah transfer.",
            ],
            'paid' => [
                'title' => 'Konfirmasi lunas',
                'desc' => 'Referensi / notifikasi setelah pembayaran diterima.',
                'auto' => false,
                'default' => "Halo *{nama}*,\nPembayaran tagihan *{periode}* sebesar *{nominal}* sudah kami terima. Terima kasih 🙏\nLayanan Anda tetap aktif.",
            ],
            'isolir_open' => [
                'title' => 'Isolir dibuka',
                'desc' => 'Referensi setelah isolir dibuka pasca bayar.',
                'auto' => false,
                'default' => "Yth. *{nama}*,\nPembayaran Anda sudah masuk. Isolir untuk akun *{username}* telah *dibuka*.\nSilakan cek koneksi WiFi Anda. Terima kasih.",
            ],
        ];
    }

    public static function body(\App\Models\Tenant $tenant, string $key): string
    {
        $defs = self::definitions();
        if (! isset($defs[$key])) {
            return '';
        }

        $stored = $tenant->metaGet('waapi.templates.'.$key);
        if (is_string($stored) && trim($stored) !== '') {
            return $stored;
        }

        // Legacy single reminder → billing_link
        if ($key === 'billing_link') {
            $legacy = $tenant->waapiReminderTemplate();
            if ($legacy) {
                return $legacy;
            }
        }

        return $defs[$key]['default'];
    }

    public static function autoEnabled(\App\Models\Tenant $tenant, string $key): bool
    {
        $defs = self::definitions();
        if (! ($defs[$key]['auto'] ?? false)) {
            return false;
        }

        return (bool) $tenant->metaGet('waapi.auto.'.$key, false);
    }
}
