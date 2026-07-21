<?php

namespace App\Exceptions\Oai;

/**
 * PURPOSE: OAI-PMH domain exception for invalid request arguments.
 *
 * SPECIFICATION: SPEC-09/AC-3
 */
class BadArgumentException extends OaiException
{
    public function errorCode(): string
    {
        return 'badArgument';
    }
}
