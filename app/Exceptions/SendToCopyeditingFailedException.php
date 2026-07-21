<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when an article cannot be sent to
 * copyediting because it is not in Accepted status.
 *
 * SPECIFICATION: SPEC-04/BR-5
 */
class SendToCopyeditingFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_send_to_copyediting_status'));
    }
}
