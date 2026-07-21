<?php

namespace App\Notifications;

use App\Models\Article;
use App\Traits\HasNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * PURPOSE: In-app and email notification sent to the author and
 * coauthors when the editor makes a decision on their article.
 *
 * SPECIFICATION: SPEC-12/AC-3, SPEC-12/BR-2
 */
class AuthorDecisionMade extends Notification implements ShouldQueue
{
    use HasNotificationChannels;
    use Queueable;

    public function __construct(
        public Article $article
    ) {}

    public function toArray(object $notifiable): array
    {
        $verdictLabel = match ($this->article->decision) {
            'accept' => 'Принята',
            'revision' => 'Отправлена на доработку',
            'reject' => 'Отклонена',
            default => $this->article->decision,
        };

        return [
            'article_id' => $this->article->id,
            'article_title' => $this->article->title,
            'event' => 'decision.made',
            'event_description' => 'Решение редактора: '.$verdictLabel,
            'message_preview' => 'Редактор принял решение по вашей статье.',
            'author_name' => 'Система',
            'route' => 'submissions.show',
            'route_params' => ['article' => $this->article->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->article;
        $verdictLabel = match ($article->decision) {
            'accept' => 'принята',
            'revision' => 'отправлена на доработку',
            'reject' => 'отклонена',
            default => $article->decision,
        };

        $mail = (new MailMessage)
            ->subject('Решение редактора по вашей статье')
            ->greeting('Здравствуйте!')
            ->line('Редактор принял решение по вашей статье: **'.$verdictLabel.'**.')
            ->line('Название: «'.$article->title.'»')
            ->action('Перейти к статье', route('submissions.show', $article));

        if ($article->decision_comments) {
            $mail->line('Комментарий редактора:')
                ->line('«'.Str::limit($article->decision_comments, 500).'»');
        }

        return $mail->salutation('С уважением, редакция журнала');
    }
}
