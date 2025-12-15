<?php

namespace S2low\Exceptions;

class ARActeAlreadyReadException extends \RuntimeException
{
    public function __construct(string $transactionId, int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf("Un acquittement à deja été recu pour la transaction : %s. Arret du traitement.", $transactionId);
        parent::__construct($message, $code, $previous);
    }
}
