<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * PURPOSE: Email notification in Russian asking the user to confirm
 * their email address via a signed verification link.
 *
 * SPECIFICATION: SPEC-23/AC-1, SPEC-23/AC-3, SPEC-23/AC-5, SPEC-23/AC-6
 */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * PURPOSE: Builds the Russian verification email with the signed
     * verification link.
     *
     * SPECIFICATION: SPEC-23/AC-1
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Подтвердите ваш email')
            ->greeting('Здравствуйте!')
            ->line('Спасибо за регистрацию на сайте журнала. Пожалуйста, подтвердите ваш адрес электронной почты.')
            ->action('Подтвердить email', $verificationUrl)
            ->line('Если вы не регистрировались на сайте, просто проигнорируйте это письмо.')
            ->salutation('С уважением, редакция журнала');
    }
}
