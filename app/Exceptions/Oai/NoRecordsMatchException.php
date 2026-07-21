<?php

namespace App\Exceptions\Oai;

/**
 * PURPOSE: OAI-PMH domain exception for queries returning no records.
 *
 * SPECIFICATION: SPEC-09/AC-3
 */
class NoRecordsMatchException extends OaiException
{
    public function errorCode(): string
    {
        return 'noRecordsMatch';
    }
}
