<?php

namespace S2low\Security\Exceptions;

class CertificateExtractionException extends \Exception
{
    public function __construct(string $message = ' impossible de continuer.')
    {
        parent::__construct('La connexion à échoué : ' . $message);
    }
}
