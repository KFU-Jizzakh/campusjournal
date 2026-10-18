<?php

namespace App\Exceptions\Oai;

use RuntimeException;

/**
 * PURPOSE: Abstract base exception for all OAI-PMH protocol errors,
 * requiring each subclass to provide an error code.
 */
abstract class OaiException extends RuntimeException
{
    abstract public function errorCode(): string;
}
