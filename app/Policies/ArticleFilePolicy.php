<?php

namespace App\Policies;

use App\Enums\ArticleFileVisibility;
use App\Enums\ArticleStatus;
use App\Models\ArticleFile;
use App\Models\User;

/**
 * PURPOSE: Defines access control for supplementary article files,
 * granting visibility based on file access level, article status,
 * and user role (author, editor, reviewer).
 *
 * SPECIFICATION: SPEC-07/BR-3, SPEC-07/BR-4
 */
class ArticleFilePolicy
{
    public function view(?User $user, ArticleFile $file): bool
    {
        // Deny access if the article no longer exists or is trashed
        if (! $file->article || $file->article->trashed()) {
            return false;
        }

        // Public files are visible to everyone if article is published
        if ($file->visibility === ArticleFileVisibility::Public && $file->article->status === ArticleStatus::Published) {
            return true;
        }

        // For non-public files, user must be authenticated
        if ($user === null) {
            return false;
        }

        // Author can see their own files
        if ($file->article->submitted_by === $user->id) {
            return true;
        }

        // Admin and editors can see all files
        if ($user->hasAnyRole(['admin', 'editor-in-chief', 'managing-editor'])) {
            return true;
        }

        // Section editor can see files for their assigned articles
        if ($user->hasRole('section-editor') && $file->article->editor_id === $user->id) {
            return true;
        }

        // Reviewers can see public and reviewers_only files
        if ($user->hasRole('reviewer')) {
            $isReviewer = $file->article->reviews()
                ->where('reviewer_id', $user->id)
                ->exists();

            if ($isReviewer && in_array($file->visibility, [
                ArticleFileVisibility::Public,
                ArticleFileVisibility::ReviewersOnly,
            ])) {
                return true;
            }
        }

        return false;
    }

    public function download(?User $user, ArticleFile $file): bool
    {
        return $this->view($user, $file);
    }

    public function delete(User $user, ArticleFile $file): bool
    {
        // Deny if the article no longer exists or is trashed
        if (! $file->article || $file->article->trashed()) {
            return false;
        }

        // Author can delete their own files if article is in draft or revision
        if ($file->article->submitted_by === $user->id
            && in_array($file->article->status, [ArticleStatus::Draft, ArticleStatus::Revision])) {
            return true;
        }

        // Admin can delete any file
        if ($user->hasRole('admin')) {
            return true;
        }

        // Editors can delete files from their assigned articles
        if ($user->hasAnyRole(['editor-in-chief', 'managing-editor'])) {
            return true;
        }

        if ($user->hasRole('section-editor') && $file->article->editor_id === $user->id) {
            return true;
        }

        return false;
    }
}
