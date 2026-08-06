<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when a DOI cannot be generated
 * because the Crossref prefix is not configured.
 *
 * SPECIFICATION: SPEC-08/BR-2a
 */
final class DoiPrefixNotConfiguredException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_doi_prefix_not_configured'));
    }
}
