<?php

namespace App\Services\Jats;

use App\Enums\ArticleFileType;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * PURPOSE: Generates JATS/NLM Publishing 1.3 XML for article
 * export, supporting uploaded override and HTML-to-JATS body
 * conversion.
 *
 * SPECIFICATION: SPEC-10/AC-1, SPEC-10/AC-3, SPEC-10/AC-4, SPEC-10/AC-5, SPEC-10/BR-1, SPEC-10/BR-2
 */
class JatsXmlBuilder
{
    public function __construct(private HtmlToJatsConverter $converter) {}

    public function build(Article $article, bool $inline = false): string
    {
        $override = $this->uploadedOverride($article);
        if ($override !== null) {
            return $override;
        }

        $article->loadMissing(['authors', 'issue', 'category', 'references']);

        $affiliations = $this->buildAffiliations($article);

        return View::make('jats.article', [
            'article' => $article,
            'issue' => $article->issue,
            'authors' => $article->authors,
            'affiliations' => $affiliations,
            'resourceUrl' => route('articles.show', $article),
            'bodyXml' => $this->converter->convert($article->body),
            'authorNameParts' => fn ($author) => $author->name_parts,
            'inline' => $inline,
            'printIssn' => Setting::get('journal_issn_print'),
            'electronicIssn' => Setting::get('journal_issn_electronic'),
            'funding' => collect($article->funding ?? [])->map(function (array $funder): array {
                $funder['identifier_type'] = Article::funderIdentifierType($funder['funder_identifier'] ?? '');

                return $funder;
            })->all(),
        ])->render();
    }

    public function hasValidUploadedOverride(Article $article): bool
    {
        $file = $this->latestJatsFile($article);

        if (! $file) {
            return true;
        }

        $content = Storage::disk('public')->get($file->file_path);

        if ($content === null) {
            return false;
        }

        return $this->isWellFormedXml($content);
    }

    private function uploadedOverride(Article $article): ?string
    {
        $file = $this->latestJatsFile($article);

        if (! $file) {
            return null;
        }

        $content = Storage::disk('public')->get($file->file_path);

        if ($content === null) {
            return null;
        }

        if (! $this->isWellFormedXml($content)) {
            Log::warning('Uploaded JATS XML override is malformed, falling back to generated XML.', [
                'article_id' => $article->id,
                'file_id' => $file->id,
            ]);

            return null;
        }

        return $content;
    }

    private function latestJatsFile(Article $article): ?ArticleFile
    {
        if ($article->relationLoaded('files')) {
            return $article->files
                ->where('file_type', ArticleFileType::JatsXml->value)
                ->sortByDesc('created_at')
                ->first();
        }

        return $article->files()
            ->where('file_type', ArticleFileType::JatsXml->value)
            ->latest()
            ->first();
    }

    private function isWellFormedXml(string $content): bool
    {
        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $valid = $doc->loadXML($content);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $valid;
    }

    /**
     * @return array<int, array{id: string, name: string, author_ids: array<int,int>}>
     */
    private function buildAffiliations(Article $article): array
    {
        $map = [];
        $order = [];

        foreach ($article->authors as $author) {
            $org = trim((string) $author->organization);
            if ($org === '') {
                continue;
            }
            if (! isset($map[$org])) {
                $map[$org] = ['id' => 'aff'.(count($order) + 1), 'name' => $org, 'author_ids' => []];
                $order[] = $org;
            }
            $map[$org]['author_ids'][] = $author->id;
        }

        return array_values($map);
    }
}
