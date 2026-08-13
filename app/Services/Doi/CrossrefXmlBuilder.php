<?php

namespace App\Services\Doi;

use App\Models\Article;
use Illuminate\Support\Facades\View;

/**
 * PURPOSE: Renders Crossref deposit XML v5.3.1 from a
 * Blade template using article and issue metadata.
 * Crossmark metadata is included inside journal_article when a
 * policy DOI is configured; when an updateType is provided, an
 * <updates> block with one <update> per pending update is added.
 * An explicit $doi overrides the article's stored DOI (used when
 * the DOI was minted but could not be persisted yet).
 *
 * SPECIFICATION: SPEC-08/AC-4, SPEC-16/AC-5, SPEC-16/BR-7, SPEC-16/BR-8
 */
class CrossrefXmlBuilder
{
    public function build(Article $article, string $batchId, ?string $updateType = null, ?string $doi = null): string
    {
        $article->loadMissing(['authors', 'issue']);

        if ($updateType) {
            $article->loadMissing('corrections');
        }

        if ($updateType === 'correction' && $article->corrections->isEmpty()) {
            $updateType = null;
        }

        $config = config('services.crossref');
        $crossmark = $config['crossmark'] ?? [];

        $updates = $this->buildUpdates($article, $updateType, $doi);

        return View::make('crossref.deposit', [
            'article' => $article,
            'issue' => $article->issue,
            'authors' => $article->authors,
            'doi' => $doi,
            'batchId' => $batchId,
            'timestamp' => now()->format('YmdHis'),
            'depositorName' => $config['depositor_name'] ?? 'Depositor',
            'depositorEmail' => $config['depositor_email'] ?? 'noreply@example.com',
            'registrant' => $config['registrant'] ?? 'Registrant',
            'resourceUrl' => route('articles.show', $article),
            'authorNameParts' => fn ($author) => $author->name_parts,
            'updates' => $updates,
            'crossmarkPolicyDoi' => $crossmark['policy_doi'] ?? null,
            'crossmarkDomains' => $crossmark['domains'] ?? [],
            'funding' => $article->funding ?? [],
        ])->render();
    }

    /**
     * PURPOSE: Builds the list of Crossmark <update> records for a
     * re-deposit: for corrections, one <update> per remaining
     * correction in chronological order (oldest first, ties broken
     * by id), typed by the correction's own type and dated by
     * published_at (falling back to today when missing); for
     * retractions, the corrections' updates followed by the
     * retraction update dated by retracted_at. Each update carries
     * the article's DOI — or the explicit minted DOI when it could
     * not be persisted yet.
     *
     * SPECIFICATION: SPEC-16/AC-5, SPEC-16/BR-7
     */
    private function buildUpdates(Article $article, ?string $updateType, ?string $doi = null): array
    {
        if (! $updateType) {
            return [];
        }

        $updateDoi = $doi ?? $article->doi;

        $correctionUpdates = $article->corrections
            ->sortBy([
                ['published_at', 'asc'],
                ['id', 'asc'],
            ])
            ->map(fn ($correction) => [
                'type' => $correction->type->value,
                'date' => $correction->published_at?->toDateString() ?? now()->toDateString(),
                'doi' => $updateDoi,
            ])
            ->values()
            ->all();

        if ($updateType === 'retraction') {
            return array_merge($correctionUpdates, [[
                'type' => 'retraction',
                'date' => $article->retracted_at?->toDateString() ?? now()->toDateString(),
                'doi' => $updateDoi,
            ]]);
        }

        return $correctionUpdates;
    }
}
