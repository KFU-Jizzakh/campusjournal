<?php

namespace App\Exceptions\Oai;

use RuntimeException;

/**
 * PURPOSE: Abstract base exception for all OAI-PMH protocol errors,
 * requiring each subclass to provide an error code.
 *
 * SPECIFICATION: SPEC-09/AC-2
 */
abstract class OaiException extends RuntimeException
{
    abstract public function errorCode(): string;
}
