<?php

namespace App\Notifications;

use App\Models\Review;
use App\Traits\HasEditorNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PURPOSE: In-app and email notification sent to the editor
 * when a reviewer declines the review invitation.
 *
 * SPECIFICATION: SPEC-03/AC-3
 */
class ReviewerDeclined extends Notification implements ShouldQueue
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
            'event' => 'review.declined',
            'event_description' => 'Рецензент отклонил приглашение',
            'message_preview' => $this->review->reviewer->full_name.' отклонил приглашение на рецензирование.',
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
            ->subject('Рецензент отклонил приглашение')
            ->greeting('Здравствуйте!')
            ->line($reviewerName.' отклонил приглашение на рецензирование.')
            ->line('Название: «'.$article->title.'»')
            ->action('Перейти к статье', route('editorial.show', $article))
            ->salutation('С уважением, редакция журнала');
    }
}
