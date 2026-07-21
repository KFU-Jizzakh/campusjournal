<?php

namespace App\Mail;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * PURPOSE: Email reminder sent to reviewers with overdue
 * response or approaching review deadlines.
 *
 * SPECIFICATION: SPEC-02/AC-3, SPEC-03/AC-2
 */
class ReviewReminderMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Review $review,
        public bool $isResponseReminder = false
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isResponseReminder
            ? 'Напоминание: необходимо принять или отклонить заявку на рецензирование'
            : 'Напоминание: приближается дедлайн рецензии — '.$this->review->article->title;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reviews.reminder',
            with: [
                'isResponseReminder' => $this->isResponseReminder,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
