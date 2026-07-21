<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when the review type cannot be
 * changed because active reviewers exist on the article.
 *
 * SPECIFICATION: SPEC-05/BR-1
 */
class ReviewTypeChangeForbiddenException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_review_type_change_forbidden'));
    }
}
