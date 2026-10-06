<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PURPOSE: Sent to a reviewer when the editor cancels their assignment
 * (pending or in-progress), so the reviewer is not left waiting on a
 * deadline that no longer exists.
 */
class ReviewCancelled extends Notification implements ShouldQueue
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
            'event' => 'review.cancelled',
            'event_description' => 'Назначение на рецензирование отменено',
            'message_preview' => 'Редактор отменил назначение на рецензирование статьи «'.$article->title.'».',
            'author_name' => 'Система',
            'route' => 'reviews.show',
            'route_params' => ['review' => $this->review->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->review->article;

        return (new MailMessage)
            ->subject('Назначение на рецензирование отменено — «'.$article->title.'»')
            ->greeting('Здравствуйте!')
            ->line('Редактор отменил назначение на рецензирование статьи «'.$article->title.'». Приносим извинения за беспокойство — дедлайн по этой заявке больше не действует.')
            ->action('Перейти к рецензиям', route('reviews.index'))
            ->salutation('С уважением, редакция журнала');
    }
}
