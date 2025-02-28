<?php

namespace S2low\Domain\Exception;

class CreateNewFileException extends \Exception
{
    public function __construct(string $filePath)
    {
        parent::__construct("Impossible de creer le fichier : '{$filePath}'");
    }
}