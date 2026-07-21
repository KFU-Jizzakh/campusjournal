<?php

namespace App\Services\Doi;

use App\Models\Article;
use Illuminate\Support\Facades\View;

/**
 * PURPOSE: Renders Crossref deposit XML v5.3.1 from a
 * Blade template using article and issue metadata.
 * Crossmark metadata is always included; when an updateType
 * is provided, a <doi_updates> block is appended.
 *
 * SPECIFICATION: SPEC-08/AC-4, SPEC-16/AC-5, SPEC-16/BR-7, SPEC-16/BR-8
 */
class CrossrefXmlBuilder
{
    public function build(Article $article, string $batchId, ?string $updateType = null): string
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

        return View::make('crossref.deposit', [
            'article' => $article,
            'issue' => $article->issue,
            'authors' => $article->authors,
            'batchId' => $batchId,
            'timestamp' => now()->format('YmdHis'),
            'depositorName' => $config['depositor_name'] ?? 'Depositor',
            'depositorEmail' => $config['depositor_email'] ?? 'noreply@example.com',
            'registrant' => $config['registrant'] ?? 'Registrant',
            'resourceUrl' => route('articles.show', $article),
            'authorNameParts' => fn ($author) => $author->name_parts,
            'updateType' => $updateType,
            'crossmarkPolicyUrl' => $crossmark['policy_url'] ?? '',
            'crossmarkDomains' => $crossmark['domains'] ?? [],
            'funding' => $article->funding ?? [],
        ])->render();
    }
}
