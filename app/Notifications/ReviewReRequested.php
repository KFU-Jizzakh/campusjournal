<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PURPOSE: In-app and email notification sent to a reviewer when the
 * editor re-invites them for a new review round of an article they
 * already reviewed in an earlier round.
 *
 * SPECIFICATION: SPEC-25/AC-3
 */
class ReviewReRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Review $review
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $article = $this->review->article;

        return [
            'article_id' => $article->id,
            'article_title' => $article->title,
            'event' => 'review.re_requested',
            'event_description' => 'Повторный запрос рецензии (раунд '.$this->review->round.')',
            'message_preview' => 'Редактор повторно запрашивает рецензию по статье «'.$article->title.'» (раунд '.$this->review->round.').',
            'author_name' => 'Система',
            'route' => 'reviews.show',
            'route_params' => ['review' => $this->review->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->review->article;

        return (new MailMessage)
            ->subject('Повторный запрос рецензии (раунд '.$this->review->round.') — '.$article->title)
            ->greeting('Здравствуйте!')
            ->line('Редактор повторно направляет вам статью на рецензирование — раунд '.$this->review->round.'. Просьба принять или отклонить заявку.')
            ->line('Название: «'.$article->title.'»')
            ->action('Перейти к рецензии', route('reviews.show', $this->review))
            ->salutation('С уважением, редакция журнала');
    }
}
