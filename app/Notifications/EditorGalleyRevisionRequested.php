<?php

namespace App\Notifications;

use App\Models\Article;
use App\Traits\HasEditorNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * PURPOSE: In-app and email notification sent to the editor
 * when the author requests revisions to the galley proof.
 *
 * SPECIFICATION: SPEC-13/AC-5
 */
class EditorGalleyRevisionRequested extends Notification implements ShouldQueue
{
    use HasEditorNotificationChannels;
    use Queueable;

    public function __construct(
        public Article $article,
        public string $comment
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'article_id' => $this->article->id,
            'article_title' => $this->article->title,
            'event' => 'galley.revision_requested',
            'event_description' => 'Автор запросил правки гранок',
            'message_preview' => 'Автор запросил правки гранок: '.Str::limit($this->comment, 100),
            'author_name' => 'Система',
            'route' => 'editorial.show',
            'route_params' => ['article' => $this->article->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->article;

        $mail = (new MailMessage)
            ->subject('Автор запросил правки гранок')
            ->greeting('Здравствуйте!')
            ->line('Автор запросил правки свёрстанной версии статьи.')
            ->line('Название: «'.$article->title.'»')
            ->line('Комментарий автора:')
            ->line('«'.Str::limit($this->comment, 500).'»')
            ->action('Перейти к статье', route('editorial.show', $article));

        return $mail->salutation('С уважением, редакция журнала');
    }
}
