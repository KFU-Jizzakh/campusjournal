<?php

namespace App\Exceptions;

/**
 * PURPOSE: Thrown when attempting to delete a corrected manuscript file
 * while the article is not in the Copyediting stage.
 */
final class DeleteCopyeditedFileFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_delete_copyedited_file_status'));
    }
}
