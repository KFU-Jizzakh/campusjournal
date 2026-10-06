<?php

namespace App\Notifications;

use App\Models\Article;
use App\Models\Author;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * PURPOSE: Sent to an existing verified user whose email was listed as a
 * coauthor on a submission, inviting them to claim the author record so
 * they receive the full article notification stream.
 */
class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Article $article,
        public Author $author,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'article_id' => $this->article->id,
            'article_title' => $this->article->title,
            'event' => 'author.invited',
            'event_description' => 'Приглашение стать соавтором',
            'message_preview' => 'Вас указали соавтором статьи «'.$this->article->title.'». Примите приглашение, чтобы следить за статусом и получать уведомления.',
            'author_name' => 'Система',
            'url' => $this->invitationUrl(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Вас указали соавтором статьи — «'.$this->article->title.'»')
            ->greeting('Здравствуйте!')
            ->line('Автор статьи «'.$this->article->title.'» указал ваш email в числе соавторов.')
            ->line('Примите приглашение, чтобы привязать профиль соавтора к вашей учётной записи и получать уведомления о статусе статьи.')
            ->action('Принять приглашение', $this->invitationUrl())
            ->line('Ссылка действительна 7 дней. Если вы не участвовали в подготовке статьи, просто проигнорируйте это письмо.')
            ->salutation('С уважением, редакция журнала');
    }

    /**
     * Signed claim URL, proxy-safe (relative signed route + app.url prefix).
     * The signature covers both the inviting article and the author record.
     */
    private function invitationUrl(): string
    {
        $relativeUrl = URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(7),
            ['article' => $this->article->id, 'author' => $this->author->id],
            absolute: false
        );

        return rtrim(config('app.url'), '/').$relativeUrl;
    }
}
