<?php

namespace App\Support;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * PURPOSE: Matches candidate reviewers against an article's keywords by
 * intersecting them with each reviewer's profile interests, so editors
 * see topic affinity right in the assignment options.
 */
class ReviewerMatcher
{
    /**
     * @param  Collection<int, User>  $reviewers
     * @return array<int, int> reviewer user_id => number of matched keywords (only > 0)
     */
    public static function match(Article $article, Collection $reviewers): array
    {
        $keywords = collect($article->keywords ?? [])
            ->map(fn ($keyword) => self::normalize($keyword))
            ->filter(fn (string $keyword) => $keyword !== '')
            ->unique()
            ->values();

        if ($keywords->isEmpty()) {
            return [];
        }

        return $reviewers
            ->mapWithKeys(function (User $reviewer) use ($keywords) {
                $interests = collect($reviewer->profile?->interests ?? [])
                    ->map(fn (string $tag) => self::normalize($tag))
                    ->filter(fn (string $tag) => $tag !== '')
                    ->unique();

                return [$reviewer->id => $interests->intersect($keywords)->count()];
            })
            ->filter(fn (int $count) => $count > 0)
            ->all();
    }

    private static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
