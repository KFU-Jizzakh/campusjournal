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
 * when the author approves the galley proof, unblocking
 * publication.
 *
 * SPECIFICATION: SPEC-13/AC-4
 */
class AuthorApprovedGalley extends Notification implements ShouldQueue
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
            'event' => 'galley.approved',
            'event_description' => 'Автор утвердил гранки',
            'message_preview' => 'Автор утвердил гранки. Статья готова к публикации.',
            'author_name' => 'Система',
            'route' => 'editorial.show',
            'route_params' => ['article' => $this->article->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->article;

        return (new MailMessage)
            ->subject('Автор утвердил гранки')
            ->greeting('Здравствуйте!')
            ->line('Автор утвердил свёрстанную версию статьи.')
            ->line('Название: «'.$article->title.'»')
            ->line('Статья готова к публикации.')
            ->action('Перейти к статье', route('editorial.show', $article))
            ->salutation('С уважением, редакция журнала');
    }
}
