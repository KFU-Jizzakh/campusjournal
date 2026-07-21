<?php

namespace App\Services\Doi;

use App\Models\Article;

/**
 * PURPOSE: Idempotent DOI generator using the configured
 * Crossref prefix and article metadata pattern.
 *
 * SPECIFICATION: SPEC-08/AC-2, SPEC-08/BR-2
 */
class DoiMinter
{
    public function mint(Article $article): string
    {
        if (filled($article->doi)) {
            return $article->doi;
        }

        $prefix = (string) config('services.crossref.prefix');
        $pattern = (string) config('services.crossref.doi_pattern', '{prefix}/kfujournal.{year}.{volume}.{article_id}');

        $issue = $article->issue;

        return strtr($pattern, [
            '{prefix}' => $prefix,
            '{year}' => (string) ($issue?->year ?? now()->year),
            '{volume}' => (string) ($issue?->volume ?? 0),
            '{number}' => (string) ($issue?->number ?? 0),
            '{article_id}' => (string) $article->id,
        ]);
    }
}
