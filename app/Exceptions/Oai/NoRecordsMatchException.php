<?php

namespace App\Exceptions\Oai;

class NoRecordsMatchException extends OaiException
{
    public function errorCode(): string
    {
        return 'noRecordsMatch';
    }
}
