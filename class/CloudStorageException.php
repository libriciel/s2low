<?php

namespace S2lowLegacy\Class;

use Exception;

class CloudStorageException extends Exception
{
    public function __construct(string $message = 'Une erreur est survenue pendant la tentative d\'acces au service de CloudStorage.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
