<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when an editorial assignment targets
 * the article's own submitter or one of its credited coauthors.
 */
final class EditorIsAuthorException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_editor_is_author'));
    }
}
