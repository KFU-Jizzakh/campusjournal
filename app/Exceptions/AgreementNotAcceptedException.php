<?php

namespace App\Exceptions;

use DomainException;

/**
 * PURPOSE: Domain exception thrown when an article submission
 * is attempted without accepting the copyright agreement.
 */
final class AgreementNotAcceptedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_agreement_not_accepted'));
    }
}
