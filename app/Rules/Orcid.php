<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * PURPOSE: Validation rule for ORCID iD format.
 */
class Orcid implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && $value !== '' && ! \App\Support\Orcid::isValid($value)) {
            $fail('The :attribute must be a valid ORCID iD (e.g. 0000-0001-2345-6789).');
        }
    }
}
