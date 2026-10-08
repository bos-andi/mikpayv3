<?php

namespace App\Notifications;

use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PartnerApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Partner $partner) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $partner = $this->partner;

        return (new MailMessage)
            ->subject('Akun Affiliate MikPay Anda sudah aktif — kode '.$partner->code)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Selamat, pendaftaran Anda sebagai **Affiliate MikPay** telah disetujui.')
            ->line('**Kode referral:** '.$partner->code)
            ->line('**Link referral:** '.$partner->referralUrl())
            ->line('Bagikan link tersebut ke pemilik ISP / RT RW Net. Setiap kali pelanggan rujukan Anda membayar langganan MikPay, komisi otomatis masuk ke panel affiliate.')
            ->action('Buka Panel Affiliate', central_url('/affiliate/dashboard'))
            ->line('Terima kasih sudah membantu ISP lokal tumbuh bersama MikPay.');
    }
}
