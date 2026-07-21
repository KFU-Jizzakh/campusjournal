<?php

namespace App\Policies;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;

/**
 * PURPOSE: Defines row-level access control for reviews, gating
 * view, update, accept, and decline actions by reviewer assignment
 * and role; admin has broad override respecting the state machine.
 *
 * SPECIFICATION: SPEC-03/BR-3
 */
class ReviewPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor', 'section-editor']);
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Admin role has broad override: admin can act on any review
     * regardless of reviewer assignment, but must still respect
     * the review status state machine.
     */
    public function view(User $user, Review $review): bool
    {
        return $user->hasRole('admin') || $review->reviewer_id === $user->id;
    }

    /**
     * Admin may update any review in progress (editorial override),
     * but the review must still be InProgress.
     */
    public function update(User $user, Review $review): bool
    {
        if ($review->status !== ReviewStatus::InProgress) {
            return false;
        }

        return $user->hasRole('admin') || $review->reviewer_id === $user->id;
    }

    /**
     * Admin may accept any pending review on behalf of a reviewer,
     * but the review must still be Pending.
     */
    public function accept(User $user, Review $review): bool
    {
        if ($review->status !== ReviewStatus::Pending) {
            return false;
        }

        return $user->hasRole('admin') || $review->reviewer_id === $user->id;
    }

    /**
     * Admin may decline any pending review on behalf of a reviewer,
     * but the review must still be Pending.
     */
    public function decline(User $user, Review $review): bool
    {
        if ($review->status !== ReviewStatus::Pending) {
            return false;
        }

        return $user->hasRole('admin') || $review->reviewer_id === $user->id;
    }
}
