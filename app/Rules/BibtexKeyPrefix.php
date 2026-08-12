<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * PURPOSE: Validation rule for the BibTeX citation-key prefix —
 * allows only letters, digits, hyphens and underscores (empty allowed).
 *
 * SPECIFICATION: SPEC-10/AC-9
 */
class BibtexKeyPrefix implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! preg_match('/^[A-Za-z0-9_-]*$/', (string) $value)) {
            $fail('The :attribute may only contain letters, digits, hyphens and underscores.');
        }
    }
}
