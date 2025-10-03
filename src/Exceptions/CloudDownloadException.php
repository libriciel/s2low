<?php

namespace S2low\Exceptions;

class CloudDownloadException extends \RuntimeException
{
    public function __construct(string $transactionId, string $cloudId, ?\Throwable $previous = null)
    {
        $message = sprintf('Une erreur est survenu pendant la tentative de téléchargement du fichier metier %s et lié à la transaction %s.', $cloudId, $transactionId);
        parent::__construct($message, 0, $previous);
    }
}
