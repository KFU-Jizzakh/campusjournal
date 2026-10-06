<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when a user attempts to claim an
 * author record without ownership proof (matching verified email on the
 * article pivot snapshot) or when the record already belongs to someone else.
 */
final class AuthorClaimFailedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('author.error_claim_failed'));
    }
}
