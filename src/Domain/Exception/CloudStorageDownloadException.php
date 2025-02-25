<?php

namespace S2low\Domain\Exception;

class CloudStorageDownloadException extends \RuntimeException
{
    public function __construct(string $filePath)
    {
        parent::__construct("Une erreur est survenue lors du téléchargement du fichier '$filePath'");
    }
}