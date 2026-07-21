<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when an editorial decision is
 * attempted but no reviews have been completed yet.
 *
 * SPECIFICATION: SPEC-04/BR-1
 */
class MissingCompletedReviewsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_missing_reviews'));
    }
}
