<?php

namespace S2low\Exceptions;

class CloudFileDeletionException extends \RuntimeException
{
    public function __construct(string $transactionId, string $cloudId, ?\Throwable $previous = null)
    {
        $message = sprintf('Impossible de supprimer le fichier metier %s de la transaction %s sur le service cloud.', $cloudId, $transactionId);
        parent::__construct($message, 0, $previous);
    }
}
