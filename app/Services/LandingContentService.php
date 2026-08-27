<?php

namespace App\Services;

use App\Models\PlatformSetting;

class LandingContentService
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $trialDays = (int) config('mikpay.trial_days', 3);

        return [
            'hero_badge' => 'Software All-in-One ISP & RTRW Net Terlengkap',
            'hero_title' => 'Kelola Bisnis WiFi, Remote OLT & Auto Isolir',
            'hero_title_highlight' => 'Tanpa Pusing.',
            'hero_subtitle' => 'Aplikasi manajemen terpadu untuk pengusaha RTRW Net & ISP. Dilengkapi Auto Isolir WhatsApp, Peta ODP Interaktif, Cetak Voucher, dan Bonus VPN Remote Tunneling Gratis tanpa perlu IP Public Statis.',
            'hero_cta_primary' => "Mulai Coba Gratis {$trialDays} Hari",
            'hero_cta_secondary' => 'Pelajari Remote VPN Gratis',
            'cta_title' => 'Siap Mengotomatiskan Bisnis Internet Anda?',
            'cta_subtitle' => "Daftar sekarang dan nikmati seluruh fitur MikPay + Remote VPN Dedicated Gratis selama {$trialDays} Hari tanpa komitmen!",
            'cta_button' => 'Daftar & Coba Gratis',
            'faqs' => [
                [
                    'id' => 'faq1',
                    'q' => 'Apakah MikPay membutuhkan IP Public Statis di lokasi saya?',
                    'a' => '<strong>Tidak sama sekali!</strong> MikPay menyediakan fasilitas Remote VPN Tunneling gratis. Router MikroTik Anda cukup dial VPN ke server Cloud MikPay, lalu Anda bisa mengakses Winbox & Web OLT dari mana saja.',
                ],
                [
                    'id' => 'faq2',
                    'q' => 'Apakah MikPay mendukung RouterOS v6 dan RouterOS v7?',
                    'a' => '<strong>Ya, keduanya didukung penuh.</strong> Untuk RouterOS v7 kami merekomendasikan protokol WireGuard yang cepat dan hemat CPU. Untuk RouterOS v6 tersedia protokol SSTP.',
                ],
                [
                    'id' => 'faq3',
                    'q' => 'Bagaimana cara kerja fitur Auto-Isolir Pelanggan?',
                    'a' => 'MikPay terhubung ke MikroTik via API. Saat pelanggan melewati tanggal jatuh tempo pembayaran, sistem akan otomatis mengubah Profile PPPoE atau men-disable secret pengguna tersebut secara real-time.',
                ],
                [
                    'id' => 'faq4',
                    'q' => 'Merk OLT apa saja yang didukung oleh MikPay?',
                    'a' => 'MikPay mendukung hampir seluruh OLT EPON & GPON populer di Indonesia, seperti HSGQ, HIOSO, ZTE (C320/C300), VSOL, Huawei, dan V-MAX yang mendukung komunikasi SNMP / SSH / TR-069.',
                ],
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $faqs
     * @return list<array{id: string, q: string, a: string}>
     */
    protected static function normalizeFaqs(array $faqs): array
    {
        $out = [];
        foreach (array_values($faqs) as $i => $faq) {
            if (! is_array($faq)) {
                continue;
            }
            $q = trim((string) ($faq['q'] ?? ''));
            $a = trim((string) ($faq['a'] ?? ''));
            if ($q === '' || $a === '') {
                continue;
            }
            $out[] = [
                'id' => is_string($faq['id'] ?? null) && $faq['id'] !== ''
                    ? (string) $faq['id']
                    : 'faq'.($i + 1),
                'q' => $q,
                'a' => $a,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $defaults = self::defaults();
        $raw = PlatformSetting::get()->landing_content;
        if (! is_array($raw)) {
            return $defaults;
        }

        $out = $defaults;
        foreach ($defaults as $key => $default) {
            if ($key === 'faqs') {
                continue;
            }
            if (is_string($raw[$key] ?? null) && trim($raw[$key]) !== '') {
                $out[$key] = trim($raw[$key]);
            }
        }

        if (isset($raw['faqs']) && is_array($raw['faqs']) && $raw['faqs'] !== []) {
            $faqs = self::normalizeFaqs($raw['faqs']);
            if ($faqs !== []) {
                $out['faqs'] = $faqs;
            }
        } else {
            $out['faqs'] = self::normalizeFaqs($defaults['faqs']);
        }

        return $out;
    }

    public static function faqsText(): string
    {
        $faqs = self::all()['faqs'] ?? [];
        $blocks = [];
        foreach ($faqs as $faq) {
            $blocks[] = trim(($faq['q'] ?? '')."\n".($faq['a'] ?? ''));
        }

        return implode("\n\n", $blocks);
    }

    /**
     * Parse FAQ textarea: blocks separated by blank line; first line = Q, rest = A.
     *
     * @return list<array{q: string, a: string}>
     */
    public static function parseFaqsText(string $text): array
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return [];
        }

        $blocks = preg_split("/\n\s*\n/", $text) ?: [];
        $faqs = [];
        foreach ($blocks as $block) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", trim($block))), static fn ($l) => $l !== ''));
            if (count($lines) < 2) {
                continue;
            }
            $q = array_shift($lines);
            $a = implode("\n", $lines);
            $faqs[] = ['q' => $q, 'a' => $a];
        }

        return $faqs;
    }
}
