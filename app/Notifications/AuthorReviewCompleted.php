<?php

namespace App\Notifications;

use App\Models\Review;
use App\Traits\HasNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PURPOSE: In-app and email notification sent to the author and
 * coauthors when a peer review of their article is completed.
 *
 * SPECIFICATION: SPEC-12/AC-2, SPEC-12/BR-2
 */
class AuthorReviewCompleted extends Notification implements ShouldQueue
{
    use HasNotificationChannels;
    use Queueable;

    public function __construct(
        public Review $review
    ) {}

    public function toArray(object $notifiable): array
    {
        $article = $this->review->article;

        return [
            'article_id' => $article->id,
            'article_title' => $article->title,
            'event' => 'review.completed',
            'event_description' => 'По вашей статье завершена рецензия',
            'message_preview' => 'Рецензент завершил рассмотрение вашей статьи.',
            'author_name' => 'Система',
            'route' => 'submissions.show',
            'route_params' => ['article' => $article->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->review->article;

        return (new MailMessage)
            ->subject('По вашей статье завершена рецензия')
            ->greeting('Здравствуйте!')
            ->line('Рецензент завершил рассмотрение вашей статьи.')
            ->line('Название: «'.$article->title.'»')
            ->action('Перейти к статье', route('submissions.show', $article))
            ->line('Вы увидите рецензию после того, как редактор примет решение.')
            ->salutation('С уважением, редакция журнала');
    }
}
