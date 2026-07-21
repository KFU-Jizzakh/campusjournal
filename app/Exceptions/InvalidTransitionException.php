<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when an article or review status
 * transition is not allowed by the state machine.
 *
 * SPECIFICATION: SPEC-01/BR-5, SPEC-03/BR-3
 */
class InvalidTransitionException extends \DomainException
{
    public function __construct(string $fromLabel, string $toLabel)
    {
        parent::__construct(__('article.error_transition_invalid', [
            'from' => $fromLabel,
            'to' => $toLabel,
        ]));
    }
}
