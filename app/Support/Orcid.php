<?php

namespace App\Support;

/**
 * PURPOSE: ORCID identifier validation and format helper.
 */
final class Orcid
{
    public static function isValid(?string $orcid): bool
    {
        if ($orcid === null || trim($orcid) === '') {
            return true;
        }

        return preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid) === 1;
    }

    public static function url(?string $orcid): ?string
    {
        if ($orcid === null) {
            return null;
        }

        $orcid = trim($orcid);
        if ($orcid === '') {
            return null;
        }
        if (preg_match('#^https?://orcid\.org/#i', $orcid)) {
            return $orcid;
        }

        return 'https://orcid.org/'.ltrim($orcid, '/');
    }
}
