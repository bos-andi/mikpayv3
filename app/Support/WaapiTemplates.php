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
                'default' => "Yth. *{nama}*,\n\n"
                    ."Berikut informasi tagihan WiFi Anda untuk periode *{periode}* dengan total *{nominal}*.\n"
                    ."Tanggal jatuh tempo: *{tanggal}*.\n\n"
                    ."Silakan melakukan pembayaran melalui tautan berikut:\n{link}\n\n"
                    .'Abaikan pesan ini apabila pembayaran sudah dilakukan. Terima kasih.',
            ],
            'h3' => [
                'title' => 'Pengingat H-3',
                'desc' => 'Otomatis 3 hari sebelum jatuh tempo (jam 08:00 WIB).',
                'auto' => true,
                'default' => "Yth. *{nama}*,\n\n"
                    ."Kami mengingatkan bahwa tagihan WiFi periode *{periode}* dengan total *{nominal}* akan jatuh tempo pada *{tanggal}* (dalam *3 hari*).\n\n"
                    ."Mohon dapat dilakukan pembayaran melalui tautan berikut agar layanan tetap nyaman digunakan:\n{link}\n\n"
                    .'Terima kasih atas perhatiannya.',
            ],
            'h0' => [
                'title' => 'Pengingat H-0 (jatuh tempo)',
                'desc' => 'Otomatis pada hari jatuh tempo / hari janji bayar (jam 08:00 WIB).',
                'auto' => true,
                'default' => "Yth. *{nama}*,\n\n"
                    ."Hari ini (*{tanggal}*) adalah hari terakhir untuk melakukan pembayaran tagihan WiFi periode *{periode}* dengan total *{nominal}*.\n\n"
                    ."Mohon segera menyelesaikan pembayaran melalui tautan berikut:\n{link}\n\n"
                    .'Terima kasih atas kerjasamanya.',
            ],
            'overdue' => [
                'title' => 'Tunggakan / siap isolir',
                'desc' => 'Otomatis setelah lewat jatuh tempo (masih menunggak, jam 08:00 WIB).',
                'auto' => true,
                'default' => "Yth. *{nama}*,\n\n"
                    ."Menurut catatan kami, tagihan WiFi periode *{periode}* dengan total *{nominal}* (jatuh tempo *{tanggal}*) masih belum kami terima.\n\n"
                    ."Mohon segera melakukan pembayaran melalui tautan berikut agar layanan tidak terganggu:\n{link}\n\n"
                    .'Apabila sudah transfer, silakan balas pesan ini atau hubungi admin. Terima kasih.',
            ],
            'paid' => [
                'title' => 'Konfirmasi lunas',
                'desc' => 'Referensi / notifikasi setelah pembayaran diterima.',
                'auto' => false,
                'default' => "Yth. *{nama}*,\n\n"
                    ."Pembayaran tagihan periode *{periode}* sebesar *{nominal}* sudah kami terima.\n\n"
                    .'Terima kasih. Layanan Anda tetap aktif.',
            ],
            'isolir_open' => [
                'title' => 'Isolir dibuka',
                'desc' => 'Referensi setelah isolir dibuka pasca bayar.',
                'auto' => false,
                'default' => "Yth. *{nama}*,\n\n"
                    ."Pembayaran sudah kami terima. Isolir untuk akun *{username}* telah dibuka kembali.\n\n"
                    .'Silakan memeriksa koneksi WiFi Anda. Terima kasih.',
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
