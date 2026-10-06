<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when an editorial attempt to cancel
 * a review assignment targets a review that is already terminal
 * (completed, declined, or cancelled).
 */
final class CancelReviewFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_review_cancel'));
    }
}
