<?php

namespace S2low\Services\ProcessCommand;

use DateTime;
use S2low\Services\Certificates\CRLReader;

class CheckSnInCRLFactory
{
    public function __construct(private readonly CRLReader $crlReader)
    {
    }

    public function get(string $serialNumber, DateTime $dateTime)
    {
        return new CheckSnInCRLCommandOutputTranslator($serialNumber, $dateTime, $this->crlReader);
    }
}
