<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when a reviewer cannot be assigned
 * because the article is not in Submitted or InReview status.
 *
 * SPECIFICATION: SPEC-02/BR-4
 */
class AssignReviewerFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_assign_reviewer_status'));
    }
}
