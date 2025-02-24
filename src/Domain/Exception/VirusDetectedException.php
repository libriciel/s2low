<?php

namespace S2low\Domain\Exception;

class VirusDetectedException extends \RuntimeException
{
    public function __construct(string $filePath, string $virusName)
    {
        parent::__construct("Le virus '{$virusName}' à été détecté dans le fichier : '$filePath'");
    }
}