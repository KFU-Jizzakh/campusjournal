<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when a user cannot be assigned as
 * a reviewer because they lack the review-article permission.
 *
 * SPECIFICATION: SPEC-02/BR-5
 */
class CannotReviewArticleException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_cannot_review'));
    }
}
