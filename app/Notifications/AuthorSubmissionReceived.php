<?php

namespace App\Notifications;

use App\Models\Article;
use App\Traits\HasNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PURPOSE: In-app and email notification sent to the author and
 * coauthors when their manuscript is successfully submitted.
 *
 * SPECIFICATION: SPEC-12/AC-1, SPEC-12/BR-2
 */
class AuthorSubmissionReceived extends Notification implements ShouldQueue
{
    use HasNotificationChannels;
    use Queueable;

    public function __construct(
        public Article $article
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'article_id' => $this->article->id,
            'article_title' => $this->article->title,
            'event' => 'submission.created',
            'event_description' => 'Рукопись получена',
            'message_preview' => 'Ваша рукопись успешно подана в редакцию.',
            'author_name' => 'Система',
            'route' => 'submissions.show',
            'route_params' => ['article' => $this->article->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->article;

        return (new MailMessage)
            ->subject('Ваша рукопись получена')
            ->greeting('Здравствуйте!')
            ->line('Ваша рукопись успешно подана в редакцию журнала.')
            ->line('Название: «'.$article->title.'»')
            ->action('Перейти к статье', route('submissions.show', $article))
            ->line('Вы будете получать уведомления об изменении статуса статьи.')
            ->salutation('С уважением, редакция журнала');
    }
}
