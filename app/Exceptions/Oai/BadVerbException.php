<?php

namespace App\Exceptions\Oai;

/**
 * PURPOSE: OAI-PMH domain exception for unsupported or missing verb.
 *
 * SPECIFICATION: SPEC-09/AC-3
 */
class BadVerbException extends OaiException
{
    public function errorCode(): string
    {
        return 'badVerb';
    }
}
