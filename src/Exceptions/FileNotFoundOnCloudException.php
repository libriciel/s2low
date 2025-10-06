<?php

namespace S2low\Exceptions;

class FileNotFoundOnCloudException extends \RuntimeException
{
    public function __construct(string $message = 'Une erreur inconnue est survenue.', int $code = 0, ?\Throwable $previous = null)
    {
        $message = 'Le fichier n\'est pas present sur dans le cloud';
        parent::__construct($message, $code, $previous);
    }
}
