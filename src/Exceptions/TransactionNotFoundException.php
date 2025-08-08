<?php

namespace S2low\Exceptions;

class TransactionNotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Impossible de trouver la transaction recherché.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
