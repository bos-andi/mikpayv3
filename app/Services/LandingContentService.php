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
        $trialDays = (int) config('mikpay.trial_days', 4);

        return [
            'hero_badge' => 'Software All-in-One ISP & RTRW Net',
            'hero_title' => 'Kelola Bisnis WiFi, Remote OLT & Auto Isolir',
            'hero_title_highlight' => 'Tanpa Pusing.',
            'hero_subtitle' => 'Aplikasi manajemen terpadu untuk RT RW Net & ISP: tagihan otomatis via WhatsApp, auto-isolir MikroTik, peta ODP, voucher hotspot, plus VPN remote gratis tanpa perlu IP Public.',
            'hero_cta_primary' => "Mulai Coba Gratis {$trialDays} Hari",
            'hero_cta_secondary' => 'Pelajari Remote VPN Gratis',
            'cta_title' => 'Siap Mengotomatiskan Bisnis Internet Anda?',
            'cta_subtitle' => "Daftar sekarang dan nikmati seluruh fitur MikPay + VPN remote gratis selama {$trialDays} hari tanpa komitmen.",
            'cta_button' => 'Daftar & Coba Gratis',
            'faqs' => [
                [
                    'id' => 'faq1',
                    'q' => 'Apakah MikPay membutuhkan IP Public Statis di lokasi saya?',
                    'a' => '<strong>Tidak.</strong> Setiap akun mendapat 1 akun VPN remote (OpenVPN) gratis. MikroTik Anda cukup terhubung ke server MikPay, lalu Winbox, API, Web OLT, dan web modem pelanggan bisa diakses dari mana saja.',
                ],
                [
                    'id' => 'faq2',
                    'q' => 'Apakah mendukung RouterOS v6 dan v7?',
                    'a' => '<strong>Ya, keduanya didukung.</strong> Panel otomatis membuatkan script OpenVPN yang sesuai untuk RouterOS v6 maupun v7. Cukup copy-paste ke Terminal Winbox.',
                ],
                [
                    'id' => 'faq3',
                    'q' => 'Bagaimana cara kerja Auto-Isolir pelanggan?',
                    'a' => 'MikPay terhubung ke MikroTik via API. Saat pelanggan melewati tanggal jatuh tempo, sistem otomatis mengisolir akun PPPoE-nya. Begitu tagihan dibayar, akses dibuka kembali secara otomatis.',
                ],
                [
                    'id' => 'faq4',
                    'q' => 'OLT merk apa saja yang bisa diremote?',
                    'a' => 'Semua OLT yang memiliki halaman web management (HSGQ, HIOSO, ZTE, VSOL, Huawei, dll) bisa dibuka lewat Remote Web OLT melalui tunnel MikPay, selama OLT terhubung ke MikroTik Anda.',
                ],
                [
                    'id' => 'faq5',
                    'q' => 'Bisa perpanjang langganan sebelum masa aktif habis?',
                    'a' => '<strong>Bisa.</strong> Perpanjangan bisa dilakukan kapan saja. Sisa masa aktif tidak hangus; durasi paket baru ditambahkan ke tanggal berakhir yang sekarang.',
                ],
                [
                    'id' => 'faq6',
                    'q' => 'Berapa router MikroTik yang bisa dihubungkan?',
                    'a' => 'Setiap akun dapat menghubungkan 1 router MikroTik dengan jumlah pelanggan tanpa batas. Hubungi admin jika Anda membutuhkan lebih dari 1 router.',
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
