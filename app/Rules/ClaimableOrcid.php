<?php

namespace App\Rules;

use App\Models\Author;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * PURPOSE: Validation rule for ORCID iD claimability. Only a record
 * already linked to a different user account reserves the ORCID: rows
 * created from coauthor listings carry no verified ownership, so they
 * neither prove nor deny a claim — otherwise anybody could block the
 * real owner by pre-listing their ORCID. A user who already owns a
 * linked row for the ORCID may always reuse it.
 */
class ClaimableOrcid implements ValidationRule
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (Author::withTrashed()->where('orcid', $value)->where('user_id', $this->user->id)->exists()) {
            return;
        }

        $conflict = Author::withTrashed()->where('orcid', $value)
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $this->user->id)
            ->exists();

        if ($conflict) {
            $fail('The :attribute has already been taken.');
        }
    }
}
