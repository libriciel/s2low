<?php

namespace S2low\Services\XMLFromDGFiP;

use S2low\DTO\PesEntrantHandlingResult;
use S2low\Services\XMLFromDGFiP\PesBuilder\ParsedPes;
use SplFileObject;

interface ParsedXmlFileStrategy
{
    public function canHandle(ParsedPes $pes): bool;
    public function handle(SplFileObject $fileObject, ParsedPes $pes): PesEntrantHandlingResult;
}
