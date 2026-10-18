<?php

namespace App\Exceptions\Oai;

class BadArgumentException extends OaiException
{
    public function errorCode(): string
    {
        return 'badArgument';
    }
}
