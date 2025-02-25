<?php

namespace S2low\Domain\Exception;

class CloudStorageFileNotFoundException extends \RuntimeException
{
    public function __construct(string $filePath)
    {
        parent::__construct("Le fichier '{$filePath}' est introuvable sur le stockage à distance.");
    }
}