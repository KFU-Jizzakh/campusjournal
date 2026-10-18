<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when attempting to publish
 * an article that has not been approved by the author via
 * the galley proof workflow.
 */
class GalleyApprovalRequiredException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_galley_approval_required'));
    }
}
