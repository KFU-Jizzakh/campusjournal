<?php

namespace App\Exceptions;

/**
 * PURPOSE: Thrown when attempting to send an article to production
 * without having first uploaded a corrected manuscript file during
 * the Copyediting stage.
 *
 * SPECIFICATION: SPEC-04/AC-5, SPEC-04/BR-4a
 */
final class CopyeditedFileNotUploadedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_copyedited_file_not_uploaded'));
    }
}
