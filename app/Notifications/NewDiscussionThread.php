<?php

namespace App\Notifications;

use App\Models\Discussion;
use App\Traits\HasDiscussionNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * PURPOSE: In-app and email notification for a new discussion thread,
 * dispatched when a root message is created.
 *
 * SPECIFICATION: SPEC-06/AC-7, SPEC-06/AC-10, SPEC-06/BR-10
 */
class NewDiscussionThread extends Notification implements ShouldQueue
{
    use HasDiscussionNotificationChannels;
    use Queueable;

    public function __construct(
        public Discussion $discussion
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'discussion_id' => $this->discussion->id,
            'article_id' => $this->discussion->article_id,
            'article_title' => $this->discussion->article->title,
            'message_preview' => Str::limit($this->discussion->message, 100),
            'author_name' => $this->discussion->user->full_name,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->discussion->article;

        return (new MailMessage)
            ->subject('Новое обсуждение — статья #'.$article->id)
            ->line($this->discussion->user->full_name.' начал новое обсуждение.')
            ->line('«'.Str::limit($this->discussion->message, 150).'»')
            ->action('Открыть статью', route('editorial.show', $article));
    }
}
