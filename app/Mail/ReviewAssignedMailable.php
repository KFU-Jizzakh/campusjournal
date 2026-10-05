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
 * PURPOSE: Email notification sent to a reviewer when they are
 * assigned to review an article (single-blind or open).
 *
 * SPECIFICATION: SPEC-02/AC-3
 */
class ReviewAssignedMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Round marker line for re-review rounds, null for the first round.
     */
    public ?string $roundLine;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Review $review
    ) {
        $this->roundLine = $review->round > 1
            ? __('email.review_assigned_round', ['round' => $review->round])
            : null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Вам назначена рецензия — '.$this->review->article->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reviews.assigned',
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
