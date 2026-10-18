<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when attempting to send a galley
 * proof to the author without first uploading a typeset PDF.
 */
class GalleyPdfNotUploadedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_galley_pdf_not_uploaded'));
    }
}
