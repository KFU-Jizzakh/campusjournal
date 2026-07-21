<?php

namespace App\Policies;

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\User;

/**
 * PURPOSE: Defines row-level access control for articles,
 * gating view, edit, editorial workflow, review type, and
 * blinded PDF operations by role and article ownership.
 *
 * SPECIFICATION: SPEC-01/BR-1, SPEC-02/BR-1, SPEC-02/BR-4, SPEC-04/BR-1, SPEC-04/BR-6, SPEC-05/BR-1, SPEC-05/BR-4
 */
class ArticlePolicy
{
    public function view(User $user, Article $article): bool
    {
        return $article->submitted_by === $user->id;
    }

    public function update(User $user, Article $article): bool
    {
        return $article->submitted_by === $user->id
            && in_array($article->status, [ArticleStatus::Draft, ArticleStatus::Revision]);
    }

    public function viewEditorial(User $user, Article $article): bool
    {
        if ($article->status === ArticleStatus::Draft) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor'])) {
            return true;
        }

        return $user->hasRole('section-editor') && $article->editor_id === $user->id;
    }

    public function assignEditor(User $user, Article $article): bool
    {
        return $user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor']);
    }

    public function assignReviewer(User $user, Article $article): bool
    {
        // Admin, editor-in-chief and managing-editor can assign reviewers to any article
        if ($user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor'])) {
            return true;
        }

        // Section editor can only assign reviewers to articles assigned to them
        return $user->hasRole('section-editor') && $article->editor_id === $user->id;
    }

    public function uploadFiles(User $user, Article $article): bool
    {
        // Author can upload files if article is in draft or revision status
        if ($article->submitted_by === $user->id && in_array($article->status, [ArticleStatus::Draft, ArticleStatus::Revision])) {
            return true;
        }

        // Only the author can upload files to a draft
        if ($article->status === ArticleStatus::Draft) {
            return false;
        }

        // Admin and editors can always upload files
        if ($user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor'])) {
            return true;
        }

        // Section editor can upload only to their assigned articles
        if ($user->hasRole('section-editor') && $article->editor_id === $user->id) {
            return true;
        }

        return false;
    }

    public function decide(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    public function sendToCopyediting(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    public function sendToProduction(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    private function canManageWorkflow(User $user, Article $article): bool
    {
        if ($user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor'])) {
            return true;
        }

        return $user->hasRole('section-editor') && $article->editor_id === $user->id;
    }

    public function publish(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article)
            && $user->hasPermissionTo('publish-issue');
    }

    /**
     * PURPOSE: Authorise changing the review type of an article.
     *
     * SPECIFICATION: SPEC-05/AC-1
     */
    public function manageReviewType(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Authorise uploading or deleting an anonymised manuscript.
     *
     * SPECIFICATION: SPEC-05/AC-3
     */
    public function uploadBlindedPdf(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Determine whether a user is allowed to download the
     * article PDF (original or blinded depending on review type).
     *
     * SPECIFICATION: SPEC-05/AC-6, SPEC-05/BR-4
     */
    public function viewPdf(User $user, Article $article): bool
    {
        if ($article->submitted_by === $user->id) {
            return true;
        }

        if ($this->canManageWorkflow($user, $article)) {
            return true;
        }

        return $article->reviews()
            ->where('reviewer_id', $user->id)
            ->whereNot('status', ReviewStatus::Declined)
            ->exists();
    }

    /**
     * PURPOSE: Authorise uploading the typeset galley PDF
     * during the Production stage.
     *
     * SPECIFICATION: SPEC-13/AC-1
     */
    public function uploadGalleyPdf(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Authorise sending the galley proof to the
     * author for final approval.
     *
     * SPECIFICATION: SPEC-13/AC-1
     */
    public function sendGalleyToAuthor(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Authorise the article submitter to approve
     * the galley proof.
     *
     * SPECIFICATION: SPEC-13/AC-4
     */
    public function approveGalley(User $user, Article $article): bool
    {
        return $article->submitted_by === $user->id;
    }

    /**
     * PURPOSE: Authorise the article submitter to request
     * galley proof revisions.
     *
     * SPECIFICATION: SPEC-13/AC-5
     */
    public function requestGalleyRevision(User $user, Article $article): bool
    {
        return $article->submitted_by === $user->id;
    }

    /**
     * PURPOSE: Authorise uploading the corrected manuscript file
     * during the Copyediting stage.
     *
     * SPECIFICATION: SPEC-04/AC-4
     */
    public function uploadCopyeditedFile(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Authorise deleting the corrected manuscript file
     * during the Copyediting stage.
     *
     * SPECIFICATION: SPEC-04/AC-4a
     */
    public function deleteCopyeditedFile(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Authorise downloading the corrected manuscript file.
     * Editorial staff (workflow managers) and the article submitter
     * can download the copyedited file.
     *
     * SPECIFICATION: SPEC-04/AC-4a
     */
    public function downloadCopyeditedFile(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article)
            || $user->id === $article->submitted_by;
    }
}
