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

    public function __construct(public Tenant $tenant, public bool $dueToday = false) {}

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
        $end = $tenant->subscriptionEndsAt();

        if ($this->dueToday) {
            $subject = "{$label} MikPay {$tenant->panelHost()} berakhir hari ini";
            $intro = "{$label} MikPay untuk panel **{$tenant->panelHost()}** jatuh tempo **hari ini pukul ".format_wib($end, 'H:i').'**.';
        } else {
            $days = $end ? max(1, (int) round(wib_now()->startOfDay()->diffInDays($end->copy()->startOfDay()))) : null;
            $subject = "{$label} MikPay {$tenant->panelHost()} berakhir ".($days ? "{$days} hari lagi" : 'segera');
            $intro = "{$label} MikPay untuk panel **{$tenant->panelHost()}** akan berakhir pada **".format_wib($end).'**'.($days ? " ({$days} hari lagi)." : '.');
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line($intro)
            ->line('Setelah berakhir, panel akan terkunci dan tunnel OpenVPN bonus dinonaktifkan sampai paket diperpanjang. Data pelanggan dan billing Anda tetap aman.')
            ->action('Perpanjang Sekarang', tenant_panel_url($tenant, '/langganan'))
            ->line('Abaikan email ini jika Anda sudah melakukan pembayaran.');
    }
}
