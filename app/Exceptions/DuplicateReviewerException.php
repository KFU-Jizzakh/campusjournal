<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when the same reviewer is assigned
 * to an article twice (non-declined reviews only).
 *
 * SPECIFICATION: SPEC-02/BR-8
 */
class DuplicateReviewerException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_duplicate_reviewer'));
    }
}
