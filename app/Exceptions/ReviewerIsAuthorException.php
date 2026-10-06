<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when a reviewer assignment targets
 * the article's own submitter or one of its credited coauthors —
 * self-review of one's own manuscript is a conflict of interest.
 */
final class ReviewerIsAuthorException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_reviewer_is_author'));
    }
}
