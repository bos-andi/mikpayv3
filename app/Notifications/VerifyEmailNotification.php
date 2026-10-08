<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const EXPIRE_HOURS = 24;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public static function verificationUrl(User $user): string
    {
        $relative = URL::temporarySignedRoute('verification.verify', now()->addHours(self::EXPIRE_HOURS), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ], absolute: false);

        return central_url($relative);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isPartner = $notifiable instanceof User && $notifiable->isPartner();

        return (new MailMessage)
            ->subject('Verifikasi email akun MikPay Anda')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line($isPartner
                ? 'Terima kasih sudah mendaftar Program Affiliate MikPay. Satu langkah lagi: konfirmasi bahwa email ini benar milik Anda.'
                : 'Terima kasih sudah mendaftar MikPay. Satu langkah lagi: konfirmasi bahwa email ini benar milik Anda supaya panel dan masa trial bisa diaktifkan.')
            ->action('Verifikasi Email Saya', self::verificationUrl($notifiable))
            ->line('Tautan ini berlaku selama '.self::EXPIRE_HOURS.' jam. Jika sudah kedaluwarsa, minta tautan baru dari halaman verifikasi setelah login.')
            ->line('Jika Anda tidak merasa mendaftar di MikPay, abaikan email ini.');
    }
}
