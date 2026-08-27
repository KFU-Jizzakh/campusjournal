<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when an issue's status is
 * changed away from published while it still contains
 * published articles.
 *
 * SPECIFICATION: SPEC-24/BR-1
 */
final class IssueUnpublishFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('issue.error_unpublish_with_published_articles'));
    }
}
