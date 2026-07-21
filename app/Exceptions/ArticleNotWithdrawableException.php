<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when withdrawal is
 * attempted on an article that is not in a withdrawable status.
 *
 * SPECIFICATION: SPEC-16/BR-1
 */
final class ArticleNotWithdrawableException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_not_withdrawable'));
    }
}
