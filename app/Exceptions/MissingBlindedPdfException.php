<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when a reviewer is assigned to a
 * double-blind article without the anonymised manuscript being uploaded.
 *
 * SPECIFICATION: SPEC-05/BR-2
 */
class MissingBlindedPdfException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_missing_blinded_pdf'));
    }
}
