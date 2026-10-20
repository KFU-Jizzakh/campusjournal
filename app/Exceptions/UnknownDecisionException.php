<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when an editorial decision carries
 * an unsupported key — the model must stay safe even when the caller
 * (controller validation, Filament, console command) did not
 * pre-validate the decision string.
 */
class UnknownDecisionException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_unknown_decision'));
    }
}
