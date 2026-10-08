<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = central_url('/reset-password/'.$this->token).'?'.http_build_query([
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Reset password akun MikPay')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Kami menerima permintaan reset password untuk akun MikPay Anda.')
            ->action('Buat Password Baru', $url)
            ->line("Tautan ini berlaku selama {$minutes} menit.")
            ->line('Jika Anda tidak meminta reset password, abaikan email ini — password Anda tidak akan berubah.');
    }
}
