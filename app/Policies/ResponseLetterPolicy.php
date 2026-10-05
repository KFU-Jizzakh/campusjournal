<?php

namespace App\Policies;

use App\Models\ResponseLetter;
use App\Models\User;

/**
 * PURPOSE: Gates response-letter file downloads — editors see all
 * letters, the author (and credited coauthors) see their own, and a
 * reviewer sees the letter of the round they are assigned to.
 *
 * SPECIFICATION: SPEC-25/BR-4
 */
class ResponseLetterPolicy
{
    public function view(User $user, ResponseLetter $responseLetter): bool
    {
        $article = $responseLetter->article;

        if ($user->can('viewEditorial', $article)) {
            return true;
        }

        if ($user->can('view', $article)) {
            return true;
        }

        return $article->reviews()
            ->where('reviewer_id', $user->id)
            ->where('round', $responseLetter->round)
            ->exists();
    }
}
