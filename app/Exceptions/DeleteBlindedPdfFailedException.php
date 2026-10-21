<?php

namespace App\Exceptions;

/**
 * PURPOSE: Thrown when attempting to delete the anonymised manuscript
 * while the article still has active reviewers — removing the blinded
 * PDF mid-review would break the double-blind anonymity contract.
 */
final class DeleteBlindedPdfFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_delete_blinded_pdf_active_reviewers'));
    }
}
