<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiredNotification extends Notification implements ShouldQueue
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
        $isTrial = ($tenant->plan ?? '') === 'trial';
        $label = $isTrial ? 'Masa trial' : 'Langganan';
        $endsAt = format_wib($tenant->subscriptionEndsAt());

        return (new MailMessage)
            ->error()
            ->subject("{$label} MikPay {$tenant->panelHost()} telah berakhir")
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("{$label} MikPay untuk panel **{$tenant->panelHost()}** telah berakhir pada **{$endsAt}**.")
            ->line('Panel saat ini terkunci dan tunnel OpenVPN bonus dinonaktifkan. Data pelanggan, billing, dan pengaturan router Anda tetap tersimpan.')
            ->line('Pilih paket dan lakukan pembayaran untuk mengaktifkan kembali panel Anda.')
            ->action('Pilih Paket & Bayar', tenant_panel_url($tenant, '/langganan'));
    }
}
