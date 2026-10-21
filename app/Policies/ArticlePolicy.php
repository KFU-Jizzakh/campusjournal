<?php

namespace App\Policies;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;

/**
 * PURPOSE: Defines row-level access control for articles,
 * gating view, edit, editorial workflow, review type, and
 * blinded PDF operations by role and article ownership.
 */
class ArticlePolicy
{
    public function view(User $user, Article $article): bool
    {
        if ($article->submitted_by === $user->id) {
            return true;
        }

        if ($article->isDraft()) {
            return false;
        }

        return $article->authors()->where('user_id', $user->id)->exists();
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
        if ($user->hasRole('admin')) {
            return true;
        }

        // The article's authors must never edit their own submission.
        if ($article->isAuthoredBy($user)) {
            return false;
        }

        return $user->hasAnyRole(['editor-in-chief', 'managing-editor']);
    }

    public function assignReviewer(User $user, Article $article): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // The article's authors must never review their own submission.
        if ($article->isAuthoredBy($user)) {
            return false;
        }

        // Editor-in-chief and managing-editor can assign reviewers to any article
        if ($user->hasAnyRole(['editor-in-chief', 'managing-editor'])) {
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
        if ($user->hasRole('admin')) {
            return true;
        }

        // Editorial decisions on one's own manuscript are a conflict of
        // interest — authors and coauthors keep read-only editorial access
        // via viewEditorial but can take no workflow actions on it.
        if ($article->isAuthoredBy($user)) {
            return false;
        }

        if ($user->hasAnyRole(['editor-in-chief', 'managing-editor'])) {
            return true;
        }

        return $user->hasRole('section-editor') && $article->editor_id === $user->id;
    }

    public function publish(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article)
            && $user->hasPermissionTo('publish-issue');
    }

    public function manageReviewType(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    public function uploadBlindedPdf(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Determine whether a user is allowed to download the
     * article PDF (original) of a non-published manuscript: the
     * submitter, credited coauthors, editorial staff, the assigned
     * section editor, or a reviewer with an active assignment
     * (declined and cancelled reviewers keep no access). The single
     * gate behind ArticleController::pdf().
     */
    public function viewPdf(User $user, Article $article): bool
    {
        if ($article->submitted_by === $user->id) {
            return true;
        }

        if (! $article->isDraft() && $article->authors()->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($user->hasAnyRole(['editor-in-chief', 'managing-editor', 'admin'])) {
            return true;
        }

        if ($user->hasRole('section-editor') && $article->editor_id === $user->id) {
            return true;
        }

        return $article->reviews()
            ->where('reviewer_id', $user->id)
            ->active()
            ->exists();
    }

    /**
     * PURPOSE: Authorise downloading the anonymised manuscript for
     * double-blind review — editorial leadership, the assigned section
     * editor, and active reviewers (declined and cancelled keep no
     * access). Authors and coauthors never see the blinded version.
     */
    public function viewBlindedPdf(User $user, Article $article): bool
    {
        if ($user->hasAnyRole(['editor-in-chief', 'managing-editor', 'admin'])) {
            return true;
        }

        if ($user->hasRole('section-editor') && $article->editor_id === $user->id) {
            return true;
        }

        return $article->reviews()
            ->where('reviewer_id', $user->id)
            ->active()
            ->exists();
    }

    /**
     * PURPOSE: Authorise downloading the typeset galley proof —
     * editorial leadership, the assigned section editor, and the
     * submitter.
     */
    public function viewGalleyPdf(User $user, Article $article): bool
    {
        if ($article->submitted_by === $user->id) {
            return true;
        }

        if ($user->hasAnyRole(['editor-in-chief', 'managing-editor', 'admin'])) {
            return true;
        }

        return $user->hasRole('section-editor') && $article->editor_id === $user->id;
    }

    /**
     * PURPOSE: Authorise uploading the typeset galley PDF
     * during the Production stage.
     */
    public function uploadGalleyPdf(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Authorise sending the galley proof to the
     * author for final approval.
     */
    public function sendGalleyToAuthor(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Authorise the article submitter to approve
     * the galley proof.
     */
    public function approveGalley(User $user, Article $article): bool
    {
        return $article->submitted_by === $user->id;
    }

    /**
     * PURPOSE: Authorise the article submitter to request
     * galley proof revisions.
     */
    public function requestGalleyRevision(User $user, Article $article): bool
    {
        return $article->submitted_by === $user->id;
    }

    /**
     * PURPOSE: Authorise uploading the corrected manuscript file
     * during the Copyediting stage.
     */
    public function uploadCopyeditedFile(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Authorise deleting the corrected manuscript file
     * during the Copyediting stage.
     */
    public function deleteCopyeditedFile(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }

    /**
     * PURPOSE: Authorise downloading the corrected manuscript file.
     * Editorial staff (workflow managers) and the article submitter
     * can download the copyedited file.
     */
    public function downloadCopyeditedFile(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article)
            || $user->id === $article->submitted_by;
    }

    /**
     * PURPOSE: Authorise retracting a published article — leadership
     * only, and subject to the conflict-of-interest boundary: an EiC
     * or managing editor who authored the manuscript cannot retract
     * it themselves (the admin override in canManageWorkflow stands).
     */
    public function retract(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article)
            && $user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor']);
    }

    /**
     * PURPOSE: Authorise withdrawing an article before publication.
     * The article submitter and workflow managers can withdraw.
     */
    public function withdraw(User $user, Article $article): bool
    {
        if ($article->submitted_by === $user->id) {
            return true;
        }

        return $this->canManageWorkflow($user, $article);
    }

    public function manageCorrections(User $user, Article $article): bool
    {
        return $this->canManageWorkflow($user, $article);
    }
}
