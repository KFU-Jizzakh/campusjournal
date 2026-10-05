<?php

namespace App\Mail;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * PURPOSE: Email notification sent to a reviewer assigned to a
 * double-blind article, including anonymisation notice.
 *
 * SPECIFICATION: SPEC-05/AC-7
 */
class ReviewAssignedDoubleBlindMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Round marker line for re-review rounds, null for the first round.
     */
    public ?string $roundLine;

    public function __construct(
        public Review $review
    ) {
        $this->roundLine = $review->round > 1
            ? __('email.review_assigned_round', ['round' => $review->round])
            : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Вам назначена рецензия (двойное слепое) — '.$this->review->article->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reviews.assigned-double-blind',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
