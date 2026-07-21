<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Exceptions\InvalidTransitionException;
use App\Notifications\AuthorReviewCompleted;
use App\Notifications\ReviewCompleted;
use App\Notifications\ReviewerAccepted;
use App\Notifications\ReviewerDeclined;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * PURPOSE: Peer review entity managing the reviewer lifecycle
 * from invitation through acceptance, completion, and deadlines.
 *
 * SPECIFICATION: SPEC-02/AC-3, SPEC-03/AC-2, SPEC-03/AC-3, SPEC-03/AC-4, SPEC-03/AC-5
 */
#[Fillable(['article_id', 'reviewer_id', 'assigned_by', 'recommendation', 'comments_for_editor', 'comments_for_author', 'status', 'assigned_at', 'completed_at', 'response_due_at', 'review_due_at', 'reminded_at'])]
class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'response_due_at' => 'datetime',
            'review_due_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class, 'review_id');
    }

    /**
     * Scope for reviews that are overdue for submission
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('status', [ReviewStatus::Pending, ReviewStatus::InProgress])
            ->whereNotNull('review_due_at')
            ->where('review_due_at', '<', now());
    }

    /**
     * Scope for reviews where response is overdue
     */
    public function scopeResponseOverdue($query)
    {
        return $query->where('status', ReviewStatus::Pending)
            ->whereNotNull('response_due_at')
            ->where('response_due_at', '<', now());
    }

    /**
     * Check if review is overdue
     */
    public function isOverdue(): bool
    {
        if (! $this->review_due_at || ! in_array($this->status, [ReviewStatus::Pending, ReviewStatus::InProgress])) {
            return false;
        }

        return $this->review_due_at->isPast();
    }

    /**
     * Check if response is overdue
     */
    public function isResponseOverdue(): bool
    {
        if (! $this->response_due_at || $this->status !== ReviewStatus::Pending) {
            return false;
        }

        return $this->response_due_at->isPast();
    }

    /**
     * Get days overdue (or days until due)
     */
    public function daysOverdue(): ?int
    {
        if (! $this->review_due_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->review_due_at, false) * -1);
    }

    /**
     * Get days until response is due (or days overdue)
     */
    public function daysUntilResponseDue(): ?int
    {
        if (! $this->response_due_at) {
            return null;
        }

        return now()->diffInDays($this->response_due_at, false);
    }

    /**
     * Get days until review is due (or days overdue)
     */
    public function daysUntilReviewDue(): ?int
    {
        if (! $this->review_due_at) {
            return null;
        }

        return now()->diffInDays($this->review_due_at, false);
    }

    /**
     * Get deadline status for UI display
     */
    public function deadlineStatus(): string
    {
        if ($this->status === ReviewStatus::Completed) {
            return 'completed';
        }

        if ($this->status === ReviewStatus::Declined) {
            return 'declined';
        }

        if ($this->isOverdue()) {
            return 'overdue';
        }

        $daysUntil = $this->daysUntilReviewDue();

        if ($daysUntil === null) {
            return 'unknown';
        }

        if ($daysUntil <= 3) {
            return 'urgent';
        }

        if ($daysUntil <= 7) {
            return 'warning';
        }

        return 'normal';
    }

    public function transitionTo(ReviewStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidTransitionException($this->status->label(), $target->label());
        }

        $this->status = $target;
    }

    /**
     * Accept the review invitation and transition to InProgress.
     * Notifies the assigning editor that the reviewer accepted.
     *
     * SPECIFICATION: SPEC-03/AC-2, SPEC-03/BR-1
     */
    public function accept(): void
    {
        DB::transaction(function () {
            $this->transitionTo(ReviewStatus::InProgress);
            $this->save();

            OutboxEvent::log('review.accepted', $this, [
                'article_id' => $this->article_id,
            ]);

            if ($this->assignedBy) {
                $this->assignedBy->notify(new ReviewerAccepted($this));
            }
        });
    }

    /**
     * Decline the review invitation.
     * Notifies the assigning editor that the reviewer declined.
     *
     * SPECIFICATION: SPEC-03/AC-3, SPEC-03/BR-2
     */
    public function decline(): void
    {
        DB::transaction(function () {
            $this->transitionTo(ReviewStatus::Declined);
            $this->save();

            OutboxEvent::log('review.declined', $this, [
                'article_id' => $this->article_id,
            ]);

            if ($this->assignedBy) {
                $this->assignedBy->notify(new ReviewerDeclined($this));
            }
        });
    }

    /**
     * Complete the review with recommendation and comments
     * for both the editor and the author.
     * Notifies the assigning editor that the review is complete.
     *
     * SPECIFICATION: SPEC-03/AC-4, SPEC-03/BR-1
     */
    public function complete(string $recommendation, string $commentsForEditor, string $commentsForAuthor): void
    {
        DB::transaction(function () use ($recommendation, $commentsForEditor, $commentsForAuthor) {
            $this->transitionTo(ReviewStatus::Completed);

            $this->update([
                'recommendation' => $recommendation,
                'comments_for_editor' => $commentsForEditor,
                'comments_for_author' => $commentsForAuthor,
                'completed_at' => now(),
            ]);

            OutboxEvent::log('review.completed', $this, [
                'article_id' => $this->article_id,
                'recommendation' => $recommendation,
            ]);

            $this->article->notifiableUsers()->each(
                fn (User $user) => $user->notify(new AuthorReviewCompleted($this))
            );

            if ($this->assignedBy) {
                $this->assignedBy->notify(new ReviewCompleted($this));
            }
        });
    }

    public function isPending(): bool
    {
        return $this->status === ReviewStatus::Pending;
    }

    public function isInProgress(): bool
    {
        return $this->status === ReviewStatus::InProgress;
    }

    public function isCompleted(): bool
    {
        return $this->status === ReviewStatus::Completed;
    }

    public function isDeclined(): bool
    {
        return $this->status === ReviewStatus::Declined;
    }

    public function recommendationLabel(): ?string
    {
        return match ($this->recommendation) {
            'accept' => __('article.recommendation_accept'),
            'minor_revision' => 'Незначит. доработка',
            'major_revision' => 'Значит. доработка',
            'reject' => __('article.recommendation_reject'),
            default => null,
        };
    }

    public function recommendationBadgeClass(): string
    {
        return match ($this->recommendation) {
            'accept' => 'bg-green-50 text-green-700',
            'minor_revision' => 'bg-yellow-50 text-yellow-700',
            'major_revision' => 'bg-orange-50 text-orange-700',
            'reject' => 'bg-red-50 text-red-700',
            default => '',
        };
    }

    public function deadlineCssClass(): string
    {
        return match ($this->deadlineStatus()) {
            'overdue' => 'red',
            'urgent' => 'orange',
            'warning' => 'yellow',
            default => 'green',
        };
    }

    public function deadlineLabel(): string
    {
        if ($this->isOverdue()) {
            $days = $this->daysOverdue();

            return 'Просрочено: дедлайн был '.$this->review_due_at->format('d.m.Y')
                .' ('.$days.' '.trans_choice('день|дня|дней', $days).' назад)';
        }

        $days = $this->daysUntilReviewDue();

        return __('dashboard.deadline').' '.$this->review_due_at->format('d.m.Y')
            .' (осталось '.$days.' '.trans_choice('день|дня|дней', $days).')';
    }
}
