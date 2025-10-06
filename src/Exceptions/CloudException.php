<?php

namespace S2low\Exceptions;

use Throwable;

class CloudException extends \RuntimeException
{
    public function __construct(string $transactionId, $cloudId, ?Throwable $previous = null)
    {
        $message = sprintf('Accès cloud indisponible pour la transaction %s avec le cloudId : %s.', $transactionId, $cloudId);

        parent::__construct($message, 0, $previous);
    }
}
