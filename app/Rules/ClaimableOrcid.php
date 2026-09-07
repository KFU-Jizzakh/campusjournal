<?php

namespace App\Rules;

use App\Models\Author;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * PURPOSE: Validation rule for ORCID iD claimability. An unlinked coauthor
 * row only proves ownership when it carries a matching contact email, so
 * rows without an email never block a claim. A user who already owns a
 * linked row for the ORCID may always reuse it, even if a stale unlinked
 * row's email has since diverged.
 */
class ClaimableOrcid implements ValidationRule
{
    public function __construct(
        private readonly User $user,
        private readonly ?string $email,
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
            ->where(function ($query) {
                $query->whereNotNull('user_id')->where('user_id', '!=', $this->user->id)
                    ->orWhere(function ($query) {
                        $query->whereNull('user_id')
                            ->whereNotNull('email')
                            ->where('email', '!=', $this->email);
                    });
            })
            ->exists();

        if ($conflict) {
            $fail('The :attribute has already been taken.');
        }
    }
}
