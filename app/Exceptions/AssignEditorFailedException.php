<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when a section editor cannot be
 * assigned because the article is not in Submitted status.
 */
class AssignEditorFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_assign_editor_status'));
    }
}
