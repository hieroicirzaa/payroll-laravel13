<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordFrontendNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset Password Payroll System')
            ->line('Anda menerima email ini karena ada permintaan reset password untuk akun payroll Anda.')
            ->action('Reset Password', $url)
            ->line('Link reset password ini memiliki masa berlaku terbatas. Abaikan email ini jika Anda tidak meminta reset password.');
    }
}
