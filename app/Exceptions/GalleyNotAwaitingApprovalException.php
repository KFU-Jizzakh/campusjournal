<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when author galley actions
 * (approve or request revision) are attempted on an article
 * that is not in AwaitingApproval status.
 *
 * SPECIFICATION: SPEC-13/AC-4, SPEC-13/AC-5
 */
class GalleyNotAwaitingApprovalException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_galley_not_awaiting_approval'));
    }
}
