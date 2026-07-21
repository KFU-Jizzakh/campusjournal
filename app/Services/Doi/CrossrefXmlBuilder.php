<?php

namespace App\Services\Doi;

use App\Models\Article;
use Illuminate\Support\Facades\View;

/**
 * PURPOSE: Renders Crossref deposit XML v5.3.1 from a
 * Blade template using article and issue metadata.
 *
 * SPECIFICATION: SPEC-08/AC-4
 */
class CrossrefXmlBuilder
{
    public function build(Article $article, string $batchId): string
    {
        $article->loadMissing(['authors', 'issue']);

        $config = config('services.crossref');

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
        ])->render();
    }
}
