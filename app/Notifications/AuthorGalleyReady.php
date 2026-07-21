<?php

namespace App\Notifications;

use App\Models\Article;
use App\Traits\HasNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PURPOSE: In-app and email notification sent to the author
 * when the editor sends a typeset galley proof for final
 * review and approval.
 *
 * SPECIFICATION: SPEC-13/AC-2
 */
class AuthorGalleyReady extends Notification implements ShouldQueue
{
    use HasNotificationChannels;
    use Queueable;

    public function __construct(
        public Article $article
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'article_id' => $this->article->id,
            'article_title' => $this->article->title,
            'event' => 'galley.sent_to_author',
            'event_description' => 'Гранки готовы к проверке',
            'message_preview' => 'Гранки вашей статьи готовы к проверке.',
            'author_name' => 'Система',
            'route' => 'submissions.show',
            'route_params' => ['article' => $this->article->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->article;

        return (new MailMessage)
            ->subject('Гранки готовы к проверке')
            ->greeting('Здравствуйте!')
            ->line('Свёрстанная версия вашей статьи готова к проверке.')
            ->line('Название: «'.$article->title.'»')
            ->line('Пожалуйста, проверьте гранки и утвердите публикацию или запросите правки.')
            ->action('Перейти к статье', route('submissions.show', $article))
            ->salutation('С уважением, редакция журнала');
    }
}
