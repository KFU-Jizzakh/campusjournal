<?php

namespace App\Policies;

use App\Enums\DiscussionScope;
use App\Models\Article;
use App\Models\Discussion;
use App\Models\Review;
use App\Models\User;

/**
 * PURPOSE: Defines visibility and action permissions for discussion
 * threads based on scope (article/editorial), review binding,
 * and user role.
 *
 * SPECIFICATION: SPEC-06/BR-2, SPEC-06/BR-3, SPEC-06/BR-8
 */
class DiscussionPolicy
{
    public function view(User $user, Discussion $discussion): bool
    {
        $article = $discussion->article;

        if ($user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor'])) {
            return true;
        }

        if ($user->hasRole('section-editor') && $article->editor_id === $user->id) {
            return true;
        }

        if ($discussion->scope === DiscussionScope::Article
            && ! $discussion->review_id
            && ($article->submitted_by === $user->id
                || $article->authors()->where('user_id', $user->id)->exists())) {
            return true;
        }

        if ($discussion->review_id && $discussion->review) {
            return $discussion->review->reviewer_id === $user->id;
        }

        return false;
    }

    /**
     * PURPOSE: Who may start a discussion thread (or reply in one)
     * on an article: leadership and the assigned section editor in
     * any scope, the submitter in "article" scope, and a reviewer —
     * only inside the editorial thread bound to their own ACTIVE
     * review (declined/cancelled/finished reviewers cannot write).
     *
     * SPECIFICATION: SPEC-06/AC-1, SPEC-06/AC-3, SPEC-06/BR-4
     */
    public function create(User $user, Article $article, DiscussionScope $scope, ?Review $review = null): bool
    {
        if ($user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor'])) {
            return true;
        }

        if ($user->hasRole('section-editor') && $article->editor_id === $user->id) {
            return true;
        }

        if (
            $review !== null
            && $scope === DiscussionScope::Editorial
            && $review->article_id === $article->id
            && $review->reviewer_id === $user->id
            && $review->status->isActive()
        ) {
            return true;
        }

        if ($scope === DiscussionScope::Article
            && $article->submitted_by === $user->id) {
            return true;
        }

        return false;
    }

    public function resolve(User $user, Discussion $discussion): bool
    {
        if ($user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor'])) {
            return true;
        }

        return $user->hasRole('section-editor')
            && $discussion->article->editor_id === $user->id;
    }
}
