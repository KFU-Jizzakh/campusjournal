<?php

namespace App\Notifications;

use App\Models\Review;
use App\Traits\HasEditorNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PURPOSE: In-app and email notification sent to the assigning
 * editor when a reviewer completes their review.
 *
 * SPECIFICATION: SPEC-03/AC-4
 */
class ReviewCompleted extends Notification implements ShouldQueue
{
    use HasEditorNotificationChannels;
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
            'event_description' => 'Рецензия завершена',
            'message_preview' => $this->review->reviewer->full_name.' завершил рецензирование.',
            'author_name' => $this->review->reviewer->full_name,
            'route' => 'editorial.show',
            'route_params' => ['article' => $article->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->review->article;
        $reviewerName = $this->review->reviewer->full_name;

        return (new MailMessage)
            ->subject('Рецензия завершена')
            ->greeting('Здравствуйте!')
            ->line($reviewerName.' завершил рецензирование статьи.')
            ->line('Название: «'.$article->title.'»')
            ->action('Перейти к статье', route('editorial.show', $article))
            ->salutation('С уважением, редакция журнала');
    }
}
