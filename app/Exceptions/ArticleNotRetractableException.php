<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when retraction is
 * attempted on an article that is not in Published status.
 *
 * SPECIFICATION: SPEC-16/BR-2
 */
final class ArticleNotRetractableException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_not_retractable'));
    }
}
