<?php

namespace App\Exceptions\Oai;

/**
 * PURPOSE: OAI-PMH domain exception for non-existent record identifier.
 *
 * SPECIFICATION: SPEC-09/AC-9
 */
class IdDoesNotExistException extends OaiException
{
    public function errorCode(): string
    {
        return 'idDoesNotExist';
    }
}
