<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when a user assigned as section
 * editor does not have the section-editor role.
 *
 * SPECIFICATION: SPEC-02/BR-2
 */
class NotSectionEditorException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_not_section_editor'));
    }
}
