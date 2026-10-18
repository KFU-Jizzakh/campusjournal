<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when an article cannot be sent to
 * production because it is not in Copyediting status.
 */
class SendToProductionFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('article.error_send_to_production_status'));
    }
}
