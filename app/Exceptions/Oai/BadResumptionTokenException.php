<?php

namespace App\Exceptions\Oai;

class BadResumptionTokenException extends OaiException
{
    public function errorCode(): string
    {
        return 'badResumptionToken';
    }
}
