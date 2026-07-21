<?php

namespace App\Exceptions\Oai;

/**
 * PURPOSE: OAI-PMH domain exception for unsupported metadata format.
 *
 * SPECIFICATION: SPEC-09/AC-4
 */
class CannotDisseminateFormatException extends OaiException
{
    public function errorCode(): string
    {
        return 'cannotDisseminateFormat';
    }
}
