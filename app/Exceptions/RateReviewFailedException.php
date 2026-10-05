<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when an editorial quality rating is
 * attempted on a review that is not completed yet.
 */
class RateReviewFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_review_not_completed'));
    }
}
