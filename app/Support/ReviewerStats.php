<?php

namespace App\Support;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * PURPOSE: Pre-aggregated per-reviewer workload and reliability stats
 * (active reviews, overdue, turnaround, yearly declines) powering the
 * reviewer assignment card, the reviewer dashboard, and analytics.
 */
class ReviewerStats
{
    /**
     * @param  Collection<int, User>  $reviewers
     * @return array<int, array{active: int, overdue: int, completed: int, avg_days: ?int, declines_year: int, avg_rating: ?float}>
     */
    public static function map(Collection $reviewers): array
    {
        $ids = $reviewers->pluck('id')->all();

        if ($ids === []) {
            return [];
        }

        $active = Review::query()
            ->whereIn('reviewer_id', $ids)
            ->whereIn('status', [ReviewStatus::Pending, ReviewStatus::InProgress])
            ->groupBy('reviewer_id')
            ->selectRaw('reviewer_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'reviewer_id');

        $overdue = Review::overdue()
            ->whereIn('reviewer_id', $ids)
            ->groupBy('reviewer_id')
            ->selectRaw('reviewer_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'reviewer_id');

        $completed = Review::query()
            ->whereIn('reviewer_id', $ids)
            ->where('status', ReviewStatus::Completed)
            ->whereNotNull('assigned_at')
            ->groupBy('reviewer_id')
            ->selectRaw('reviewer_id, COUNT(*) as total, AVG(EXTRACT(EPOCH FROM (completed_at - assigned_at))) as avg_seconds')
            ->get()
            ->keyBy('reviewer_id');

        $declines = Review::query()
            ->whereIn('reviewer_id', $ids)
            ->where('status', ReviewStatus::Declined)
            ->where('assigned_at', '>=', now()->subYear())
            ->groupBy('reviewer_id')
            ->selectRaw('reviewer_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'reviewer_id');

        $ratings = Review::query()
            ->whereIn('reviewer_id', $ids)
            ->where('status', ReviewStatus::Completed)
            ->whereNotNull('quality_rating')
            ->groupBy('reviewer_id')
            ->selectRaw('reviewer_id, AVG(quality_rating) as avg_rating')
            ->pluck('avg_rating', 'reviewer_id');

        return $reviewers
            ->mapWithKeys(fn (User $reviewer) => [
                $reviewer->id => [
                    'active' => (int) ($active[$reviewer->id] ?? 0),
                    'overdue' => (int) ($overdue[$reviewer->id] ?? 0),
                    'completed' => (int) ($completed[$reviewer->id]->total ?? 0),
                    'avg_days' => isset($completed[$reviewer->id])
                        ? (int) round(((float) $completed[$reviewer->id]->avg_seconds) / 86400)
                        : null,
                    'declines_year' => (int) ($declines[$reviewer->id] ?? 0),
                    'avg_rating' => isset($ratings[$reviewer->id])
                        ? round((float) $ratings[$reviewer->id], 1)
                        : null,
                ],
            ])
            ->all();
    }

    /**
     * Stats for a single reviewer (defaults for reviewers without history).
     *
     * @return array{active: int, overdue: int, completed: int, avg_days: ?int, declines_year: int, avg_rating: ?float}
     */
    public static function single(User $reviewer): array
    {
        return self::map(collect([$reviewer]))[$reviewer->id] ?? [
            'active' => 0,
            'overdue' => 0,
            'completed' => 0,
            'avg_days' => null,
            'declines_year' => 0,
            'avg_rating' => null,
        ];
    }
}
