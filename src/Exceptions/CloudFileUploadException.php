<?php

namespace S2low\Exceptions;

class CloudFileUploadException extends \RuntimeException
{
    public function __construct(string $transactionId, string $cloudId, ?\Throwable $previous = null)
    {
        $message = sprintf('Impossible de sauvegarder le fichier metier %s de la transaction %s vers le service cloud.', $cloudId, $transactionId);
        parent::__construct($message, 0, $previous);
    }
}
