<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FonnteSetting extends Model
{
    protected $fillable = [
        'user_id',
        'enabled',
        'api_token',
        'sender_name',
        'auto_send_h3',
        'auto_send_h0',
        'auto_send_overdue',
        'send_window_start',
        'send_window_end',
        'antispam_enabled',
        'antispam_delay_min',
        'antispam_delay_max',
        'antispam_hourly_limit',
        'antispam_daily_limit',
        'antispam_randomize_message',
        'template_greetings',
        'template_reminder',
        'template_due_today',
        'template_janji_bayar',
        'template_overdue',
        'template_closings',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'auto_send_h3' => 'boolean',
            'auto_send_h0' => 'boolean',
            'auto_send_overdue' => 'boolean',
            'antispam_enabled' => 'boolean',
            'antispam_randomize_message' => 'boolean',
            'template_greetings' => 'array',
            'template_closings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Default template & salam/penutup untuk pengguna baru (Bahasa Indonesia).
     *
     * @return array<string, mixed>
     */
    public static function defaultTemplatePayload(): array
    {
        return [
            'enabled' => false,
            'sender_name' => 'MIKPAY WiFi',
            'send_window_start' => '08:00',
            'send_window_end' => '20:00',
            'template_greetings' => [
                'Assalamualaikum',
                'Halo',
                'Salam sejahtera',
                'Yth. Bapak/Ibu',
                'Hai',
            ],
            'template_closings' => [
                'Terima kasih atas perhatiannya.',
                'Mohon abaikan pesan ini jika sudah melakukan pembayaran.',
                'Salam hangat dari kami.',
                'Hormat kami,',
            ],
            'template_reminder' => <<<'TXT'
Assalamualaikum *{nama}*,

Ini pengingat tagihan internet/WiFi Anda sebesar *Rp {nominal}* yang akan jatuh tempo pada *{tanggal}* (sekitar *{hari}* hari lagi).

Silakan lakukan pembayaran tepat waktu agar layanan tetap lancar.

_Jika sudah membayar, abaikan pesan ini._

{pengirim}
TXT,
            'template_due_today' => <<<'TXT'
Halo *{nama}*,

Tagihan WiFi Anda sebesar *Rp {nominal}* *jatuh tempo hari ini* (*{tanggal}*).

Mohon segera melakukan pembayaran agar tidak ada gangguan layanan.

{pengirim}
TXT,
            'template_janji_bayar' => <<<'TXT'
Halo *{nama}*,

Ini pengingat *janji bayar* Anda: tagihan WiFi *Rp {nominal}* dijadwalkan dibayar pada *{tanggal}* (hari ini).

Mohon tepati janji pembayaran agar layanan tetap lancar.

_Jika sudah membayar, abaikan pesan ini._

{pengirim}
TXT,
            'template_overdue' => <<<'TXT'
Halo *{nama}*,

Tagihan WiFi Anda sebesar *Rp {nominal}* sudah *melewati jatuh tempo* (tanggal jatuh tempo: *{tanggal}*).

Segera lunasi tagihan agar layanan dapat aktif kembali. Hubungi kami jika butuh bantuan.

{pengirim}
TXT,
        ];
    }

    /**
     * Satu baris pengaturan Fonnte per user (untuk API WhatsApp / reminder tagihan).
     */
    public static function forUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            self::defaultTemplatePayload()
        );
    }

    /**
     * Apakah jam sekarang (zona app) berada di dalam rentang kirim pengingat (format HH:MM 24 jam).
     */
    public function isNowWithinSendWindow(?\DateTimeInterface $at = null): bool
    {
        $at = $at === null ? now() : \Carbon\Carbon::parse($at);
        $now = $at->format('H:i');
        $start = $this->normalizeTimeString($this->send_window_start ?? '08:00');
        $end = $this->normalizeTimeString($this->send_window_end ?? '20:00');

        if ($start === $end) {
            return false;
        }

        if ($start < $end) {
            return $now >= $start && $now <= $end;
        }

        /* Rentang melewati tengah malam, mis. 22:00–06:00 */
        return $now >= $start || $now <= $end;
    }

    private function normalizeTimeString(?string $value): string
    {
        $value = trim((string) $value);
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return '08:00';
    }
}
