<?php

namespace App\Services\Oai;

use App\Models\Article;

/**
 * PURPOSE: Generates and parses OAI-PMH record identifiers
 * using the oai:repository:article:{id} scheme.
 *
 * SPECIFICATION: SPEC-09/AC-9
 */
class OaiIdentifier
{
    public static function forArticle(Article $article): string
    {
        return 'oai:'.self::repositoryId().':article:'.$article->id;
    }

    public static function parse(string $identifier): ?array
    {
        $prefix = 'oai:'.self::repositoryId().':article:';

        if (! str_starts_with($identifier, $prefix)) {
            return null;
        }

        $id = substr($identifier, strlen($prefix));

        if ($id === '' || ! ctype_digit($id)) {
            return null;
        }

        return ['type' => 'article', 'id' => (int) $id];
    }

    public static function repositoryId(): string
    {
        return (string) config('oai.repository_id', 'localhost');
    }
}
