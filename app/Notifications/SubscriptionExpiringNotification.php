<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
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
            ->subject("{$label} MikPay {$tenant->panelHost()} segera berakhir")
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("{$label} MikPay untuk panel **{$tenant->panelHost()}** akan berakhir pada **{$endsAt}**.")
            ->line('Setelah berakhir, panel akan terkunci dan tunnel OpenVPN bonus dinonaktifkan sampai paket diperpanjang. Data pelanggan dan billing Anda tetap aman.')
            ->action('Perpanjang Sekarang', tenant_panel_url($tenant, '/langganan'))
            ->line('Abaikan email ini jika Anda sudah melakukan pembayaran.');
    }
}
