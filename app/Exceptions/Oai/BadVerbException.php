<?php

namespace App\Exceptions\Oai;

class BadVerbException extends OaiException
{
    public function errorCode(): string
    {
        return 'badVerb';
    }
}
