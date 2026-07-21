<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when galley operations are
 * attempted on an article that is not in Production status.
 *
 * SPECIFICATION: SPEC-13/AC-1
 */
class GalleyNotProductionException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_galley_not_production'));
    }
}
