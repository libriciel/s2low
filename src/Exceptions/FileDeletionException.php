<?php

namespace S2low\Exceptions;

class FileDeletionException extends \RuntimeException
{
    public function __construct(string $filePath, ?\Throwable $previous = null)
    {
        $message = sprintf('Une erreur est survenue lors de la tentative de suppression de %s.', $filePath);
        parent::__construct($message, 0, $previous);
    }
}
