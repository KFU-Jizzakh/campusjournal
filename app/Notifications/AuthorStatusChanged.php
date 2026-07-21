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
 * coauthors when the article status changes (InReview,
 * Copyediting, Production, Published).
 *
 * SPECIFICATION: SPEC-12/AC-4, SPEC-12/AC-5, SPEC-12/BR-2
 */
class AuthorStatusChanged extends Notification implements ShouldQueue
{
    use HasNotificationChannels;
    use Queueable;

    public function __construct(
        public Article $article,
        public string $event,
        public string $eventDescription
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'article_id' => $this->article->id,
            'article_title' => $this->article->title,
            'event' => $this->event,
            'event_description' => $this->eventDescription,
            'message_preview' => 'Статус статьи изменился.',
            'author_name' => 'Система',
            'route' => 'submissions.show',
            'route_params' => ['article' => $this->article->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->article;

        return (new MailMessage)
            ->subject($this->eventDescription)
            ->greeting('Здравствуйте!')
            ->line('Статус вашей статьи изменился: **'.$this->eventDescription.'**')
            ->line('Название: «'.$article->title.'»')
            ->action('Перейти к статье', route('submissions.show', $article))
            ->salutation('С уважением, редакция журнала');
    }
}
