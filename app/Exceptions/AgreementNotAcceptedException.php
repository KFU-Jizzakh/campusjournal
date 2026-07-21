<?php

namespace App\Exceptions;

use DomainException;

/**
 * PURPOSE: Domain exception thrown when an article submission
 * is attempted without accepting the copyright agreement.
 *
 * SPECIFICATION: SPEC-14/BR-1, SPEC-14/BR-2
 */
final class AgreementNotAcceptedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_agreement_not_accepted'));
    }
}
