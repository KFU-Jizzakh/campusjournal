<?php

namespace App\Services\Oai;

use App\Models\Article;
use App\Models\Category;
use App\Models\Issue;
use Illuminate\Support\Facades\Cache;

/**
 * PURPOSE: Resolves OAI-PMH sets into filtered article queries
 * for category-based and issue-based set hierarchies.
 *
 * SPECIFICATION: SPEC-09/AC-7
 */
class OaiSetResolver
{
    /**
     * @return array<int, array{spec: string, name: string}>
     */
    public static function all(): array
    {
        return Cache::remember('oai.sets', 300, function () {
            $sets = [];

            $categories = Category::query()
                ->whereHas('articles', fn ($q) => $q->published())
                ->orderBy('name')
                ->get();

            foreach ($categories as $category) {
                $sets[] = [
                    'spec' => 'category:'.$category->slug,
                    'name' => $category->name,
                ];
            }

            $issues = Issue::query()
                ->whereHas('articles', fn ($q) => $q->published())
                ->orderByDesc('year')
                ->orderByDesc('volume')
                ->orderByDesc('number')
                ->get();

            foreach ($issues as $issue) {
                $sets[] = [
                    'spec' => 'issue:'.$issue->id,
                    'name' => $issue->full_title,
                ];
            }

            return $sets;
        });
    }

    public static function exists(string $setSpec): bool
    {
        if (str_starts_with($setSpec, 'category:')) {
            $slug = substr($setSpec, strlen('category:'));

            return Category::where('slug', $slug)->exists();
        }

        if (str_starts_with($setSpec, 'issue:')) {
            $id = substr($setSpec, strlen('issue:'));

            return ctype_digit($id) && Issue::whereKey((int) $id)->exists();
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public static function setsForArticle(Article $article): array
    {
        $specs = [];

        if ($article->category && $article->category->slug) {
            $specs[] = 'category:'.$article->category->slug;
        }

        if ($article->issue_id) {
            $specs[] = 'issue:'.$article->issue_id;
        }

        return $specs;
    }
}
