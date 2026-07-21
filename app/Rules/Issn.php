<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * PURPOSE: Validation rule for ISSN format (XXXX-XXXX) with ISO 3297 mod 11 check digit.
 *
 * SPECIFICATION: SPEC-09, SPEC-10, SPEC-11
 */
class Issn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $issn = trim(str_replace('ISSN ', '', (string) $value));

        if (! preg_match('/^\d{4}-\d{3}[\dX]$/', $issn)) {
            $fail('The :attribute must be in ISSN format (e.g. 1234-5678).');

            return;
        }

        $digits = str_replace('-', '', $issn);
        $sum = 0;

        for ($i = 0; $i < 7; $i++) {
            $sum += (int) $digits[$i] * (8 - $i);
        }

        $remainder = $sum % 11;
        $expectedValue = (11 - $remainder) % 11;
        $expected = $expectedValue === 10 ? 'X' : (string) $expectedValue;

        if ($digits[7] !== $expected) {
            $fail('The :attribute check digit is invalid.');
        }
    }
}
