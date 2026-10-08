<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeTenantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Tenant $tenant) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->tenant;
        $panelUrl = tenant_panel_url($tenant, '/dashboard');
        $trialUntil = format_wib($tenant->subscriptionEndsAt());

        $message = (new MailMessage)
            ->subject('Selamat datang di MikPay — panel '.$tenant->panelHost().' siap')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Pendaftaran MikPay Anda berhasil. Panel billing ISP Anda sudah aktif dan siap dipakai.')
            ->line('**Alamat panel:** '.$tenant->panelHost())
            ->line('**Email login:** '.$notifiable->email);

        if ($trialUntil) {
            $message->line('**Masa trial gratis berlaku sampai:** '.$trialUntil);
        }

        return $message
            ->action('Buka Panel MikPay', $panelUrl)
            ->line('Langkah berikutnya: buka menu **Remote & OpenVPN**, salin script ke MikroTik, lalu tambahkan router di **Settings → Router** dan klik Test Koneksi. Panduan lengkap tersedia di halaman Mulai pada panel.')
            ->line('Simpan email ini sebagai pengingat alamat panel Anda.');
    }
}
