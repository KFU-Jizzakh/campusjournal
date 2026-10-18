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
 * PURPOSE: In-app and email notification for a new reply in an
 * existing discussion thread, dispatched to all participants
 * except the message author.
 */
class NewDiscussionMessage extends Notification implements ShouldQueue
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
            ->subject('Новый ответ — статья #'.$article->id)
            ->line($this->discussion->user->full_name.' ответил в обсуждении.')
            ->line('«'.Str::limit($this->discussion->message, 150).'»')
            ->action('Открыть обсуждение', route('editorial.show', $article));
    }
}
