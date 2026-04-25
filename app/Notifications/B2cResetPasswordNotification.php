<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class B2cResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $b2cDomain = env('B2C_DOMAIN', 'gruprezervasyonlari.com');
        $resetUrl  = 'https://' . $b2cDomain . '/hesabim/sifre-sifirla/' . $this->token
            . '?email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Şifre Sıfırlama — Grup Rezervasyonları')
            ->from(
                env('B2C_MAIL_FROM_ADDRESS', 'noreply@gruprezervasyonlari.com'),
                env('B2C_MAIL_FROM_NAME', 'Grup Rezervasyonları')
            )
            ->greeting('Merhaba ' . explode(' ', $notifiable->name)[0] . ',')
            ->line('Şifre sıfırlama talebinizi aldık. Aşağıdaki butona tıklayarak yeni şifrenizi belirleyebilirsiniz.')
            ->action('Şifremi Sıfırla', $resetUrl)
            ->line('Bu bağlantı **60 dakika** geçerlidir.')
            ->line('Şifre sıfırlama talebinde bulunmadıysanız bu e-postayı görmezden gelebilirsiniz.')
            ->salutation('GrupRezervasyonları Ekibi');
    }
}
