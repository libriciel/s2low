<?php

namespace S2low\Exceptions;

class ConnectionFailedException extends \RuntimeException
{
    public function __construct(string $message = ' impossible de continuer.')
    {
        parent::__construct('Une connexion a échoué : ' . $message);
    }
}
