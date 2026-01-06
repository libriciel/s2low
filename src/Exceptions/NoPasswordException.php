<?php

namespace S2low\Exceptions;

class NoPasswordException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Connexion Impossible. Aucun mot de passe ne correspond à cet identifiant de connexion.');
    }
}
