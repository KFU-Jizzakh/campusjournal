<?php

namespace App\Policies;

use App\Models\CrossrefDeposit;
use App\Models\User;

/**
 * PURPOSE: Defines access control for Crossref DOI deposits,
 * requiring manage-submissions for viewing and manage-doi
 * for create/update/delete operations.
 */
class CrossrefDepositPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage-submissions');
    }

    public function view(User $user, CrossrefDeposit $deposit): bool
    {
        return $user->hasPermissionTo('manage-submissions');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-doi');
    }

    public function update(User $user, CrossrefDeposit $deposit): bool
    {
        return $user->hasPermissionTo('manage-doi');
    }

    public function delete(User $user, CrossrefDeposit $deposit): bool
    {
        return $user->hasPermissionTo('manage-doi');
    }

    public function restore(User $user, CrossrefDeposit $deposit): bool
    {
        return $user->hasPermissionTo('manage-doi');
    }

    public function forceDelete(User $user, CrossrefDeposit $deposit): bool
    {
        return $user->hasPermissionTo('manage-doi');
    }
}
