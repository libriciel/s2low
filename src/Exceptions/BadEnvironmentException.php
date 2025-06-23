<?php

namespace S2low\Exceptions;

class BadEnvironmentException extends \RuntimeException
{
    public function __construct(string $message = 'Invalid environment configuration.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
