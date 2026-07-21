<?php

namespace App\Exceptions\Oai;

/**
 * PURPOSE: OAI-PMH domain exception for invalid or expired resumption token.
 *
 * SPECIFICATION: SPEC-09/BR-3
 */
class BadResumptionTokenException extends OaiException
{
    public function errorCode(): string
    {
        return 'badResumptionToken';
    }
}
