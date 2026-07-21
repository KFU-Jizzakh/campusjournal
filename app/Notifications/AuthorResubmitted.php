<?php

namespace App\Notifications;

use App\Models\Article;
use App\Traits\HasEditorNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PURPOSE: In-app and email notification sent to the editor
 * when the author resubmits the article after a revision
 * request.
 *
 * SPECIFICATION: SPEC-01/AC-7
 */
class AuthorResubmitted extends Notification implements ShouldQueue
{
    use HasEditorNotificationChannels;
    use Queueable;

    public function __construct(
        public Article $article
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'article_id' => $this->article->id,
            'article_title' => $this->article->title,
            'event' => 'submission.revised',
            'event_description' => 'Автор повторно подал рукопись',
            'message_preview' => 'Автор внёс правки и повторно подал рукопись.',
            'author_name' => 'Система',
            'route' => 'editorial.show',
            'route_params' => ['article' => $this->article->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->article;

        return (new MailMessage)
            ->subject('Автор повторно подал рукопись')
            ->greeting('Здравствуйте!')
            ->line('Автор внёс правки и повторно подал рукопись на рассмотрение.')
            ->line('Название: «'.$article->title.'»')
            ->action('Перейти к статье', route('editorial.show', $article))
            ->salutation('С уважением, редакция журнала');
    }
}
