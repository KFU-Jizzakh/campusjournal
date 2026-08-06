<?php

namespace App\Services\Doi;

use App\Exceptions\DoiPrefixNotConfiguredException;
use App\Models\Article;

/**
 * PURPOSE: Idempotent DOI generator producing a full DOI as the
 * configured Crossref prefix plus a short, opaque random suffix
 * (Crossref best practice: no readable metadata in suffixes).
 *
 * SPECIFICATION: SPEC-08/AC-2, SPEC-08/BR-2, SPEC-08/BR-2a, SPEC-08/BR-2b, SPEC-08/BR-2c
 */
class DoiMinter
{
    /**
     * Unambiguous charset: no 0/O, 1/I/l that are easy to mistype.
     */
    public const SUFFIX_CHARSET = 'abcdefghjkmnpqrstuvwxyz23456789';

    public function mint(Article $article): string
    {
        if (filled($article->doi)) {
            return $article->doi;
        }

        if (! $this->isConfigured()) {
            throw new DoiPrefixNotConfiguredException;
        }

        return $this->prefix().'/'.$this->suffix();
    }

    /**
     * Generate a random opaque DOI suffix of the configured length.
     */
    public function suffix(): string
    {
        $length = max(1, (int) config('services.crossref.doi_suffix_length', 8));
        $maxIndex = strlen(self::SUFFIX_CHARSET) - 1;
        $suffix = '';

        for ($i = 0; $i < $length; $i++) {
            $suffix .= self::SUFFIX_CHARSET[random_int(0, $maxIndex)];
        }

        return $suffix;
    }

    /**
     * The configured Crossref prefix, or an empty string when unset.
     */
    public function prefix(): string
    {
        return (string) config('services.crossref.prefix');
    }

    /**
     * Whether a DOI can be generated (a prefix must be configured).
     *
     * SPECIFICATION: SPEC-08/BR-2a
     */
    public function isConfigured(): bool
    {
        return filled($this->prefix());
    }

    /**
     * Whether DOI deposits can be dispatched: the Crossref service is
     * enabled and the prefix is configured. Used by all dispatch paths
     * (publication, backfill, Filament manual deposit, crossmark).
     *
     * SPECIFICATION: SPEC-08/BR-1, SPEC-08/BR-2a
     */
    public function isReady(): bool
    {
        return (bool) config('services.crossref.enabled') && $this->isConfigured();
    }
}
