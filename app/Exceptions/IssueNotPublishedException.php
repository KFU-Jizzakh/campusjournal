<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when a published article is
 * placed into an issue that has not been published.
 *
 * SPECIFICATION: SPEC-24/BR-1
 */
final class IssueNotPublishedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_issue_not_published'));
    }
}
