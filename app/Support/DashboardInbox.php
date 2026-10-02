<?php

namespace App\Support;

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Discussion;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * PURPOSE: Builds the task-oriented inbox shown at the top of the
 * dashboard — objects that need an action from the user right now,
 * derived from workflow state (not from notifications) and ordered
 * by urgency (overdue first).
 *
 * SPECIFICATION: Items are per role — author: drafts to submit,
 * revisions to resubmit, galley proofs to approve; reviewer: pending
 * invitations and reviews to write; editors: the next editorial
 * action per article; everyone: unread visible discussion threads.
 */
class DashboardInbox
{
    /**
     * Per-request memoization of computed inbox collections, keyed by
     * "user id | task family". Prevents duplicate computation when the
     * navigation composer and the dashboard controller both build the
     * inbox. NOTE: static state — must be flushed between tests, and
     * per request under any long-running runtime (e.g. Octane).
     */
    private static array $cache = [];

    /**
     * PURPOSE: Tolerant permission check — false when the permission
     * tables are unseeded (e.g. auth-only pages in tests).
     */
    public static function can(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    /**
     * PURPOSE: Whether the user may act editorially. Tolerant of
     * unseeded permission tables (e.g. auth-only pages in tests).
     */
    public static function canManageSubmissions(User $user): bool
    {
        return self::can($user, 'manage-submissions');
    }

    /**
     * PURPOSE: Drop the per-request memoized inbox collections.
     */
    public static function flush(): void
    {
        self::$cache = [];
    }

    /**
     * PURPOSE: All non-draft articles the user may manage editorially —
     * everything for EiC/managing-editor, only assigned ones for a
     * plain section-editor.
     */
    public static function editorialArticles(User $user): Builder
    {
        $query = Article::submitted();

        if ($user->hasRole('section-editor') && ! $user->hasAnyRole(['editor-in-chief', 'managing-editor'])) {
            $query->where('editor_id', $user->id);
        }

        return $query;
    }

    public static function for(User $user, ?string $activeRole = null): Collection
    {
        $family = self::familyOf($activeRole) ?? 'all';

        return self::$cache[$user->id.'|'.$family] ??= self::build($user, $family);
    }

    /**
     * PURPOSE: The session-stored active working role, validated against the user's actual roles. Null
     * means "all roles" (or a stale/invalid session value).
     */
    public static function activeRoleFor(User $user): ?string
    {
        $role = session('active_role');

        if (! is_string($role) || $role === 'all') {
            return null;
        }

        return $user->hasRole($role) ? $role : null;
    }

    /**
     * PURPOSE: Task family shown while working "as" the given role.
     * Purely presentational — real permissions never narrow.
     */
    private static function familyOf(?string $activeRole): ?string
    {
        return match ($activeRole) {
            'author' => 'author',
            'reviewer' => 'reviewer',
            'section-editor', 'editor-in-chief', 'managing-editor', 'admin' => 'editorial',
            default => null,
        };
    }

    /**
     * PURPOSE: Number of actionable inbox entries for the navigation
     * badge. Counts via cheap queries and one eager-loaded editorial
     * pass — no item building for discussions.
     */
    public static function countFor(User $user, ?string $activeRole = null): int
    {
        $family = self::familyOf($activeRole) ?? 'all';
        $count = 0;

        if ($family === 'all' || $family === 'author') {
            $count += $user->submittedArticles()
                ->whereIn('status', [
                    ArticleStatus::Draft,
                    ArticleStatus::Revision,
                    ArticleStatus::AwaitingApproval,
                ])
                ->count();
        }

        if ($family === 'all' || $family === 'reviewer') {
            $count += $user->reviews()
                ->whereIn('status', [ReviewStatus::Pending, ReviewStatus::InProgress])
                ->count();
        }

        $count += Discussion::root()
            ->unresolved()
            ->visibleTo($user)
            ->unreadBy($user)
            ->count();

        if (($family === 'all' || $family === 'editorial') && self::canManageSubmissions($user)) {
            $count += self::editorialArticles($user)
                ->with('reviews')
                ->whereIn('status', [
                    ArticleStatus::Submitted,
                    ArticleStatus::InReview,
                    ArticleStatus::Accepted,
                    ArticleStatus::Copyediting,
                    ArticleStatus::Production,
                    ArticleStatus::Approved,
                ])
                ->get()
                ->filter(fn (Article $article) => self::editorialItem($user, $article) !== null)
                ->count();
        }

        return $count;
    }

    private static function build(User $user, string $family = 'all'): Collection
    {
        $items = collect();

        if ($family === 'all' || $family === 'author') {
            $items = $items->merge(self::authorItems($user));
        }

        if ($family === 'all' || $family === 'reviewer') {
            $items = $items->merge(self::reviewerItems($user));
        }

        if ($family === 'all' || $family === 'editorial') {
            $items = $items->merge(self::editorialItems($user));
        }

        return $items
            ->merge(self::discussionItems($user))
            ->sort(InboxItem::sortCompare(...))
            ->values();
    }

    /** @return array<int, InboxItem> */
    private static function authorItems(User $user): array
    {
        return $user->submittedArticles()
            ->whereIn('status', [
                ArticleStatus::Draft,
                ArticleStatus::Revision,
                ArticleStatus::AwaitingApproval,
            ])
            ->get()
            ->map(fn (Article $article) => match ($article->status) {
                ArticleStatus::Draft => new InboxItem(
                    title: $article->title,
                    task: __('dashboard.inbox.task.submit_draft'),
                    url: route('submissions.edit', $article),
                    actionLabel: __('dashboard.inbox.action.submit'),
                    badgeLabel: $article->status->label(),
                    badgeColor: $article->status->color(),
                    sortDate: $article->updated_at,
                ),
                ArticleStatus::Revision => new InboxItem(
                    title: $article->title,
                    task: __('dashboard.inbox.task.resubmit'),
                    url: route('submissions.edit', $article),
                    actionLabel: __('dashboard.inbox.action.resubmit'),
                    badgeLabel: $article->status->label(),
                    badgeColor: $article->status->color(),
                    sortDate: $article->updated_at,
                ),
                ArticleStatus::AwaitingApproval => new InboxItem(
                    title: $article->title,
                    task: __('dashboard.inbox.task.approve_galley'),
                    url: route('submissions.show', $article),
                    actionLabel: __('dashboard.inbox.action.open'),
                    badgeLabel: $article->status->label(),
                    badgeColor: $article->status->color(),
                    primaryForm: new InboxAction(
                        label: __('dashboard.inbox.action.approve'),
                        url: route('submissions.approve-galley', $article),
                    ),
                    sortDate: $article->galley_sent_at ?? $article->updated_at,
                ),
                default => null,
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<int, InboxItem> */
    private static function reviewerItems(User $user): array
    {
        return $user->reviews()
            ->with('article')
            ->whereIn('status', [ReviewStatus::Pending, ReviewStatus::InProgress])
            ->get()
            ->map(function (Review $review) {
                if ($review->status === ReviewStatus::Pending) {
                    return new InboxItem(
                        title: $review->article?->title ?? __('dashboard.inbox.review_task'),
                        task: __('dashboard.inbox.task.review_invitation'),
                        url: route('reviews.show', $review),
                        actionLabel: __('dashboard.inbox.action.open'),
                        urgency: self::responseUrgency($review),
                        deadlineLabel: self::responseDeadlineLabel($review),
                        badgeLabel: $review->status->label(),
                        badgeColor: $review->status->color(),
                        primaryForm: new InboxAction(
                            label: __('dashboard.inbox.accept'),
                            url: route('reviews.accept', $review),
                        ),
                        secondaryForm: new InboxAction(
                            label: __('dashboard.inbox.decline'),
                            url: route('reviews.decline', $review),
                            style: 'danger',
                            confirm: __('dashboard.confirm_decline'),
                        ),
                        sortDate: $review->response_due_at,
                    );
                }

                return new InboxItem(
                    title: $review->article?->title ?? __('dashboard.inbox.review_task'),
                    task: __('dashboard.inbox.task.write_review'),
                    url: route('reviews.show', $review),
                    actionLabel: __('dashboard.inbox.action.review'),
                    urgency: $review->review_due_at !== null ? $review->deadlineStatus() : 'normal',
                    deadlineLabel: $review->review_due_at !== null ? $review->deadlineLabel() : null,
                    badgeLabel: $review->status->label(),
                    badgeColor: $review->status->color(),
                    sortDate: $review->review_due_at,
                );
            })
            ->all();
    }

    /** @return array<int, InboxItem> */
    private static function editorialItems(User $user): array
    {
        if (! self::canManageSubmissions($user)) {
            return [];
        }

        return self::editorialArticles($user)
            ->with('reviews')
            ->whereIn('status', [
                ArticleStatus::Submitted,
                ArticleStatus::InReview,
                ArticleStatus::Accepted,
                ArticleStatus::Copyediting,
                ArticleStatus::Production,
                ArticleStatus::Approved,
            ])
            ->orderBy('submitted_at')
            ->get()
            ->map(fn (Article $article) => self::editorialItem($user, $article))
            ->filter()
            ->values()
            ->all();
    }

    private static function editorialItem(User $user, Article $article): ?InboxItem
    {
        $base = [
            'title' => $article->title,
            'url' => route('editorial.show', $article),
            'badgeLabel' => $article->status->label(),
            'badgeColor' => $article->status->color(),
            'sortDate' => $article->submitted_at,
        ];

        return match ($article->status) {
            ArticleStatus::Submitted => self::submissionItem($article, $base),
            ArticleStatus::InReview => self::hasCompletedReview($article)
                ? new InboxItem(
                    ...$base,
                    task: __('dashboard.inbox.task.decide'),
                    actionLabel: __('dashboard.inbox.action.decide'),
                    urgency: 'warning',
                )
                : self::reassignItem($article, $base),
            ArticleStatus::Accepted => new InboxItem(
                ...$base,
                task: __('dashboard.inbox.task.send_to_copyediting'),
                actionLabel: __('dashboard.inbox.action.send'),
            ),
            ArticleStatus::Copyediting => $article->copyedited_file_path === null
                ? new InboxItem(
                    ...$base,
                    task: __('dashboard.inbox.task.upload_copyedited'),
                    actionLabel: __('dashboard.inbox.action.upload'),
                )
                : new InboxItem(
                    ...$base,
                    task: __('dashboard.inbox.task.send_to_production'),
                    actionLabel: __('dashboard.inbox.action.send'),
                ),
            ArticleStatus::Production => $article->galley_pdf_path === null
                ? new InboxItem(
                    ...$base,
                    task: __('dashboard.inbox.task.upload_galley'),
                    actionLabel: __('dashboard.inbox.action.upload'),
                )
                : new InboxItem(
                    ...$base,
                    task: __('dashboard.inbox.task.send_galley'),
                    actionLabel: __('dashboard.inbox.action.send'),
                ),
            ArticleStatus::Approved => $user->can('publish', $article)
                ? new InboxItem(
                    ...$base,
                    task: __('dashboard.inbox.task.publish'),
                    actionLabel: __('dashboard.inbox.action.publish'),
                )
                : null,
            default => null,
        };
    }

    private static function submissionItem(Article $article, array $base): ?InboxItem
    {
        if ($article->editor_id === null) {
            return new InboxItem(
                ...$base,
                task: __('dashboard.inbox.task.assign_editor'),
                actionLabel: __('dashboard.inbox.action.assign'),
                urgency: 'warning',
            );
        }

        if ($article->needsBlindedPdf()) {
            return new InboxItem(
                ...$base,
                task: __('dashboard.inbox.task.upload_blinded'),
                actionLabel: __('dashboard.inbox.action.upload'),
            );
        }

        if (! self::hasActiveReviewers($article)) {
            return new InboxItem(
                ...$base,
                task: __('dashboard.inbox.task.assign_reviewer'),
                actionLabel: __('dashboard.inbox.action.assign'),
            );
        }

        return self::reassignItem($article, $base);
    }

    private static function reassignItem(Article $article, array $base): ?InboxItem
    {
        $stuck = $article->reviews->first(
            fn (Review $review) => $review->status === ReviewStatus::Declined
                || ($review->status === ReviewStatus::Pending && $review->isResponseOverdue())
                || ($review->status === ReviewStatus::InProgress && $review->isOverdue())
        );

        if (! $stuck) {
            return null;
        }

        $overdue = $stuck->status !== ReviewStatus::Declined;

        if ($overdue) {
            $base['sortDate'] = $stuck->review_due_at ?? $stuck->response_due_at;
        }

        return new InboxItem(
            ...$base,
            task: $stuck->status === ReviewStatus::Declined
                ? __('dashboard.inbox.task.reviewer_declined')
                : __('dashboard.inbox.task.reviewer_overdue'),
            actionLabel: __('dashboard.inbox.action.reassign'),
            urgency: $overdue ? 'overdue' : 'warning',
            deadlineLabel: match ($stuck->status) {
                ReviewStatus::InProgress => $stuck->deadlineLabel(),
                ReviewStatus::Pending => self::responseDeadlineLabel($stuck),
                default => null,
            },
        );
    }

    private static function hasActiveReviewers(Article $article): bool
    {
        return $article->reviews->contains(
            fn (Review $review) => $review->status === ReviewStatus::Pending
                || $review->status === ReviewStatus::InProgress
        );
    }

    /**
     * PURPOSE: Whether the article has at least one completed review.
     * Uses the eager-loaded `reviews` collection (callers run
     * `->with('reviews')`) — avoids the per-article query that
     * `Article::canBeDecided()` would trigger inside lists.
     */
    public static function hasCompletedReview(Article $article): bool
    {
        return $article->reviews->contains(
            fn (Review $review) => $review->status === ReviewStatus::Completed
        );
    }

    /** @return array<int, InboxItem> */
    private static function discussionItems(User $user): array
    {
        return Discussion::root()
            ->unresolved()
            ->visibleTo($user)
            ->unreadBy($user)
            ->with('article', 'review')
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->map(function (Discussion $discussion) use ($user) {
                $url = self::discussionUrl($user, $discussion);

                if ($url === null) {
                    return null;
                }

                return new InboxItem(
                    title: $discussion->article?->title ?? '',
                    task: __('dashboard.inbox.task.discussion').' · '.Str::limit(strip_tags($discussion->message), 80),
                    url: $url,
                    actionLabel: __('dashboard.inbox.action.reply'),
                    badgeLabel: __('dashboard.inbox.discussion_badge'),
                    badgeColor: 'info',
                    sortDate: $discussion->updated_at,
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function discussionUrl(User $user, Discussion $discussion): ?string
    {
        $article = $discussion->article;

        if ($user->can('viewEditorial', $article)) {
            return route('editorial.show', $article).'#discussions';
        }

        if ($article->submitted_by === $user->id) {
            return route('submissions.show', $article).'#discussions';
        }

        if ($discussion->review && $discussion->review->reviewer_id === $user->id) {
            return route('reviews.show', $discussion->review).'#discussions';
        }

        return null;
    }

    public static function responseUrgency(Review $review): string
    {
        if ($review->isResponseOverdue()) {
            return 'overdue';
        }

        $days = $review->daysUntilResponseDue();

        return match (true) {
            $days === null => 'normal',
            $days <= 3 => 'urgent',
            $days <= 7 => 'warning',
            default => 'normal',
        };
    }

    public static function responseDeadlineLabel(Review $review): ?string
    {
        if ($review->response_due_at === null) {
            return null;
        }

        if ($review->isResponseOverdue()) {
            return __('dashboard.inbox.response_overdue', [
                'date' => $review->response_due_at->format('d.m.Y'),
            ]);
        }

        return __('dashboard.inbox.response_due', [
            'date' => $review->response_due_at->format('d.m.Y'),
        ]);
    }
}
