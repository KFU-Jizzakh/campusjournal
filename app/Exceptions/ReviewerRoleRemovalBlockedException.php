<?php

namespace App\Exceptions;

/**
 * PURPOSE: Domain exception thrown when a user tries to drop the
 * reviewer role while still having active (pending or in-progress)
 * review assignments.
 */
final class ReviewerRoleRemovalBlockedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct(__('profile.error_reviewer_role_blocked'));
    }
}
