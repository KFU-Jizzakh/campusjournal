<?php

namespace App\Notifications;

use App\Models\Review;
use App\Traits\HasEditorNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PURPOSE: In-app and email notification sent to the editor when a
 * reviewer accepts the review invitation.
 *
 * SPECIFICATION: SPEC-03/AC-2
 */
class ReviewerAccepted extends Notification implements ShouldQueue
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
            'event' => 'review.accepted',
            'event_description' => 'Рецензент принял приглашение',
            'message_preview' => $this->review->reviewer->full_name.' принял приглашение на рецензирование.',
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
            ->subject('Рецензент принял приглашение')
            ->greeting('Здравствуйте!')
            ->line($reviewerName.' принял приглашение на рецензирование.')
            ->line('Название: «'.$article->title.'»')
            ->action('Перейти к статье', route('editorial.show', $article))
            ->salutation('С уважением, редакция журнала');
    }
}
