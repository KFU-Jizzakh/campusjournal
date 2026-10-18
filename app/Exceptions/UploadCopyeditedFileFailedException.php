<?php

namespace App\Exceptions;

/**
 * PURPOSE: Thrown when attempting to upload a corrected manuscript file
 * while the article is not in the Copyediting stage.
 */
final class UploadCopyeditedFileFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_upload_copyedited_file_status'));
    }
}
