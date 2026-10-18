<?php

namespace App\Exceptions\Oai;

class IdDoesNotExistException extends OaiException
{
    public function errorCode(): string
    {
        return 'idDoesNotExist';
    }
}
