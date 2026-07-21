<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when attempting to send a galley
 * proof to the author without first uploading a typeset PDF.
 *
 * SPECIFICATION: SPEC-13/AC-1
 */
class GalleyPdfNotUploadedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_galley_pdf_not_uploaded'));
    }
}
