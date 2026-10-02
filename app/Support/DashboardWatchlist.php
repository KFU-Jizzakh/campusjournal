<?php

namespace App\Support;

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * PURPOSE: Builds the dashboard "На контроле" (watchlist) and
 * "Дедлайны рецензий" sections for editors — articles that are NOT
 * actionable by the user but wait on someone else (reviewers, authors),
 * plus the review deadlines hitting within the next week.
 */
class DashboardWatchlist
{
    private const STALE_IN_REVIEW_DAYS = 30;

    private const STALE_AWAITING_APPROVAL_DAYS = 7;

    private const STALE_REVISION_DAYS = 14;

    /**
     * Articles in the editor's scope waiting on others.
     *
     * @return Collection<int, InboxItem>
     */
    public static function for(User $user, ?string $activeRole = null): Collection
    {
        if ($activeRole === 'author' || $activeRole === 'reviewer') {
            return collect();
        }

        if (! DashboardInbox::canManageSubmissions($user)) {
            return collect();
        }

        return DashboardInbox::editorialArticles($user)
            ->with('reviews')
            ->whereIn('status', [
                ArticleStatus::InReview,
                ArticleStatus::AwaitingApproval,
                ArticleStatus::Revision,
            ])
            ->orderBy('updated_at')
            ->get()
            ->map(fn (Article $article) => self::watchItem($article))
            ->filter()
            ->sort(InboxItem::sortCompare(...))
            ->values();
    }

    /**
     * Active reviews in the editor's scope due within a week or overdue,
     * earliest first.
     *
     * @return Collection<int, InboxItem>
     */
    public static function deadlines(User $user, ?string $activeRole = null): Collection
    {
        if ($activeRole === 'author' || $activeRole === 'reviewer') {
            return collect();
        }

        if (! DashboardInbox::canManageSubmissions($user)) {
            return collect();
        }

        $articleIds = DashboardInbox::editorialArticles($user)->pluck('id');

        if ($articleIds->isEmpty()) {
            return collect();
        }

        return Review::with('article', 'reviewer.profile')
            ->whereIn('article_id', $articleIds)
            ->whereIn('status', [ReviewStatus::Pending, ReviewStatus::InProgress])
            ->where(fn ($query) => $query
                ->where(fn ($inner) => $inner
                    ->whereNotNull('review_due_at')
                    ->where('review_due_at', '<=', now()->addWeek()))
                ->orWhere(fn ($inner) => $inner
                    ->where('status', ReviewStatus::Pending)
                    ->whereNotNull('response_due_at')
                    ->where('response_due_at', '<=', now()->addWeek())))
            ->orderBy('review_due_at')
            ->orderBy('response_due_at')
            ->get()
            ->map(fn (Review $review) => new InboxItem(
                title: $review->article?->title ?? '',
                task: __('dashboard.deadlines.review_by', [
                    'reviewer' => $review->reviewer?->full_name ?? '—',
                ]),
                url: route('editorial.show', $review->article_id),
                actionLabel: __('dashboard.inbox.action.open'),
                urgency: $review->status === ReviewStatus::Pending
                    ? DashboardInbox::responseUrgency($review)
                    : self::dueUrgency($review),
                deadlineLabel: $review->status === ReviewStatus::Pending
                    ? DashboardInbox::responseDeadlineLabel($review)
                    : $review->deadlineLabel(),
                badgeLabel: $review->status->label(),
                badgeColor: $review->status->color(),
                sortDate: $review->status === ReviewStatus::Pending
                    ? $review->response_due_at
                    : $review->review_due_at,
            ));
    }

    private static function watchItem(Article $article): ?InboxItem
    {
        $base = [
            'title' => $article->title,
            'url' => route('editorial.show', $article),
            'actionLabel' => __('dashboard.inbox.action.open'),
            'badgeLabel' => $article->status->label(),
            'badgeColor' => $article->status->color(),
        ];

        return match ($article->status) {
            ArticleStatus::InReview => self::inReviewItem($article, $base),
            ArticleStatus::AwaitingApproval => new InboxItem(
                ...$base,
                task: __('dashboard.watch.task.awaiting_approval'),
                urgency: ($article->galley_sent_at ?? $article->updated_at)->diffInDays(now()) >= self::STALE_AWAITING_APPROVAL_DAYS ? 'warning' : 'normal',
                deadlineLabel: $article->galley_sent_at
                    ? __('dashboard.watch.with_author_since', ['date' => $article->galley_sent_at->format('d.m.Y')])
                    : null,
                sortDate: $article->galley_sent_at ?? $article->updated_at,
            ),
            ArticleStatus::Revision => new InboxItem(
                ...$base,
                task: __('dashboard.watch.task.revision'),
                urgency: ($article->decided_at ?? $article->updated_at)->diffInDays(now()) >= self::STALE_REVISION_DAYS ? 'warning' : 'normal',
                deadlineLabel: $article->decided_at
                    ? __('dashboard.watch.with_author_since', ['date' => $article->decided_at->format('d.m.Y')])
                    : null,
                sortDate: $article->decided_at ?? $article->updated_at,
            ),
            default => null,
        };
    }

    private static function inReviewItem(Article $article, array $base): ?InboxItem
    {
        if (DashboardInbox::hasCompletedReview($article)) {
            return null; // actionable — already surfaced by the inbox
        }

        $nearest = $article->reviews
            ->filter(fn (Review $review) => in_array($review->status, [ReviewStatus::Pending, ReviewStatus::InProgress], true)
                && $review->review_due_at !== null)
            ->sortBy('review_due_at')
            ->first();

        $urgency = $nearest !== null && $nearest->deadlineStatus() !== 'unknown'
            ? $nearest->deadlineStatus()
            : 'normal';

        if ($urgency === 'normal' && $article->daysInStatus() >= self::STALE_IN_REVIEW_DAYS) {
            $urgency = 'warning';
        }

        $base['sortDate'] = $nearest?->review_due_at ?? $article->updated_at;

        return new InboxItem(
            ...$base,
            task: __('dashboard.watch.task.in_review'),
            urgency: $urgency,
            deadlineLabel: $nearest?->deadlineLabel(),
        );
    }

    private static function dueUrgency(Review $review): string
    {
        return match ($review->deadlineStatus()) {
            'overdue', 'urgent', 'warning' => $review->deadlineStatus(),
            default => 'normal',
        };
    }
}
