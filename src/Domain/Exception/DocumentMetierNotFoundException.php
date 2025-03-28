<?php

namespace S2low\Domain\Exception;

class DocumentMetierNotFoundException extends \RuntimeException
{
    /**
     * @param $path
     */
    public function __construct($path)
    {
        parent::__construct("Le document metier situé au chemin : '$path' est introuvable.");
    }
}
