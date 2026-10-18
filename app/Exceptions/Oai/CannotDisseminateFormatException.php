<?php

namespace App\Exceptions\Oai;

class CannotDisseminateFormatException extends OaiException
{
    public function errorCode(): string
    {
        return 'cannotDisseminateFormat';
    }
}
